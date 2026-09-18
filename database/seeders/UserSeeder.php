<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // General Manager — can see everything
        $gm = User::firstOrCreate(
            ['email' => 'gm@aljabali.com'],
            [
                'name' => 'نادر الجبالي',
                'password' => $password,
                'phone' => '+962790000001',
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $gm->assignRole('general_manager');

        // Financial Manager — reports to GM
        $fm = User::firstOrCreate(
            ['email' => 'finance@aljabali.com'],
            [
                'name' => 'سامر الحوراني',
                'password' => $password,
                'phone' => '+962790000002',
                'manager_id' => $gm->id,
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $fm->assignRole('financial_manager');

        // Purchasing Manager — reports to GM
        $pm = User::firstOrCreate(
            ['email' => 'purchasing@aljabali.com'],
            [
                'name' => 'محمود النجار',
                'password' => $password,
                'phone' => '+962790000003',
                'manager_id' => $gm->id,
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $pm->assignRole('purchasing_manager');

        // Sales Manager — reports to GM
        $sm = User::firstOrCreate(
            ['email' => 'sales@aljabali.com'],
            [
                'name' => 'رامي الخطيب',
                'password' => $password,
                'phone' => '+962790000004',
                'manager_id' => $gm->id,
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $sm->assignRole('sales_manager');

        // Warehouse Keeper — reports to Sales Manager for HR
        $wk = User::firstOrCreate(
            ['email' => 'warehouse@aljabali.com'],
            [
                'name' => 'فراس الزعبي',
                'password' => $password,
                'phone' => '+962790000005',
                'manager_id' => $sm->id,
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $wk->assignRole('warehouse_keeper');

        // Second Sales Manager — for cross-team scoping tests
        $sm2 = User::firstOrCreate(
            ['email' => 'sales2@aljabali.com'],
            [
                'name' => 'باسم العبدالله',
                'password' => $password,
                'phone' => '+962790000007',
                'manager_id' => $gm->id,
                'is_active' => true,
                'locale' => 'ar',
                'gender' => 'male',
            ]
        );
        $sm2->assignRole('sales_manager');

        // Field Engineers under first Sales Manager (sm)
        $engineersTeamA = [
            ['email' => 'engineer@aljabali.com',  'name' => 'أحمد العزام',  'phone' => '+962790000006'],
            ['email' => 'engineer2@aljabali.com', 'name' => 'خالد الرواشدة', 'phone' => '+962790000008'],
            ['email' => 'engineer3@aljabali.com', 'name' => 'محمد القضاة', 'phone' => '+962790000009'],
        ];

        foreach ($engineersTeamA as $data) {
            $e = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'phone' => $data['phone'],
                    'manager_id' => $sm->id,
                    'is_active' => true,
                    'locale' => 'ar',
                    'gender' => 'male',
                ]
            );
            $e->assignRole('engineer');
        }

        // Field Engineers under second Sales Manager (sm2)
        $engineersTeamB = [
            ['email' => 'engineer4@aljabali.com', 'name' => 'يوسف الشوابكة', 'phone' => '+962790000010'],
            ['email' => 'engineer5@aljabali.com', 'name' => 'سامي أبو رمان', 'phone' => '+962790000011'],
        ];

        foreach ($engineersTeamB as $data) {
            $e = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'phone' => $data['phone'],
                    'manager_id' => $sm2->id,
                    'is_active' => true,
                    'locale' => 'ar',
                    'gender' => 'male',
                ]
            );
            $e->assignRole('engineer');
        }

        $this->command->info('Test users created with roles.');

        // Print credentials
        $allUsers = User::orderBy('id')->get();
        foreach ($allUsers as $user) {
            $role = $user->roles->first()?->name ?? 'none';
            $managerEmail = $user->manager?->email ?? '—';
            $this->command->info("  {$user->email} / password — Role: {$role} — Manager: {$managerEmail}");
        }
    }
}
