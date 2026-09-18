<?php

namespace Database\Seeders;

use App\Enums\ProductUnitType;
use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Database\Seeder;

class ProductHierarchyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['طماطم', 'تشكيلة واسعة من أصناف الطماطم عالية الإنتاجية ومقاومة الأمراض، مناسبة للزراعة المحمية والمكشوفة.', 'demo-products/tomato-family.svg', [
                ['طماطم بلدي', 'TOM-LOCAL', 'ثمار مستديرة متجانسة الحجم بلون أحمر قاني ومذاق حلو مركز.', 1.10, 5.00, 'demo-products/local-tomato.svg'],
                ['طماطم كرزية', 'TOM-CHERRY', 'ثمار صغيرة حلوة عالية البريكس، مناسبة للتصدير وسلاسل الفنادق.', 1.60, 7.25, 'demo-products/cherry-tomato.svg'],
                ['طماطم روما', 'TOM-PLUM', 'ثمار بيضاوية متماسكة القشرة تتحمل النقل الطويل والتخزين.', 1.00, 4.50, 'demo-products/plum-tomato.svg'],
            ]],
            ['خيار', 'أصناف خيار طازجة للبيع المحلي والتصدير.', 'demo-products/cucumber.svg', [
                ['خيار شامي', 'CUC-SHAMI', 'ثمار متوسطة مقرمشة ومناسبة للسلطات.', .75, 3.50],
                ['خيار إنجليزي', 'CUC-ENGLISH', 'ثمار طويلة ناعمة القشرة وقليلة البذور.', 1.10, 5.00],
            ]],
            ['فلفل', 'تشكيلة من الفلفل الحلو والحار بأحجام مختلفة.', 'demo-products/pepper.svg', [
                ['فلفل حلو أخضر', 'PEP-GREEN', 'ثمار متماسكة بلون أخضر لامع.', 1.25, 5.75],
                ['فلفل حار أحمر', 'PEP-HOT-RED', 'فلفل أحمر حار مناسب للطبخ والمخللات.', 1.50, 7.00],
            ]],
            ['باذنجان', 'أصناف باذنجان منتقاة مناسبة للطبخ والتسويق.', 'demo-products/eggplant.svg', [
                ['باذنجان بلدي', 'EGG-LOCAL', 'ثمار كبيرة بلون بنفسجي داكن ولب متماسك.', .90, 4.00],
                ['باذنجان طويل', 'EGG-LONG', 'ثمار طويلة متجانسة ومناسبة للشوي.', 1.00, 4.50],
            ]],
        ];

        foreach ($catalog as [$categoryName, $categoryDescription, $image, $varieties]) {
            $category = ProductFamily::updateOrCreate(['name' => $categoryName], [
                'description' => $categoryDescription, 'image_path' => $image, 'is_active' => true,
            ]);

            foreach ($varieties as $variety) {
                [$name, $sku, $description, $kiloPrice, $boxPrice] = $variety;

                $product = Product::updateOrCreate(['sku' => $sku], [
                    'product_family_id' => $category->id, 'name' => $name, 'description' => $description,
                    'image_path' => $variety[5] ?? $image, 'pdf_url' => 'https://example.com/catalog/'.$sku.'.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-'.$sku, 'is_active' => true,
                ]);

                foreach ([['كيلوغرام', 1, $kiloPrice], ['صندوق 5 كغم', 5, $boxPrice]] as [$label, $value, $price]) {
                    $product->productUnits()->updateOrCreate(['label' => $label], [
                        'unit_type' => ProductUnitType::Kilogram, 'unit_value' => $value,
                        'price' => $price, 'needs_price_review' => false, 'is_active' => true,
                    ]);
                }
            }
        }
    }
}
