<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paper form values: ذمم / ورقة حسبة / شيك / دفعات.
 * Old enum: cash / credit / installment / check.
 *
 * Mapping decided with the plan:
 *   cash         → on_account       (cash was never on the paper form;
 *                                    on-account is the closest substitute
 *                                    until finance re-classifies)
 *   credit       → accounting_note  (receivables paid against accounting notes)
 *   check        → check            (unchanged)
 *   installment  → installment      (unchanged)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales_approval_requests')->where('payment_method', 'cash')->update(['payment_method' => 'on_account']);
        DB::table('sales_approval_requests')->where('payment_method', 'credit')->update(['payment_method' => 'accounting_note']);
    }

    public function down(): void
    {
        DB::table('sales_approval_requests')->where('payment_method', 'on_account')->update(['payment_method' => 'cash']);
        DB::table('sales_approval_requests')->where('payment_method', 'accounting_note')->update(['payment_method' => 'credit']);
    }
};
