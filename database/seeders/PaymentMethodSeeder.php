<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['key' => 'on_account',        'name_ar' => 'ذمم',          'name_en' => 'On Account',      'color' => 'info',    'sort_order' => 1],
            ['key' => 'accounting_note',   'name_ar' => 'ورقة حسبة',     'name_en' => 'Accounting Note', 'color' => 'primary', 'sort_order' => 2],
            ['key' => 'check',             'name_ar' => 'شيك',           'name_en' => 'Check',           'color' => 'gray',    'sort_order' => 3],
            ['key' => 'installment',       'name_ar' => 'دفعات',         'name_en' => 'Installment',     'color' => 'warning', 'sort_order' => 4],
            ['key' => 'cash_checks',       'name_ar' => 'نقدي وشيكات',   'name_en' => 'Cash & Checks',   'color' => 'success', 'sort_order' => 5],
            ['key' => 'checks_on_account', 'name_ar' => 'شيكات وذمم',    'name_en' => 'Checks & On Account', 'color' => 'info', 'sort_order' => 6],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['key' => $method['key']],
                $method + ['is_active' => true],
            );
        }
    }
}
