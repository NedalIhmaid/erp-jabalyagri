<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('duration_days', 4, 1)->nullable();
            $table->text('reason')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_action_at')->nullable();
            $table->text('manager_comments')->nullable();
            $table->timestamp('gm_action_at')->nullable();
            $table->text('gm_comments')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['manager_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_requests');
    }
};
