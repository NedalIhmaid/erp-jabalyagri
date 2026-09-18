<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leave_balances')
            ->where('sick_total', 84)
            ->update(['sick_total' => 14]);

        DB::table('leave_balances')
            ->where('sick_used', '>', 14)
            ->update(['sick_used' => 14]);
    }

    public function down(): void
    {
        // Intentionally empty: no safe reversal.
    }
};
