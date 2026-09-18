<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_contains_sellable_varieties(): void
    {
        $family = ProductFamily::create([
            'name' => 'بندورة',
            'description' => 'أصناف البندورة',
            'is_active' => true,
        ]);

        $variety = Product::create([
            'product_family_id' => $family->id,
            'name' => 'بندورة شيري',
            'sku' => 'TOM-CHERRY',
            'pdf_url' => 'https://example.com/cherry.pdf',
            'google_drive_url' => 'https://drive.google.com/drive/folders/example',
            'is_active' => true,
        ]);

        $this->assertTrue($family->products->contains($variety));
        $this->assertTrue($variety->family->is($family));
    }
}
