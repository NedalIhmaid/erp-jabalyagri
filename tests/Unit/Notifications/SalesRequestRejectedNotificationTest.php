<?php

namespace Tests\Unit\Notifications;

use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesRequestRejectedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
    {
        $user = User::factory()->create();
        $rejector = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 2,
            'status' => 'cancelled',
            'rejection_reason' => 'Budget exceeded',
            'rejected_by' => $rejector->id,
        ]);

        $notification = new SalesRequestRejectedNotification($request, 'Budget exceeded', 'Manager');

        $this->assertEquals(['mail', 'database'], $notification->via($user));
    }

    public function test_notification_array_has_correct_data(): void
    {
        $user = User::factory()->create();
        $rejector = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 2,
            'status' => 'cancelled',
            'rejection_reason' => 'Budget exceeded',
            'rejected_by' => $rejector->id,
        ]);

        $notification = new SalesRequestRejectedNotification($request, 'Budget exceeded', 'Manager');
        $data = $notification->toArray($user);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('request_number', $data);
        $this->assertArrayHasKey('reason', $data);
        $this->assertArrayHasKey('rejected_by', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('sales_request_rejected', $data['type']);
        $this->assertEquals($request->id, $data['request_id']);
        $this->assertEquals('REQ-001', $data['request_number']);
        $this->assertEquals('Budget exceeded', $data['reason']);
        $this->assertEquals('Manager', $data['rejected_by']);
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
            'status' => 'cancelled',
        ]);

        $user->notify(new SalesRequestRejectedNotification($request, 'Budget issue', 'Manager'));

        Notification::assertSentTo($user, SalesRequestRejectedNotification::class);
    }
}
