<?php

namespace Tests\Unit\Models;

use App\Models\HrRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_request_can_be_created(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
            'reason' => 'Vacation',
        ]);

        $this->assertDatabaseHas('hr_requests', [
            'user_id' => $user->id,
            'type' => 'annual_leave',
            'status' => 'pending',
        ]);
    }

    public function test_hr_request_has_correct_casts(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'duration_days' => 5,
        ]);

        $this->assertInstanceOf('App\Enums\HrRequestType', $request->type);
        $this->assertInstanceOf('App\Enums\HrRequestStatus', $request->status);
        $this->assertInstanceOf('Carbon\Carbon', $request->start_date);
        $this->assertInstanceOf('Carbon\Carbon', $request->end_date);
    }

    public function test_hr_request_user_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Employee']);
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->assertEquals('Employee', $request->user->name);
    }

    public function test_hr_request_manager_relationship(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create(['name' => 'Manager']);

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $this->assertEquals('Manager', $request->manager->name);
    }

    public function test_hr_request_fillable_attributes(): void
    {
        $request = new HrRequest();
        $expected = [
            'user_id', 'type', 'start_date', 'end_date', 'start_time', 'end_time',
            'duration_days', 'reason', 'attachment', 'status', 'manager_id',
            'manager_action_at', 'manager_comments', 'gm_action_at', 'gm_comments',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $request->getFillable());
        }
    }

    public function test_hr_request_duration_days_is_decimal(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5.5,
        ]);

        $this->assertEquals(5.5, (float) $request->duration_days);
    }

    public function test_hr_request_can_be_updated(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        // Update without triggering observer status change to 'approved'
        $request->update([
            'manager_action_at' => now(),
            'manager_comments' => 'Approved by manager',
        ]);

        $this->assertNotNull($request->manager_action_at);
        $this->assertEquals('Approved by manager', $request->manager_comments);
    }

    public function test_hr_request_supports_optional_fields(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'manager_id' => $manager->id,
            'type' => 'annual_leave',
            'status' => 'pending',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
            'reason' => null,
            'attachment' => null,
        ]);

        $this->assertNull($request->reason);
        $this->assertNull($request->attachment);
        $this->assertNull($request->start_time);
        $this->assertNull($request->end_time);
    }
}
