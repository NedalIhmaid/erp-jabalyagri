<?php

namespace Database\Factories;

use App\Models\ProductUnit;
use App\Models\SalesApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesRequestItem>
 */
class SalesRequestItemFactory extends Factory
{
    public function definition(): array
    {
        // Use an existing active ProductUnit if available, otherwise create one via factory
        $unit = ProductUnit::where('is_active', true)->inRandomOrder()->first()
            ?? ProductUnit::factory()->create();

        $quantity  = fake()->randomFloat(2, 1, 100);
        $unitPrice = (float) $unit->price;

        return [
            'sales_approval_request_id' => SalesApprovalRequest::factory(),
            'product_id'                => $unit->product_id,
            'product_unit_id'           => $unit->id,
            'product_name'              => $unit->product->name,
            'quantity'                  => $quantity,
            'unit'                      => $unit->label,
            'unit_price'                => $unitPrice,
            'total_price'               => round($quantity * $unitPrice, 2),
            'notes'                     => fake()->optional()->sentence(),
        ];
    }
}
