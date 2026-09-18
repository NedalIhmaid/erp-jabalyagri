<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_approval_requests') || ! Schema::hasTable('approval_stages')) {
            return;
        }

        DB::transaction(function (): void {
            // Requests that reached the former sales-manager stage have already
            // passed the three approvals that now make up the complete workflow.
            DB::table('sales_approval_requests')
                ->where('status', 'in_progress')
                ->where('current_stage', 4)
                ->update([
                    'status' => 'approved',
                    'current_stage' => 3,
                    'updated_at' => now(),
                ]);

            DB::table('sales_approval_requests')
                ->where('current_stage', '>', 3)
                ->update(['current_stage' => 3]);

            DB::table('approval_stages')
                ->where('stage_number', 4)
                ->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_approval_requests') || ! Schema::hasTable('approval_stages')) {
            return;
        }

        DB::table('sales_approval_requests')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                $now = now();
                $rows = $requests->map(fn ($request): array => [
                    'sales_approval_request_id' => $request->id,
                    'stage_number' => 4,
                    'role' => 'sales_manager',
                    'approver_id' => null,
                    'action' => $request->status === 'approved' ? 'approved' : 'pending',
                    'comments' => null,
                    'acted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('approval_stages')->insertOrIgnore($rows);
            });
    }
};
