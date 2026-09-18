<?php

namespace Tests\Feature;

use App\Enums\ProductUnitType;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngineerProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_view_category_cards_and_products(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $category = ProductFamily::create([
            'name' => 'خيار',
            'description' => 'أصناف خيار طازجة.',
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_family_id' => $category->id,
            'name' => 'خيار شامي',
            'sku' => 'CUC-SHAMI',
            'description' => 'ثمار متوسطة مقرمشة.',
            'is_active' => true,
        ]);

        $product->productUnits()->create([
            'label' => 'كيلوغرام',
            'unit_type' => ProductUnitType::Kilogram,
            'unit_value' => 1,
            'price' => 0.75,
            'is_active' => true,
        ]);

        $engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();

        $this->actingAs($engineer)
            ->get('/product-categories')
            ->assertOk()
            ->assertSee('خيار')
            ->assertSee('catalog-family-row', false)
            ->assertDontSee('fi-ta-record-checkbox', false);

        $this->actingAs($engineer)
            ->get("/product-categories/{$category->id}")
            ->assertOk()
            ->assertSee('خيار شامي')
            ->assertSee("/products/{$product->id}", false);

        $this->actingAs($engineer)
            ->get("/products/{$product->id}")
            ->assertOk()
            ->assertSee('خيار شامي')
            ->assertSee('ثمار متوسطة مقرمشة.')
            ->assertSee('كيلوغرام')
            ->assertDontSee('JOD');
    }
}
