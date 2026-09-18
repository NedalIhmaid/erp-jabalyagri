<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_approval_request_id')->constrained('sales_approval_requests')->cascadeOnDelete();
            $table->string('product_name', 255);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sales_approval_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_request_items');
    }
};
