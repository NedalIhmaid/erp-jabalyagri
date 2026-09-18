<?php

namespace Tests\Unit\Notifications;

use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesRequestSubmittedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_channels(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $notification = new SalesRequestSubmittedNotification($request);

        $this->assertEquals(['mail', 'database'], $notification->via($approver));
    }

    public function test_notification_mail_has_correct_subject(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create(['name' => 'Approver']);

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $notification = new SalesRequestSubmittedNotification($request);
        $mailMessage = $notification->toMail($approver);

        $this->assertInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class, $mailMessage);
    }

    public function test_notification_array_has_correct_data(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $notification = new SalesRequestSubmittedNotification($request);
        $data = $notification->toArray($approver);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('request_id', $data);
        $this->assertArrayHasKey('request_number', $data);
        $this->assertArrayHasKey('client_name', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('sales_request_submitted', $data['type']);
        $this->assertEquals($request->id, $data['request_id']);
        $this->assertEquals('REQ-001', $data['request_number']);
        $this->assertEquals('Client ABC', $data['client_name']);
    }

    public function test_notification_can_be_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $approver = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $approver->notify(new SalesRequestSubmittedNotification($request));

        Notification::assertSentTo($approver, SalesRequestSubmittedNotification::class);
    }
}
