<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_visits', function (Blueprint $table) {
            $table->foreignId('farmer_id')
                ->nullable()
                ->after('user_id')
                ->constrained('farmers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('farmer_id');
        });
    }
};
