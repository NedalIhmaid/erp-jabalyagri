<?php

namespace Tests\Feature;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveSalesManagerApprovalStageMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_four_requests_are_finalized_and_sales_manager_stages_are_removed(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 4,
        ]);

        ApprovalStage::create([
            'sales_approval_request_id' => $request->id,
            'stage_number' => 4,
            'role' => 'sales_manager',
            'action' => ApprovalAction::Pending,
        ]);

        $migration = require database_path('migrations/2026_09_03_000000_remove_sales_manager_approval_stage.php');
        $migration->up();

        $request->refresh();

        $this->assertSame(SalesRequestStatus::Approved, $request->status);
        $this->assertSame(3, $request->current_stage);
        $this->assertDatabaseMissing('approval_stages', [
            'sales_approval_request_id' => $request->id,
            'stage_number' => 4,
        ]);
    }
}
