<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment methods are now GM-managed (see payment_methods table). GM-generated
 * slugs can exceed the original 20-char limit, so widen the column to 50 to
 * match payment_methods.key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_approval_requests', function (Blueprint $table) {
            $table->string('payment_method', 20)->change();
        });
    }
};
