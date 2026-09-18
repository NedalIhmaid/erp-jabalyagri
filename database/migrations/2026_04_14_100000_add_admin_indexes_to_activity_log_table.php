<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->index('created_at', 'activity_log_created_at_index');
                $table->index(['subject_type', 'subject_id'], 'activity_log_subject_lookup_index');
                $table->index(['causer_type', 'causer_id'], 'activity_log_causer_lookup_index');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->dropIndex('activity_log_created_at_index');
                $table->dropIndex('activity_log_subject_lookup_index');
                $table->dropIndex('activity_log_causer_lookup_index');
            });
    }
};
