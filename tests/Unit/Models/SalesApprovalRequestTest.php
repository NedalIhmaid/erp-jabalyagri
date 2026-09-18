<?php

namespace Tests\Unit\Models;

use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApprovalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_approval_request_can_be_created(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'client_phone' => '+962791234567',
            'client_address' => 'Amman, Jordan',
            'payment_method' => 'on_account',
            'engineer_notes' => 'Test notes',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('sales_approval_requests', [
            'request_number' => 'REQ-001',
            'client_name' => 'Client ABC',
        ]);
    }

    public function test_sales_approval_request_has_correct_casts(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'accounting_note',
            'total_amount' => 1500.50,
            'current_stage' => 1,
            'status' => 'in_progress',
        ]);

        $this->assertEquals('accounting_note', $request->payment_method);
        $this->assertInstanceOf('App\Enums\SalesRequestStatus', $request->status);
        $this->assertEquals(1500.50, (float) $request->total_amount);
    }

    public function test_sales_approval_request_user_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Engineer']);

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $this->assertEquals('Engineer', $request->user->name);
    }

    public function test_sales_approval_request_items_relationship(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $this->assertCount(0, $request->items);
    }

    public function test_sales_approval_request_approval_stages_relationship(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => 'pending',
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => 'pending',
        ]);

        $stages = $request->approvalStages;
        $this->assertCount(2, $stages);
        $this->assertEquals(1, $stages->first()->stage_number);
        $this->assertEquals(2, $stages->last()->stage_number);
    }

    public function test_sales_approval_request_fillable_attributes(): void
    {
        $request = new SalesApprovalRequest;
        $expected = [
            'request_number', 'user_id', 'client_name', 'client_phone',
            'client_address', 'payment_method', 'engineer_notes', 'total_amount',
            'current_stage', 'status', 'rejection_reason', 'rejected_by', 'returned_by',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $request->getFillable());
        }
    }

    public function test_sales_approval_request_rejected_by_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Rejector']);
        $engineer = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $engineer->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'cancelled',
            'rejected_by' => $user->id,
        ]);

        $this->assertEquals('Rejector', $request->rejectedBy->name);
    }

    public function test_sales_approval_request_returned_by_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Returner']);
        $engineer = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $engineer->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'returned',
            'returned_by' => $user->id,
        ]);

        $this->assertEquals('Returner', $request->returnedBy->name);
    }

    public function test_sales_approval_request_can_be_updated(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $request->update([
            'current_stage' => 2,
            'status' => 'in_progress',
            'engineer_notes' => 'Updated notes',
        ]);

        $fresh = $request->fresh();
        $this->assertEquals(2, $fresh->current_stage);
        $this->assertEquals('in_progress', $fresh->status->value);
        $this->assertEquals('Updated notes', $fresh->engineer_notes);
    }

    public function test_sales_approval_request_optional_fields(): void
    {
        $user = User::factory()->create();

        $request = SalesApprovalRequest::create([
            'request_number' => 'REQ-001',
            'user_id' => $user->id,
            'client_name' => 'Client ABC',
            'payment_method' => 'on_account',
            'total_amount' => 1000.00,
            'current_stage' => 1,
            'status' => 'pending',
        ]);

        $this->assertNull($request->client_phone);
        $this->assertNull($request->client_address);
        $this->assertNull($request->engineer_notes);
        $this->assertNull($request->rejection_reason);
        $this->assertNull($request->rejected_by);
        $this->assertNull($request->returned_by);
    }
}
