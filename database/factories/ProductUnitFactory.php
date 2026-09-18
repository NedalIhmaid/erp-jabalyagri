<?php

namespace Database\Factories;

use App\Enums\ProductUnitType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(ProductUnitType::cases());

        $valueMap = [
            ProductUnitType::Milliliter->value => fake()->randomElement([100, 250, 500]),
            ProductUnitType::Liter->value       => fake()->randomElement([1, 5, 20]),
            ProductUnitType::Gram->value        => fake()->randomElement([100, 250, 500]),
            ProductUnitType::Kilogram->value    => fake()->randomElement([1, 5, 25, 50]),
            ProductUnitType::Ton->value         => 1,
            ProductUnitType::Sack->value        => fake()->randomElement([25, 50]),
            ProductUnitType::Packet->value      => null,
            ProductUnitType::Unit->value        => null,
        ];

        $value = $valueMap[$type->value];

        $label = match ($type) {
            ProductUnitType::Milliliter => "علبة {$value}مل",
            ProductUnitType::Liter      => "علبة {$value} لتر",
            ProductUnitType::Gram       => "{$value} غرام",
            ProductUnitType::Kilogram   => "{$value} كغم",
            ProductUnitType::Ton        => 'طن',
            ProductUnitType::Sack       => "شوال {$value}كغ",
            ProductUnitType::Packet     => 'بكيت',
            ProductUnitType::Unit       => 'وحدة',
        };

        return [
            'product_id' => Product::factory(),
            'label'      => $label,
            'unit_type'  => $type,
            'unit_value' => $value,
            'price'      => fake()->randomFloat(2, 5, 500),
            'is_active'  => true,
        ];
    }
}
