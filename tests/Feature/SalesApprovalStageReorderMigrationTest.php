<?php

namespace Tests\Feature;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApprovalStageReorderMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_requests_restart_after_warehouse_while_closed_history_is_preserved(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $engineer = User::role('engineer')->firstOrFail();
        $warehouse = User::role('warehouse_keeper')->firstOrFail();
        $salesManager = User::role('sales_manager')->firstOrFail();

        $activeRequest = SalesApprovalRequest::factory()->create([
            'user_id' => $engineer->id,
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 3,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'approver_id' => $warehouse->id,
            'action' => ApprovalAction::Approved,
            'acted_at' => now(),
        ]);
        ApprovalStage::create([
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 2,
            'role' => 'sales_manager',
            'approver_id' => $salesManager->id,
            'action' => ApprovalAction::Approved,
            'acted_at' => now(),
        ]);

        $closedRequest = SalesApprovalRequest::factory()->create([
            'user_id' => $engineer->id,
            'status' => SalesRequestStatus::Approved,
            'current_stage' => 4,
        ]);
        ApprovalStage::create([
            'sales_approval_request_id' => $closedRequest->id,
            'stage_number' => 2,
            'role' => 'sales_manager',
            'approver_id' => $salesManager->id,
            'action' => ApprovalAction::Approved,
            'acted_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_08_30_000000_reorder_active_sales_approval_stages.php');
        $migration->up();

        $this->assertSame(2, $activeRequest->fresh()->current_stage);
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 1,
            'role' => 'warehouse_keeper',
            'action' => ApprovalAction::Approved->value,
        ]);
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 2,
            'role' => 'financial_manager',
            'action' => ApprovalAction::Pending->value,
        ]);
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 3,
            'role' => 'purchasing_manager',
            'action' => ApprovalAction::Pending->value,
        ]);
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $activeRequest->id,
            'stage_number' => 4,
            'role' => 'sales_manager',
            'action' => ApprovalAction::Pending->value,
        ]);
        $this->assertDatabaseHas('approval_stages', [
            'sales_approval_request_id' => $closedRequest->id,
            'stage_number' => 2,
            'role' => 'sales_manager',
            'action' => ApprovalAction::Approved->value,
        ]);
    }
}
