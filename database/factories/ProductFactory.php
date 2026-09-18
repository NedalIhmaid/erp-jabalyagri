<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $names = [
            'بذور طماطم هجينة',
            'بذور خيار محمي',
            'بذور فلفل حلو',
            'بذور باذنجان طويل',
            'شتلات فراولة',
            'محلول عناصر صغرى',
            'سماد ورقي متوازن',
            'منشط جذور سائل',
            'كالسيوم بورون سائل',
            'حمض أميني للنبات',
            'سماد بوتاسيوم حبيبي',
            'سماد نيتروجين مركب',
            'سماد عضوي محبب',
            'كبريت زراعي ناعم',
            'فوسفات زراعي',
            'صينية تشتيل بلاستيكية',
            'شبك تظليل زراعي',
            'خرطوم ري بالتنقيط',
            'وصلة ري بلاستيكية',
            'مصيدة حشرات لاصقة',
        ];

        return [
            'name'      => fake()->randomElement($names),
            'sku'       => 'منتج-' . fake()->unique()->numberBetween(1000, 999999),
            'is_active' => true,
        ];
    }
}
