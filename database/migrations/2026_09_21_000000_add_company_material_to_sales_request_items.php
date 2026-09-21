<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->foreignId('company_material_id')->nullable()
                ->constrained('company_materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_material_id');
        });
    }
};
