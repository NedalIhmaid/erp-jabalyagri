<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDescriptionEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_form_uses_rich_text_description(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $gm = User::where('email', 'gm@aljabali.com')->firstOrFail();

        $this->actingAs($gm)->get('/products/create')
            ->assertOk()
            ->assertSee('fi-fo-rich-editor', false);

        $family = ProductFamily::create(['name' => 'خيار', 'is_active' => true]);
        $product = Product::create([
            'product_family_id' => $family->id,
            'name' => 'خيار شامي',
            'sku' => 'CUC-SHAMI',
            'is_active' => true,
        ]);

        $this->actingAs($gm)->get("/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('fi-fo-rich-editor', false);
    }

    public function test_product_page_renders_rich_text_description_safely(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();

        $family = ProductFamily::create(['name' => 'خيار', 'is_active' => true]);
        $product = Product::create([
            'product_family_id' => $family->id,
            'name' => 'خيار شامي',
            'sku' => 'CUC-SHAMI',
            'is_active' => true,
            'description' => '<p>ثمار متوسطة مقرمشة.</p><h3>التعبئة</h3><ul><li>صناديق 5 كغم</li></ul><a href="javascript:alert(1)">رابط</a>',
        ]);

        $this->actingAs($engineer)
            ->get("/products/{$product->id}")
            ->assertOk()
            ->assertSee('ثمار متوسطة مقرمشة.')
            ->assertSee('<h3>التعبئة</h3>', false)
            ->assertSee('<li>صناديق 5 كغم</li>', false)
            ->assertDontSee('javascript:');
    }

    public function test_admin_product_view_gives_description_full_width(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $gm = User::where('email', 'gm@aljabali.com')->firstOrFail();

        $family = ProductFamily::create(['name' => 'خيار', 'is_active' => true]);
        $product = Product::create([
            'product_family_id' => $family->id,
            'name' => 'خيار شامي',
            'sku' => 'CUC-SHAMI',
            'is_active' => true,
            'description' => '<p>ثمار متوسطة مقرمشة.</p>',
            'pdf_url' => 'https://example.com/catalog.pdf',
            'google_drive_url' => 'https://drive.google.com/folder',
        ]);

        $this->actingAs($gm)
            ->get("/products/{$product->id}")
            ->assertOk()
            ->assertSee('md:fi-grid-col-span', false)
            ->assertSee('fi-prose', false)
            ->assertSee('href="https://example.com/catalog.pdf"', false)
            ->assertSee('href="https://drive.google.com/folder"', false)
            ->assertSee('فتح ملف PDF')
            ->assertSee('فتح مجلد الصور');
    }
}
