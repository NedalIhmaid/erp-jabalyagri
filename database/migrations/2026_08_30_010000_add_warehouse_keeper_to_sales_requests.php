<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->foreignId('warehouse_keeper_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $fallbackWarehouseKeeperId = DB::table('roles')
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->where('roles.name', 'warehouse_keeper')
            ->where('users.is_active', true)
            ->orderBy('users.id')
            ->value('users.id');

        DB::table('sales_approval_requests')
            ->leftJoin('approval_stages', function ($join) {
                $join->on('approval_stages.sales_approval_request_id', '=', 'sales_approval_requests.id')
                    ->where('approval_stages.stage_number', 1);
            })
            ->select([
                'sales_approval_requests.id',
                'approval_stages.approver_id',
            ])
            ->orderBy('sales_approval_requests.id')
            ->get()
            ->each(function ($request) use ($fallbackWarehouseKeeperId): void {
                DB::table('sales_approval_requests')
                    ->where('id', $request->id)
                    ->update([
                        'warehouse_keeper_id' => $request->approver_id ?: $fallbackWarehouseKeeperId,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_keeper_id');
        });
    }
};
