<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->boolean('needs_price_review')->default(false)->after('price');

            $table->index('needs_price_review');
        });
    }

    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropIndex(['needs_price_review']);
            $table->dropColumn('needs_price_review');
        });
    }
};
