<?php

namespace Tests\Unit\Services;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\User;
use App\Services\HrRequestService;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HrRequestService $hrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrService = app(HrRequestService::class);
    }

    public function test_submit_hr_request(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = $this->hrService->submit([
            'type' => HrRequestType::AnnualLeave->value,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
            'reason' => 'Vacation',
        ], $user);

        $this->assertDatabaseHas('hr_requests', [
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
        ]);
        $this->assertEquals($user->id, $request->user_id);
        $this->assertEquals(HrRequestStatus::Pending, $request->status);
    }

    public function test_submit_hr_request_throws_exception_on_insufficient_balance(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        // Exhaust leave balance
        $leaveService = app(LeaveService::class);
        $balance = $leaveService->getOrCreateBalance($user);
        $balance->update(['annual_used' => 14]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient leave balance');

        $this->hrService->submit([
            'type' => HrRequestType::AnnualLeave->value,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
            'reason' => 'Vacation',
        ], $user);
    }

    public function test_approve_as_manager(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        // Disable observers to avoid role lookup
        HrRequest::unsetEventDispatcher();

        $result = $this->hrService->approveAsManager($request, $manager, 'Approved');

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(HrRequestStatus::ManagerApproved, $request->status);
        $this->assertNotNull($request->manager_action_at);
        $this->assertEquals('Approved', $request->manager_comments);
    }

    public function test_reject_as_manager(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $result = $this->hrService->rejectAsManager($request, $manager, 'Insufficient notice');

        $this->assertTrue($result);
        $request->refresh();
        $this->assertEquals(HrRequestStatus::Rejected, $request->status);
        $this->assertNotNull($request->manager_action_at);
        $this->assertEquals('Insufficient notice', $request->manager_comments);
    }

    public function test_can_approve_as_manager(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->assertTrue($this->hrService->canApproveAsManager($request, $manager));
    }

    public function test_cannot_approve_as_manager_if_not_manager(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->assertFalse($this->hrService->canApproveAsManager($request, $otherUser));
    }

    public function test_cannot_approve_as_manager_if_already_approved(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::ManagerApproved,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->assertFalse($this->hrService->canApproveAsManager($request, $manager));
    }

    public function test_manager_approval_deducts_no_leave(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);
        $manager = User::factory()->create();

        $user->update(['manager_id' => $manager->id]);

        // Disable observers to avoid role lookup
        HrRequest::unsetEventDispatcher();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->hrService->approveAsManager($request, $manager);

        $leaveService = app(LeaveService::class);
        $balance = $leaveService->getOrCreateBalance($user);

        // Manager approval doesn't deduct leave
        $this->assertEquals(0, (float) $balance->annual_used);
    }
}
