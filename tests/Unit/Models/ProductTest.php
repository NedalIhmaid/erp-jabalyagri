<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-' . uniqid(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => $product->sku,
        ]);
    }

    public function test_product_has_correct_casts(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-' . uniqid(),
            'is_active' => true,
        ]);

        $this->assertTrue($product->is_active);
    }

    public function test_product_sales_request_items_relationship(): void
    {
        $request = SalesApprovalRequest::factory()->create();
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-' . uniqid(),
        ]);

        SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_id' => $product->id,
            'product_name' => 'Test Product',
            'quantity' => 2,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 200.00,
        ]);

        $this->assertCount(1, $product->salesRequestItems);
    }

    public function test_product_fillable_attributes(): void
    {
        $product = new Product();
        $expected = [
            'product_family_id', 'name', 'sku', 'description', 'image_path',
            'pdf_url', 'google_drive_url', 'is_active',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $product->getFillable());
        }
    }

    public function test_product_can_be_inactive(): void
    {
        $product = Product::create([
            'name' => 'Discontinued Product',
            'sku' => 'DISC-' . uniqid(),
            'is_active' => false,
        ]);

        $this->assertFalse($product->is_active);
    }

    public function test_product_can_be_updated(): void
    {
        $product = Product::create([
            'name' => 'Old Name',
            'sku' => 'OLD-' . uniqid(),
            'is_active' => true,
        ]);

        $product->update([
            'name' => 'New Name',
            'is_active' => false,
        ]);

        $fresh = $product->fresh();
        $this->assertEquals('New Name', $fresh->name);
        $this->assertFalse($fresh->is_active);
    }
}
