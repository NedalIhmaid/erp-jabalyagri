<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Real company staff for Al-Jabali Agricultural Co.
 *
 * Emails are under @jabalyagri.com. All accounts share the default password
 * "password" and should be changed on first login.
 */
class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $make = function (string $email, string $name, string $role, ?int $managerId) use ($password): User {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'      => $name,
                    'password'  => $password,
                    'is_active' => true,
                    'locale'    => 'ar',
                    'gender'    => 'male',
                    'manager_id' => $managerId,
                ]
            );

            $user->syncRoles([$role]);

            return $user;
        };

        // General Manager — top of the hierarchy
        $gm = $make('firas.aljabali@jabalyagri.com', 'فراس الجبالي', 'general_manager', null);

        // Department managers report to the GM
        $salesManager = $make('albaraa.zaidan@jabalyagri.com', 'البراء زيدان', 'sales_manager', $gm->id);
        $make('ibrahim.siouf@jabalyagri.com', 'إبراهيم سيوف', 'financial_manager', $gm->id);
        $make('ashraf.alzoughbi@jabalyagri.com', 'أشرف الزغبي', 'purchasing_manager', $gm->id);

        // Warehouse keeper reports to the sales manager (HR direct-manager chain)
        $make('hosam.shoshieh@jabalyagri.com', 'حسام شوشيه', 'warehouse_keeper', $salesManager->id);

        // Sales engineers report to the sales manager
        $engineers = [
            'nasser.alnasser@jabalyagri.com'   => 'ناصر الناصر',
            'murad.altalawi@jabalyagri.com'    => 'مراد التلاوي',
            'ihab.alamarat@jabalyagri.com'     => 'ايهاب العمارات',
            'odai.alshyoukhi@jabalyagri.com'   => 'عدي الشيوخي',
            'mohammad.albustoni@jabalyagri.com' => 'محمد البستوني',
            'khaled.handash@jabalyagri.com'    => 'خالد حنداش',
            'osama.abumansour@jabalyagri.com'  => 'اسامة ابو منصور',
            'moath.alessa@jabalyagri.com'      => 'معاذ العيسى',
            'hazem.farah@jabalyagri.com'       => 'حازم فراح',
        ];

        foreach ($engineers as $email => $name) {
            $make($email, $name, 'engineer', $salesManager->id);
        }

        $this->command?->info('Created '.User::count().' company users (@jabalyagri.com).');
    }
}
