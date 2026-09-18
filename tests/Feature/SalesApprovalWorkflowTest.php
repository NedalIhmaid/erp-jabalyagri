<?php

namespace Tests\Feature;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $service;

    protected User $engineer;

    protected User $warehouseKeeper;

    protected User $salesManager;

    protected User $purchasingManager;

    protected User $financialManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->service = app(SalesApprovalService::class);

        $this->engineer = User::role('engineer')->first();
        $this->warehouseKeeper = User::role('warehouse_keeper')->first();
        $this->salesManager = User::role('sales_manager')->first();
        $this->purchasingManager = User::role('purchasing_manager')->first();
        $this->financialManager = User::role('financial_manager')->first();
    }

    private function createRequest(): SalesApprovalRequest
    {
        $request = SalesApprovalRequest::create([
            'request_number' => 'SAR-TEST-0001',
            'user_id' => $this->engineer->id,
            'client_name' => 'Test Client',
            'payment_method' => 'on_account',
            'total_amount' => 100.00,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        $this->service->initializeStages($request);

        return $request->fresh();
    }

    public function test_engineer_creates_request_with_in_progress_status(): void
    {
        $request = $this->createRequest();

        $this->assertEquals(SalesRequestStatus::InProgress, $request->status);
        $this->assertEquals(1, $request->current_stage);
        $this->assertEquals(3, $request->approvalStages()->count());
    }

    public function test_warehouse_keeper_can_act_on_stage_1_request(): void
    {
        $request = $this->createRequest();

        $this->assertTrue($this->service->canActOnRequest($request, $this->warehouseKeeper));
    }

    public function test_warehouse_keeper_cannot_act_on_stage_2_request(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper);
        $request->refresh();

        $this->assertFalse($this->service->canActOnRequest($request, $this->warehouseKeeper));
    }

    public function test_stage_1_approval_advances_to_stage_2(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper, 'Stock verified');
        $request->refresh();

        $this->assertEquals(2, $request->current_stage);
        $this->assertEquals(SalesRequestStatus::InProgress, $request->status);

        $stage1 = $request->approvalStages()->where('stage_number', 1)->first();
        $this->assertEquals('approved', $stage1->action->value);
        $this->assertEquals('Stock verified', $stage1->comments);
    }

    public function test_full_3_stage_approval_approves_request(): void
    {
        $request = $this->createRequest();

        $this->service->approve($request, $this->warehouseKeeper);
        $this->assertTrue($this->service->canActOnRequest($request->fresh(), $this->financialManager));
        $this->assertFalse($this->service->canActOnRequest($request->fresh(), $this->salesManager));

        $this->service->approve($request->fresh(), $this->financialManager);
        $this->assertTrue($this->service->canActOnRequest($request->fresh(), $this->purchasingManager));

        $this->service->approve($request->fresh(), $this->purchasingManager);
        $request->refresh();

        $this->assertEquals(SalesRequestStatus::Approved, $request->status);
        $this->assertFalse($this->service->canActOnRequest($request, $this->salesManager));
    }

    public function test_rejection_cancels_the_request(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Out of stock');
        $request->refresh();

        $this->assertEquals(SalesRequestStatus::Cancelled, $request->status);
        $this->assertEquals('Out of stock', $request->rejection_reason);
        $this->assertEquals($this->warehouseKeeper->id, $request->rejected_by);
    }

    public function test_warehouse_keeper_can_return_request_to_engineer(): void
    {
        $request = $this->createRequest();

        $this->assertTrue($this->service->canReturnRequest($request, $this->warehouseKeeper));

        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Missing info');
        $request->refresh();

        $this->assertEquals(SalesRequestStatus::Returned, $request->status);
        $this->assertEquals($this->warehouseKeeper->id, $request->returned_by);
    }

    public function test_sales_manager_cannot_return_request(): void
    {
        $request = $this->createRequest();

        $this->assertFalse($this->service->canReturnRequest($request, $this->salesManager));
    }

    public function test_engineer_cannot_approve_request(): void
    {
        $request = $this->createRequest();

        $this->assertFalse($this->service->canActOnRequest($request, $this->engineer));
    }

    public function test_reset_stages_restores_request_to_stage_1(): void
    {
        $request = $this->createRequest();
        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Fix needed');
        $this->service->resetStages($request);
        $request->refresh();

        $this->assertEquals(1, $request->current_stage);
        $this->assertEquals(SalesRequestStatus::InProgress, $request->status);
        $this->assertEquals(3, $request->approvalStages()->count());
    }

    public function test_cancelled_request_cannot_be_acted_on(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Denied');
        $request->refresh();

        $this->assertFalse($this->service->canActOnRequest($request, $this->salesManager));
    }
}
