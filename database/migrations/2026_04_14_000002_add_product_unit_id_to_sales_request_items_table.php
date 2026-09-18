<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ProductUnit::class);
            $table->dropColumn('product_unit_id');
        });
    }
};
