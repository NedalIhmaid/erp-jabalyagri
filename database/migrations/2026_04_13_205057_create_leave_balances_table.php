<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->year('year');
            // Annual leave
            $table->decimal('annual_total', 5, 1)->default(14);
            $table->decimal('annual_used', 5, 1)->default(0);
            // Sick leave (days per year)
            $table->decimal('sick_total', 5, 1)->default(14);
            $table->decimal('sick_used', 5, 1)->default(0);
            // Marriage leave (one-time, 3 days)
            $table->boolean('marriage_used')->default(false);
            // Maternity leave (70 days, lifetime)
            $table->integer('maternity_used')->default(0);
            // Bereavement (tracks total used)
            $table->integer('bereavement_used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
