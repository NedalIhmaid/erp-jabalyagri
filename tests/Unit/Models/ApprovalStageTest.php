<?php

namespace Tests\Unit\Models;

use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_stage_can_be_created(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => 'pending',
        ]);

        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => 'pending',
        ]);
    }

    public function test_approval_stage_has_correct_casts(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => 'approved',
            'acted_at' => now(),
        ]);

        $this->assertInstanceOf('App\Enums\ApprovalAction', $stage->action);
        $this->assertInstanceOf('Carbon\Carbon', $stage->acted_at);
    }

    public function test_approval_stage_sales_approval_relationship(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => 'pending',
        ]);

        $this->assertEquals($request->id, $stage->salesApprovalRequest->id);
    }

    public function test_approval_stage_approver_relationship(): void
    {
        $approver = User::factory()->create(['name' => 'Approver']);
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'approver_id' => $approver->id,
            'action' => 'approved',
        ]);

        $this->assertEquals('Approver', $stage->approver->name);
    }

    public function test_approval_stage_fillable_attributes(): void
    {
        $stage = new ApprovalStage;
        $expected = [
            'sales_approval_request_id', 'stage_number', 'role', 'approver_id',
            'action', 'comments', 'acted_at',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $stage->getFillable());
        }
    }

    public function test_approval_stage_without_approver(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'approver_id' => null,
            'action' => 'pending',
        ]);

        $this->assertNull($stage->approver_id);
        $this->assertNull($stage->approver);
    }

    public function test_approval_stage_can_be_updated(): void
    {
        $approver = User::factory()->create();
        $request = SalesApprovalRequest::factory()->create();

        $stage = ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'approver_id' => null,
            'action' => 'pending',
        ]);

        $stage->update([
            'approver_id' => $approver->id,
            'action' => 'approved',
            'comments' => 'Approved',
            'acted_at' => now(),
        ]);

        $fresh = $stage->fresh();
        $this->assertEquals('approved', $fresh->action->value);
        $this->assertEquals('Approved', $fresh->comments);
        $this->assertNotNull($fresh->acted_at);
    }

    public function test_approval_stage_orders_by_stage_number(): void
    {
        $request = SalesApprovalRequest::factory()->create();

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

        $stages = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->orderBy('stage_number')
            ->get();

        $this->assertEquals(1, $stages->first()->stage_number);
        $this->assertEquals(2, $stages->last()->stage_number);
    }
}
