<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->after('client_address');
            $table->string('project_type', 30)->nullable()->after('region');
            $table->string('project_size', 50)->nullable()->after('project_type');

            $table->index('region');
            $table->index('project_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropIndex(['project_type']);
            $table->dropColumn(['region', 'project_type', 'project_size']);
        });
    }
};
