<?php

namespace Tests\Feature;

use App\Enums\SalesRequestStatus;
use App\Filament\Pages\ApprovalDashboard;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestSubmittedNotification;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PurchasingManagerWarehouseKeeperRoleTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $service;

    protected User $purchasingWarehouseManager;

    protected User $engineer;

    protected User $financialManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->service = app(SalesApprovalService::class);
        $this->purchasingWarehouseManager = User::role('purchasing_manager')->firstOrFail();
        $this->purchasingWarehouseManager->assignRole('warehouse_keeper');
        $this->engineer = User::role('engineer')->firstOrFail();
        $this->financialManager = User::role('financial_manager')->firstOrFail();
    }

    public function test_user_can_hold_purchasing_and_warehouse_roles_together(): void
    {
        $this->assertTrue($this->purchasingWarehouseManager->hasRole('purchasing_manager'));
        $this->assertTrue($this->purchasingWarehouseManager->hasRole('warehouse_keeper'));

        $this->actingAs($this->purchasingWarehouseManager);

        $this->assertTrue(ApprovalDashboard::canAccess());
    }

    public function test_dual_role_user_can_approve_warehouse_and_purchasing_stages(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'warehouse_keeper_id' => $this->purchasingWarehouseManager->id,
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 1,
        ]);
        $this->service->initializeStages($request);

        $this->assertTrue($this->service->canActOnRequest($request, $this->purchasingWarehouseManager));

        $this->service->approve($request, $this->purchasingWarehouseManager);
        $this->service->approve($request->fresh(), $this->financialManager);

        $this->assertSame(3, $request->fresh()->current_stage);
        $this->assertTrue($this->service->canActOnRequest(
            $request->fresh(),
            $this->purchasingWarehouseManager
        ));
    }

    public function test_dual_role_user_receives_warehouse_submission_notifications(): void
    {
        Notification::fake();

        $this->service->submit([
            'client_name' => 'Purchasing warehouse test client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'warehouse_keeper_id' => $this->purchasingWarehouseManager->id,
        ], $this->engineer);

        Notification::assertSentToTimes(
            $this->purchasingWarehouseManager,
            SalesRequestSubmittedNotification::class,
            1
        );
    }
}
