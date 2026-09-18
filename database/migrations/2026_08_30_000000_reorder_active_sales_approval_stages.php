<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reorderActiveRequests([
            1 => 'warehouse_keeper',
            2 => 'financial_manager',
            3 => 'purchasing_manager',
            4 => 'sales_manager',
        ]);
    }

    public function down(): void
    {
        $this->reorderActiveRequests([
            1 => 'warehouse_keeper',
            2 => 'sales_manager',
            3 => 'purchasing_manager',
            4 => 'financial_manager',
        ]);
    }

    /**
     * Preserve the warehouse decision, then restart unfinished downstream
     * approvals at stage 2 so no role in the new chain is skipped.
     *
     * @param  array<int, string>  $roleMap
     */
    private function reorderActiveRequests(array $roleMap): void
    {
        if (! Schema::hasTable('sales_approval_requests') || ! Schema::hasTable('approval_stages')) {
            return;
        }

        $requests = DB::table('sales_approval_requests')
            ->whereIn('status', ['pending', 'in_progress'])
            ->get(['id', 'current_stage']);

        if ($requests->isEmpty()) {
            return;
        }

        $approverIds = $this->firstActiveApproverIds(array_values($roleMap));
        $now = now();

        DB::transaction(function () use ($requests, $roleMap, $approverIds, $now): void {
            foreach ($requests as $request) {
                $stageOneExists = DB::table('approval_stages')
                    ->where('sales_approval_request_id', $request->id)
                    ->where('stage_number', 1)
                    ->exists();

                if (! $stageOneExists) {
                    DB::table('approval_stages')->insert([
                        'sales_approval_request_id' => $request->id,
                        'stage_number' => 1,
                        'role' => $roleMap[1],
                        'approver_id' => $approverIds[$roleMap[1]] ?? null,
                        'action' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('approval_stages')
                    ->where('sales_approval_request_id', $request->id)
                    ->where('stage_number', '>=', 2)
                    ->delete();

                foreach (array_slice($roleMap, 1, null, true) as $stageNumber => $role) {
                    DB::table('approval_stages')->insert([
                        'sales_approval_request_id' => $request->id,
                        'stage_number' => $stageNumber,
                        'role' => $role,
                        'approver_id' => $approverIds[$role] ?? null,
                        'action' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if ((int) $request->current_stage > 1) {
                    DB::table('sales_approval_requests')
                        ->where('id', $request->id)
                        ->update([
                            'current_stage' => 2,
                            'updated_at' => $now,
                        ]);
                }
            }
        });
    }

    /** @return array<string, int> */
    private function firstActiveApproverIds(array $roles): array
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return [];
        }

        return DB::table('roles')
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->whereIn('roles.name', $roles)
            ->where('users.is_active', true)
            ->orderBy('users.id')
            ->get(['roles.name as role', 'users.id'])
            ->unique('role')
            ->pluck('id', 'role')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
};
