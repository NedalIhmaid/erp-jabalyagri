<?php

namespace Tests\Feature;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\User;
use App\Services\HrRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected HrRequestService $service;
    protected User $engineer;
    protected User $salesManager;
    protected User $generalManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->service = app(HrRequestService::class);

        $this->engineer = User::role('engineer')->first();
        $this->salesManager = User::role('sales_manager')->first();
        $this->generalManager = User::role('general_manager')->first();

        // Ensure engineer's manager is the sales manager
        $this->engineer->update(['manager_id' => $this->salesManager->id]);
    }

    private function createHrRequest(): HrRequest
    {
        return HrRequest::create([
            'user_id' => $this->engineer->id,
            'type' => HrRequestType::AnnualLeave,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'duration_days' => 3,
            'status' => HrRequestStatus::Pending,
            'manager_id' => $this->engineer->manager_id,
        ]);
    }

    public function test_hr_request_submitted_with_pending_status(): void
    {
        $request = $this->createHrRequest();

        $this->assertEquals(HrRequestStatus::Pending, $request->status);
        $this->assertEquals($this->engineer->id, $request->user_id);
    }

    public function test_direct_manager_can_approve_pending_request(): void
    {
        $request = $this->createHrRequest();

        $this->assertTrue($this->service->canApproveAsManager($request, $this->salesManager));
    }

    public function test_gm_cannot_approve_pending_request_before_manager(): void
    {
        $request = $this->createHrRequest();

        $this->assertFalse($this->service->canApproveAsGM($request, $this->generalManager));
    }

    public function test_manager_approval_sets_status_to_manager_approved(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager, 'Approved by SM');
        $request->refresh();

        $this->assertEquals(HrRequestStatus::ManagerApproved, $request->status);
        $this->assertEquals('Approved by SM', $request->manager_comments);
        $this->assertNotNull($request->manager_action_at);
    }

    public function test_gm_can_approve_manager_approved_request(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager);
        $request->refresh();

        $this->assertTrue($this->service->canApproveAsGM($request, $this->generalManager));
    }

    public function test_gm_final_approval_sets_status_to_approved(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager);
        $this->service->approveAsGM($request->fresh(), $this->generalManager, 'Final approved');
        $request->refresh();

        $this->assertEquals(HrRequestStatus::Approved, $request->status);
        $this->assertEquals('Final approved', $request->gm_comments);
        $this->assertNotNull($request->gm_action_at);
    }

    public function test_manager_rejection_sets_status_to_rejected(): void
    {
        $request = $this->createHrRequest();
        $this->service->rejectAsManager($request, $this->salesManager, 'Busy period');
        $request->refresh();

        $this->assertEquals(HrRequestStatus::Rejected, $request->status);
        $this->assertEquals('Busy period', $request->manager_comments);
    }

    public function test_gm_rejection_sets_status_to_rejected(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager);
        $this->service->rejectAsGM($request->fresh(), $this->generalManager, 'Quota exceeded');
        $request->refresh();

        $this->assertEquals(HrRequestStatus::Rejected, $request->status);
        $this->assertEquals('Quota exceeded', $request->gm_comments);
    }

    public function test_wrong_manager_cannot_approve_request(): void
    {
        $otherManager = User::role('purchasing_manager')->first();
        $request = $this->createHrRequest();

        $this->assertFalse($this->service->canApproveAsManager($request, $otherManager));
    }
}
