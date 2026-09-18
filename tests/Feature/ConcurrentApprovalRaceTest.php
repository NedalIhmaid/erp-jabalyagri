<?php

namespace Tests\Feature;

use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrentApprovalRaceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $service;

    protected User $engineer;

    protected User $warehouseKeeperA;

    protected User $warehouseKeeperB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->service = app(SalesApprovalService::class);
        $this->engineer = User::role('engineer')->firstOrFail();

        $this->warehouseKeeperA = User::role('warehouse_keeper')->firstOrFail();

        $this->warehouseKeeperB = User::factory()->create([
            'name' => 'Second Warehouse Keeper',
            'email' => 'warehouse2@aljabali.com',
            'is_active' => true,
            'manager_id' => $this->warehouseKeeperA->manager_id,
        ]);
        $this->warehouseKeeperB->assignRole('warehouse_keeper');
    }

    public function test_two_warehouse_keepers_approving_stage_one_results_in_single_advance(): void
    {
        $request = $this->submitRequest();

        // Both keepers hold stale local references to stage 1.
        $requestForA = SalesApprovalRequest::find($request->id);
        $requestForB = SalesApprovalRequest::find($request->id);

        $this->service->approve($requestForA, $this->warehouseKeeperA, 'from A');
        $this->service->approve($requestForB, $this->warehouseKeeperB, 'from B');

        $final = $request->fresh();

        $this->assertSame(2, $final->current_stage);
        $this->assertSame(SalesRequestStatus::InProgress, $final->status);

        // Only one ApprovalStage row per stage number.
        $stages = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->get()
            ->groupBy('stage_number')
            ->map->count();

        foreach ($stages as $count) {
            $this->assertSame(1, $count, 'Stage records must remain unique per stage_number.');
        }
    }

    public function test_approve_on_stage_after_it_advanced_is_no_op_from_wrong_role(): void
    {
        $request = $this->submitRequest();
        $this->service->approve($request, $this->warehouseKeeperA);
        $request->refresh();

        // Warehouse keeper B can no longer act — stage is now 2 (sales_manager).
        $this->assertFalse($this->service->canActOnRequest($request, $this->warehouseKeeperB));
    }

    public function test_cancelled_request_cannot_be_acted_on(): void
    {
        $request = $this->submitRequest();
        $this->service->reject($request, $this->warehouseKeeperA, 'bad request');
        $request->refresh();

        $this->assertSame(SalesRequestStatus::Cancelled, $request->status);
        $this->assertFalse($this->service->canActOnRequest($request, $this->warehouseKeeperA));
        $this->assertFalse($this->service->canActOnRequest($request, $this->warehouseKeeperB));
    }

    protected function submitRequest(): SalesApprovalRequest
    {
        return $this->service->submit([
            'client_name' => 'Race Client',
            'payment_method' => 'on_account',
            'total_amount' => 250,
        ], $this->engineer);
    }
}
