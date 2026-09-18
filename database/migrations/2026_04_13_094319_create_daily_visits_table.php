<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('client_name', 255);
            $table->string('location_text', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('client_phone', 20)->nullable();
            $table->text('visit_reason');
            $table->text('visit_notes')->nullable();
            $table->string('visit_photo', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_visits');
    }
};
