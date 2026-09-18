<?php

namespace Tests\Unit\Notifications;

use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestStageAdvancedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesRequestStageAdvancedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 2,
            'status' => 'in_progress',
        ]);

        $notification = new SalesRequestStageAdvancedNotification($request, 2);

        $this->assertEquals(['mail', 'database'], $notification->via($user));
    }

    public function test_notification_array_has_correct_data(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 2,
            'status' => 'in_progress',
        ]);

        $notification = new SalesRequestStageAdvancedNotification($request, 2);
        $data = $notification->toArray($user);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('stage_number', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('sales_request_stage_advanced', $data['type']);
        $this->assertEquals($request->id, $data['request_id']);
        $this->assertEquals(2, $data['stage_number']);
    }

    public function test_notification_can_be_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 2,
            'status' => 'in_progress',
        ]);

        $user->notify(new SalesRequestStageAdvancedNotification($request, 2));

        Notification::assertSentTo($user, SalesRequestStageAdvancedNotification::class);
    }
}
