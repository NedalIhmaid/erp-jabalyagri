<?php

namespace Tests\Unit\Models;

use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRequestItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_request_item_can_be_created(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Test Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);

        $this->assertDatabaseHas('sales_request_items', [
            'product_name' => 'Test Product',
            'quantity' => 5,
        ]);
    }

    public function test_sales_request_item_has_correct_casts(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Test Product',
            'quantity' => 2.5,
            'unit' => 'kg',
            'unit_price' => 50.75,
            'total_price' => 126.88,
        ]);

        $this->assertEquals(2.5, (float) $item->quantity);
        $this->assertEquals(50.75, (float) $item->unit_price);
        $this->assertEquals(126.88, (float) $item->total_price);
    }

    public function test_sales_request_item_sales_approval_relationship(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Test Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);

        $this->assertEquals($request->id, $item->salesApprovalRequest->id);
    }

    public function test_sales_request_item_fillable_attributes(): void
    {
        $item = new SalesRequestItem();
        $expected = [
            'sales_approval_request_id', 'product_id',
            'product_unit_id', 'product_name', 'quantity', 'unit', 'unit_price', 'total_price', 'notes',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $item->getFillable());
        }
    }

    public function test_sales_request_item_display_product_name_without_product(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Fallback Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);

        $this->assertEquals('Fallback Product', $item->display_product_name);
    }

    public function test_sales_request_item_can_have_optional_notes(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Test Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
            'notes' => 'Special delivery instructions',
        ]);

        $this->assertEquals('Special delivery instructions', $item->notes);
    }

    public function test_sales_request_item_notes_are_optional(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Test Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);

        $this->assertNull($item->notes);
    }

    public function test_sales_request_item_can_be_updated(): void
    {
        $request = SalesApprovalRequest::factory()->create();

        $item = SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_name' => 'Old Product',
            'quantity' => 5,
            'unit' => 'piece',
            'unit_price' => 100.00,
            'total_price' => 500.00,
        ]);

        $item->update([
            'product_name' => 'New Product',
            'quantity' => 10,
            'unit_price' => 150.00,
            'total_price' => 1500.00,
        ]);

        $fresh = $item->fresh();
        $this->assertEquals('New Product', $fresh->product_name);
        $this->assertEquals(10, (float) $fresh->quantity);
        $this->assertEquals(150.00, (float) $fresh->unit_price);
        $this->assertEquals(1500.00, (float) $fresh->total_price);
    }
}
