<?php

namespace Tests\Browser\AuditLog;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class AuditLogTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected User $generalManager;

    protected User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();

        $balance = app(LeaveService::class)->getOrCreateBalance($this->engineer);
        $before = app(AuditLogger::class)->leaveBalanceState($balance);

        $balance->update(['annual_used' => 1.5]);

        app(AuditLogger::class)->logLeaveBalanceAdjusted($balance->fresh('user'), $this->generalManager, $before);
    }

    #[Test]
    public function general_manager_can_open_audit_log_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->generalManager)
                ->visit('/audit-log')
                ->assertPathIs('/audit-log');
        });
    }
}
