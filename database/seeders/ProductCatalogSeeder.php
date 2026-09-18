<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $workbook = base_path('مواد الشركة.xlsx');

        $exitCode = $this->command?->call('products:import', [
            'path' => $workbook,
        ]) ?? 1;

        if ($exitCode !== 0) {
            throw new \RuntimeException("فشل استيراد ملف المنتجات: {$workbook}");
        }
    }
}
