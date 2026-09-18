<?php

namespace Database\Seeders;

use App\Models\Farmer;
use Illuminate\Database\Seeder;

class FarmerSeeder extends Seeder
{
    public function run(): void
    {
        $farmers = [
            ['name' => 'أحمد الجبالي',   'phone' => '0599123456', 'location' => 'طولكرم',  'sort_order' => 1],
            ['name' => 'محمود عبد الله', 'phone' => '0598234567', 'location' => 'جنين',    'sort_order' => 2],
            ['name' => 'خالد يوسف',      'phone' => '0597345678', 'location' => 'نابلس',   'sort_order' => 3],
            ['name' => 'سامي درويش',     'phone' => '0569456789', 'location' => 'قلقيلية',  'sort_order' => 4],
            ['name' => 'عماد حسن',       'phone' => '0568567890', 'location' => 'أريحا',   'sort_order' => 5],
        ];

        foreach ($farmers as $farmer) {
            Farmer::updateOrCreate(
                ['name' => $farmer['name']],
                $farmer + ['is_active' => true],
            );
        }
    }
}
