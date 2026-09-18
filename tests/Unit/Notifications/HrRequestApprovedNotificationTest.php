<?php

namespace Tests\Unit\Notifications;

use App\Models\HrRequest;
use App\Models\User;
use App\Notifications\HrRequestApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HrRequestApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
    {
        $user = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'type' => 'annual_leave',
            'status' => 'approved',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $notification = new HrRequestApprovedNotification($request, 'Manager Name');

        $this->assertEquals(['mail', 'database'], $notification->via($user));
    }

    public function test_notification_array_has_correct_data(): void
    {
        $user = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'type' => 'annual_leave',
            'status' => 'approved',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $notification = new HrRequestApprovedNotification($request, 'Manager Name');
        $data = $notification->toArray($user);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('approved_by', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals($request->id, $data['request_id']);
        $this->assertEquals('Manager Name', $data['approved_by']);
        // Note: 'type' key is overwritten by HrRequestType label in the notification
    }

    public function test_notification_can_be_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $request = HrRequest::create([
            'user_id' => $user->id,
            'type' => 'annual_leave',
            'status' => 'approved',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
            'duration_days' => 5,
        ]);

        $user->notify(new HrRequestApprovedNotification($request, 'Manager Name'));

        Notification::assertSentTo($user, HrRequestApprovedNotification::class);
    }
}
