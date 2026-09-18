<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SalesRequestSubmittedNotification;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WarehouseKeeperSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $service;

    protected User $engineer;

    protected User $defaultWarehouseKeeper;

    protected User $selectedWarehouseKeeper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->service = app(SalesApprovalService::class);
        $this->engineer = User::role('engineer')->firstOrFail();
        $this->defaultWarehouseKeeper = User::role('warehouse_keeper')->firstOrFail();
        $this->selectedWarehouseKeeper = User::role('purchasing_manager')->firstOrFail();
        $this->selectedWarehouseKeeper->assignRole('warehouse_keeper');
    }

    public function test_selected_warehouse_keeper_is_saved_assigned_and_notified(): void
    {
        Notification::fake();

        $request = $this->service->submit([
            'client_name' => 'Warehouse selection client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'warehouse_keeper_id' => $this->selectedWarehouseKeeper->id,
        ], $this->engineer);

        $this->assertSame($this->selectedWarehouseKeeper->id, $request->warehouse_keeper_id);
        $this->assertTrue($request->warehouseKeeper->is($this->selectedWarehouseKeeper));
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'approver_id' => $this->selectedWarehouseKeeper->id,
        ]);
        Notification::assertSentToTimes(
            $this->selectedWarehouseKeeper,
            SalesRequestSubmittedNotification::class,
            1
        );
        Notification::assertNotSentTo(
            $this->defaultWarehouseKeeper,
            SalesRequestSubmittedNotification::class
        );
    }

    public function test_only_selected_warehouse_keeper_can_act_or_return_at_stage_one(): void
    {
        $request = $this->service->submit([
            'client_name' => 'Assigned warehouse client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'warehouse_keeper_id' => $this->selectedWarehouseKeeper->id,
        ], $this->engineer);

        $this->assertTrue($this->service->canActOnRequest($request, $this->selectedWarehouseKeeper));
        $this->assertTrue($this->service->canReturnRequest($request, $this->selectedWarehouseKeeper));
        $this->assertFalse($this->service->canActOnRequest($request, $this->defaultWarehouseKeeper));
        $this->assertFalse($this->service->canReturnRequest($request, $this->defaultWarehouseKeeper));
    }

    public function test_inactive_or_non_warehouse_user_cannot_be_selected(): void
    {
        $salesManager = User::role('sales_manager')->firstOrFail();

        $this->expectException(ValidationException::class);

        $this->service->submit([
            'client_name' => 'Invalid warehouse client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'warehouse_keeper_id' => $salesManager->id,
        ], $this->engineer);
    }
}
