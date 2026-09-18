<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ShieldSeeder::class,
            ProductCatalogSeeder::class,
            VarietyCatalogSeeder::class,
            PaymentMethodSeeder::class,
            FarmerSeeder::class,
            CompanyUserSeeder::class,
        ]);
    }
}
