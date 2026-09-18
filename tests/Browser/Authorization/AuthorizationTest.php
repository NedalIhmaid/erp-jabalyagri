<?php

namespace Tests\Browser\Authorization;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class AuthorizationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $engineer;
    protected User $warehouseKeeper;
    protected User $salesManager;
    protected User $purchasingManager;
    protected User $financialManager;
    protected User $gm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->first();
        $this->warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->first();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->first();
        $this->purchasingManager = User::where('email', 'purchasing@aljabali.com')->first();
        $this->financialManager = User::where('email', 'finance@aljabali.com')->first();
        $this->gm = User::where('email', 'gm@aljabali.com')->first();
    }

    #[Test]
    public function engineer_cannot_access_approval_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/approval-dashboard')
                ->assertPathIsNot('/approval-dashboard');
        });
    }

    #[Test]
    public function warehouse_keeper_cannot_access_gm_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->warehouseKeeper)
                ->visit('/general-manager-dashboard')
                ->assertPathIsNot('/general-manager-dashboard');
        });
    }

    #[Test]
    public function engineer_cannot_delete_sales_requests(): void
    {
        $request = $this->createRequest();

        // Engineer cannot delete (policy check)
        $this->assertFalse($this->engineer->can('delete', $request));
    }

    #[Test]
    public function only_gm_can_access_user_management(): void
    {
        // Engineer cannot access user list
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/users')
                ->assertPathIsNot('/users');
        });
    }

    #[Test]
    public function gm_can_access_user_management(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->gm)
                ->visit('/users')
                ->assertPathIs('/users');
        });
    }

    #[Test]
    public function warehouse_keeper_action_only_on_stage_1(): void
    {
        $service = app(SalesApprovalService::class);
        $request = $this->createRequest();

        // Stage 1: warehouse keeper CAN act
        $this->assertTrue($service->canActOnRequest($request, $this->warehouseKeeper));

        // After stage 1 approval, warehouse keeper CANNOT act on stage 2
        $service->approve($request, $this->warehouseKeeper);
        $this->assertFalse($service->canActOnRequest($request->fresh(), $this->warehouseKeeper));
    }

    #[Test]
    public function stage_approvers_cannot_act_out_of_turn(): void
    {
        $service = app(SalesApprovalService::class);
        $request = $this->createRequest();

        // Stage 1: only warehouse keeper can act
        $this->assertFalse($service->canActOnRequest($request, $this->salesManager));
        $this->assertFalse($service->canActOnRequest($request, $this->purchasingManager));
        $this->assertFalse($service->canActOnRequest($request, $this->financialManager));
        $this->assertFalse($service->canActOnRequest($request, $this->gm));
    }

    #[Test]
    public function only_warehouse_keeper_can_return_request(): void
    {
        $service = app(SalesApprovalService::class);
        $request = $this->createRequest();

        $this->assertTrue($service->canReturnRequest($request, $this->warehouseKeeper));
        $this->assertFalse($service->canReturnRequest($request, $this->salesManager));
        $this->assertFalse($service->canReturnRequest($request, $this->engineer));
        $this->assertFalse($service->canReturnRequest($request, $this->gm));
    }

    protected function createRequest(): SalesApprovalRequest
    {
        $request = SalesApprovalRequest::create([
            'user_id' => $this->engineer->id,
            'request_number' => 'REQ-AUTH-' . rand(1000, 9999),
            'client_name' => 'Auth Test Client',
            'client_phone' => '+962790000099',
            'client_address' => 'Amman',
            'payment_method' => 'on_account',
            'total_amount' => 100.00,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        app(SalesApprovalService::class)->initializeStages($request);

        return $request;
    }
}
