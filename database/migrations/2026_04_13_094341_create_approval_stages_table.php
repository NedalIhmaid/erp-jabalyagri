<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_approval_request_id')->constrained('sales_approval_requests')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->string('role', 30);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['sales_approval_request_id', 'stage_number']);
            $table->index(['approver_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_stages');
    }
};
