<?php

namespace Tests\Browser\Report;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class ReportTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $engineer;
    protected User $gm;
    protected User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->first();
        $this->gm = User::where('email', 'gm@aljabali.com')->first();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->first();
    }

    #[Test]
    public function gm_can_access_sales_reports_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->gm)
                ->visit('/sales-reports')
                ->assertPathIs('/sales-reports');
        });
    }

    #[Test]
    public function gm_can_access_hr_reports_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->gm)
                ->visit('/hr-reports')
                ->assertPathIs('/hr-reports');
        });
    }

    #[Test]
    public function engineer_cannot_access_sales_reports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-reports')
                ->assertPathIsNot('/sales-reports');
        });
    }

    #[Test]
    public function sales_manager_can_access_sales_reports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->salesManager)
                ->visit('/sales-reports')
                ->assertPathIs('/sales-reports');
        });
    }

    #[Test]
    public function sales_reports_shows_request_data(): void
    {
        $request = SalesApprovalRequest::create([
            'user_id' => $this->engineer->id,
            'request_number' => 'REQ-REPORT-0001',
            'client_name' => 'Report Test Client',
            'client_phone' => '+962790000099',
            'client_address' => 'Amman',
            'payment_method' => 'on_account',
            'total_amount' => 750.00,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        app(SalesApprovalService::class)->initializeStages($request);

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->gm)
                ->visit('/sales-reports')
                ->waitForText($request->request_number)
                ->assertSee($request->request_number);
        });
    }
}
