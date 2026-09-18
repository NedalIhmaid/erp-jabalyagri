<?php

namespace Tests\Unit\Services;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $salesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->salesService = app(SalesApprovalService::class);
    }

    public function test_initialize_stages(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $this->salesService->initializeStages($request);

        $this->assertEquals(3, ApprovalStage::where('sales_approval_request_id', $request->id)->count());

        $stages = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->orderBy('stage_number')
            ->get();

        $this->assertEquals('warehouse_keeper', $stages[0]->role);
        $this->assertEquals('financial_manager', $stages[1]->role);
        $this->assertEquals('purchasing_manager', $stages[2]->role);

        foreach ($stages as $stage) {
            $this->assertEquals(ApprovalAction::Pending, $stage->action);
        }
    }

    public function test_approve_stage_1_to_2(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::Pending,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => ApprovalAction::Pending,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $user = User::factory()->create();
        $user->assignRole('warehouse_keeper');

        $result = $this->salesService->approve($request, $user);

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(2, $request->current_stage);
        $this->assertEquals(SalesRequestStatus::InProgress, $request->status);

        $stage1 = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', 1)
            ->first();
        $this->assertEquals(ApprovalAction::Approved, $stage1->action);
        $this->assertEquals($user->id, $stage1->approver_id);
        $this->assertNotNull($stage1->acted_at);
    }

    public function test_approve_final_stage(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 3,
            'status' => SalesRequestStatus::InProgress,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 3,
            'role' => 'purchasing_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $user = User::factory()->create();
        $user->assignRole('purchasing_manager');

        $result = $this->salesService->approve($request, $user);

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(SalesRequestStatus::Approved, $request->status);

        $stage3 = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', 3)
            ->first();
        $this->assertEquals(ApprovalAction::Approved, $stage3->action);
    }

    public function test_reject_cancels_request(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 2,
            'status' => SalesRequestStatus::InProgress,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $user = User::factory()->create();
        $user->assignRole('financial_manager');

        $result = $this->salesService->reject($request, $user, 'Budget exceeded');

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(SalesRequestStatus::Cancelled, $request->status);
        $this->assertEquals('Budget exceeded', $request->rejection_reason);
        $this->assertEquals($user->id, $request->rejected_by);

        $stage = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', 2)
            ->first();
        $this->assertEquals(ApprovalAction::Rejected, $stage->action);
    }

    public function test_return_to_engineer(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => ApprovalAction::Pending,
        ]);

        $user = User::factory()->create();
        $user->assignRole('warehouse_keeper');

        $result = $this->salesService->returnToEngineer($request, $user, 'Need more details');

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(SalesRequestStatus::Returned, $request->status);
        $this->assertEquals($user->id, $request->returned_by);

        $stage = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', 1)
            ->first();
        $this->assertEquals(ApprovalAction::Returned, $stage->action);
        $this->assertEquals('Need more details', $stage->comments);
    }

    public function test_can_act_on_request(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        $user = User::factory()->create();

        // Without proper role, should return false
        $this->assertFalse($this->salesService->canActOnRequest($request, $user));
    }

    public function test_sales_manager_has_view_only_access_and_cannot_act(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 3,
            'status' => SalesRequestStatus::InProgress,
        ]);

        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        $this->assertFalse($this->salesService->canActOnRequest($request, $user));
        $this->assertFalse($this->salesService->approve($request, $user));
        $this->assertFalse($this->salesService->reject($request, $user, 'Not allowed'));
        $this->assertSame(SalesRequestStatus::InProgress, $request->fresh()->status);
    }

    public function test_cannot_act_on_cancelled_request(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::Cancelled,
        ]);

        $user = User::factory()->create();
        $user->assignRole('warehouse_keeper');

        $this->assertFalse($this->salesService->canActOnRequest($request, $user));
    }

    public function test_cannot_act_on_approved_request(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 3,
            'status' => SalesRequestStatus::Approved,
        ]);

        $user = User::factory()->create();

        $this->assertFalse($this->salesService->canActOnRequest($request, $user));
    }

    public function test_can_return_request(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        $user = User::factory()->create();

        // Without warehouse_keeper role, should return false
        $this->assertFalse($this->salesService->canReturnRequest($request, $user));
    }

    public function test_reset_stages(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 3,
            'status' => SalesRequestStatus::InProgress,
        ]);

        foreach ([
            1 => 'warehouse_keeper',
            2 => 'financial_manager',
            3 => 'purchasing_manager',
        ] as $stageNumber => $role) {
            ApprovalStage::create([
                'sales_approval_request_id' => $request->id,
                'stage_number' => $stageNumber,
                'role' => $role,
                'action' => $stageNumber <= 2 ? ApprovalAction::Approved : ApprovalAction::Pending,
            ]);
        }

        $this->salesService->resetStages($request);

        $request->refresh();
        $this->assertEquals(1, $request->current_stage);
        $this->assertEquals(SalesRequestStatus::InProgress, $request->status);
        $this->assertNull($request->rejection_reason);
        $this->assertNull($request->rejected_by);
        $this->assertNull($request->returned_by);

        $stages = ApprovalStage::where('sales_approval_request_id', $request->id)->get();
        $this->assertEquals(3, $stages->count());

        foreach ($stages as $stage) {
            $this->assertEquals(ApprovalAction::Pending, $stage->action);
        }
    }

    public function test_approve_with_comments(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'current_stage' => 1,
            'status' => SalesRequestStatus::Pending,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => ApprovalAction::Pending,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $user = User::factory()->create();
        $user->assignRole('warehouse_keeper');

        $result = $this->salesService->approve($request, $user, 'Looks good');

        $this->assertTrue($result);

        $stage = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', 1)
            ->first();
        $this->assertEquals('Looks good', $stage->comments);
    }

    public function test_create_stage_record_skips_if_exists(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $method = new \ReflectionMethod($this->salesService, 'createStageRecord');
        $method->invoke($this->salesService, $request->id, 2);

        $this->assertEquals(
            1,
            ApprovalStage::where('sales_approval_request_id', $request->id)
                ->where('stage_number', 2)
                ->count()
        );
    }
}
