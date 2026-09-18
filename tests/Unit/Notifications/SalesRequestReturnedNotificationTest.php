<?php

namespace Tests\Unit\Notifications;

use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestReturnedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesRequestReturnedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
    {
        $user = User::factory()->create();
        $returner = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'returned',
            'returned_by' => $returner->id,
        ]);

        $notification = new SalesRequestReturnedNotification($request, 'Need more details', 'Warehouse');

        $this->assertEquals(['mail', 'database'], $notification->via($user));
    }

    public function test_notification_array_has_correct_data(): void
    {
        $user = User::factory()->create();
        $returner = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'returned',
            'returned_by' => $returner->id,
        ]);

        $notification = new SalesRequestReturnedNotification($request, 'Need more details', 'Warehouse');
        $data = $notification->toArray($user);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('request_number', $data);
        $this->assertArrayHasKey('reason', $data);
        $this->assertArrayHasKey('returned_by', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('sales_request_returned', $data['type']);
        $this->assertEquals($request->id, $data['request_id']);
        $this->assertEquals('REQ-001', $data['request_number']);
        $this->assertEquals('Need more details', $data['reason']);
        $this->assertEquals('Warehouse', $data['returned_by']);
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
            'current_stage' => 1,
            'status' => 'returned',
        ]);

        $user->notify(new SalesRequestReturnedNotification($request, 'Missing information', 'Warehouse'));

        Notification::assertSentTo($user, SalesRequestReturnedNotification::class);
    }
}
