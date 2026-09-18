<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_visits', function (Blueprint $table) {
            $table->renameColumn('visit_notes', 'visit_results');
        });
    }

    public function down(): void
    {
        Schema::table('daily_visits', function (Blueprint $table) {
            $table->renameColumn('visit_results', 'visit_notes');
        });
    }
};
