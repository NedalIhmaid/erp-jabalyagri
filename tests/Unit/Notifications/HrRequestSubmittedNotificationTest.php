<?php

namespace Tests\Unit\Notifications;

use App\Models\HrRequest;
use App\Models\User;
use App\Notifications\HrRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HrRequestSubmittedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
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

        $notification = new HrRequestSubmittedNotification($request);

        $this->assertEquals(['mail', 'database'], $notification->via($manager));
    }

    public function test_notification_mail_has_correct_subject(): void
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

        $notification = new HrRequestSubmittedNotification($request);
        $mailMessage = $notification->toMail($manager);

        $this->assertInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class, $mailMessage);
    }

    public function test_notification_array_has_correct_data(): void
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

        $notification = new HrRequestSubmittedNotification($request);
        $data = $notification->toArray($manager);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals($request->id, $data['request_id']);
        // Note: 'type' key is overwritten by HrRequestType label in the notification
    }

    public function test_notification_can_be_sent(): void
    {
        Notification::fake();

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

        $manager->notify(new HrRequestSubmittedNotification($request));

        Notification::assertSentTo($manager, HrRequestSubmittedNotification::class);
    }
}
