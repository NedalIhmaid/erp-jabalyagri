<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address', 255)->nullable();
            $table->string('label', 255)->nullable();
            $table->string('provider_message_id', 64)->nullable()->unique();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['user_id', 'recorded_at']);
        });

        // Last time the employee messaged the business number; WhatsApp only
        // allows free-form messages within 24 hours of it.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('whatsapp_last_inbound_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp_last_inbound_at');
        });

        Schema::dropIfExists('attendance_records');
    }
};
