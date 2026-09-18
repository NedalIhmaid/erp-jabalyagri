<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            [
                'name' => 'engineer',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employee',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse_keeper',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales_manager',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchasing_manager',
                'guard_name' => 'web',
            ],
            [
                'name' => 'financial_manager',
                'guard_name' => 'web',
            ],
            [
                'name' => 'general_manager',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->info(count($roles).' roles created successfully.');
    }
}
