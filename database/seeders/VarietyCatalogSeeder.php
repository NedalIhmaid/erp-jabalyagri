<?php

namespace Database\Seeders;

use App\Enums\ProductUnitType;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

/**
 * Adds extra seed-company style varieties (type + feature bullets + variety
 * notes, like the reference seed catalogs) to the imported catalog families.
 * Keyed on SKU so it can be re-run without duplicating or touching rows that
 * were imported from the company workbook.
 */
class VarietyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $familyName => $varieties) {
            $family = ProductFamily::where('name', $familyName)->first();
            if (! $family) {
                $this->command?->warn("التصنيف غير موجود، تم التخطي: {$familyName}");

                continue;
            }

            foreach ($varieties as $variety) {
                $units = $variety['units'];
                unset($variety['units']);

                $product = Product::updateOrCreate(
                    ['sku' => $variety['sku']],
                    [...$variety, 'product_family_id' => $family->id],
                );

                foreach ($units as $unit) {
                    ProductUnit::updateOrCreate(
                        ['product_id' => $product->id, 'label' => $unit['label']],
                        [...$unit, 'product_id' => $product->id],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function catalog(): array
    {
        $bullet = fn (array $lines): string => implode("\n", array_map(fn ($l) => "• {$l}", $lines));

        return [
            'خيار' => [
                [
                    'name' => 'خيار بيبي',
                    'sku' => 'CUC-BABY',
                    'description' => "خيار بيبي ثمار قصيرة داكنة اللون بقشرة رقيقة، مثالي للسلطات والتغليف الحديث.\n"
                        .$bullet([
                            'نمو قوي ومتوازن مع تحمل جيد للبياض الزغبي',
                            'ثمار متجانسة الطول بنسبة جودة تصديرية مرتفعة',
                            'مناسب للزراعة المحمية على طول الموسم',
                        ]),
                    'image_path' => 'demo-products/cucumber.svg',
                    'pdf_url' => 'https://example.com/catalog/CUC-BABY.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-CUC-BABY',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 10 غم', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 10, 'price' => 0.90, 'is_active' => true],
                        ['label' => 'علبة 100 غم', 'unit_type' => ProductUnitType::Gram, 'unit_value' => 100, 'price' => 7.50, 'is_active' => true],
                    ],
                ],
                [
                    'name' => 'خيار بلدي',
                    'sku' => 'CUC-BALADI',
                    'description' => "خيار بلدي ثمار صغيرة بنكهة تقليدية غنية، الأكثر طلباً في الأسواق المحلية.\n"
                        .$bullet([
                            'ثمار خضراء فاتحة متعسلة تتحمل النقل للمسافات البعيدة',
                            'دورة إنتاج قصيرة مع تحمل متوسط للعفن الأبيض',
                            'ملائم للزراعة المكشوفة في الموسم الصيفي',
                        ]),
                    'image_path' => 'demo-products/cucumber.svg',
                    'pdf_url' => 'https://example.com/catalog/CUC-BALADI.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-CUC-BALADI',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 10 غم', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 10, 'price' => 0.65, 'is_active' => true],
                        ['label' => 'علبة 100 غم', 'unit_type' => ProductUnitType::Gram, 'unit_value' => 100, 'price' => 5.25, 'is_active' => true],
                    ],
                ],
            ],
            'فلفل' => [
                [
                    'name' => 'فلفل حلو أحمر',
                    'sku' => 'PEP-SWEET-RED',
                    'description' => "فلفل حلو أحمر ثمار كبيرة رباعية الفصوص بجدار سميك ولون أحمر لامع.\n"
                        .$bullet([
                            'تحمل عالٍ لفيروس تبرقش ورق الطماطم (TYLCV)',
                            'ثمار ثابتة الحجم مناسبة للتصدير والتغليف',
                            'تلوين متجانس بعد اكتمال نمو الثمرة',
                        ]),
                    'image_path' => 'demo-products/pepper.svg',
                    'pdf_url' => 'https://example.com/catalog/PEP-SWEET-RED.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-PEP-SWEET-RED',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 1000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 1000, 'price' => 12.00, 'is_active' => true],
                        ['label' => 'علبة 5000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 5000, 'price' => 55.00, 'is_active' => true],
                    ],
                ],
            ],
            'باذنجان' => [
                [
                    'name' => 'باذنجان أسود دائري',
                    'sku' => 'EGG-BLACK-ROUND',
                    'description' => "باذنجان أسود دائري بلون ليلي لامع ولب كثيف قليل البذور.\n"
                        .$bullet([
                            'ثمار متوسطة الحجم تتحمل الطهي الطويل دون تفلل',
                            'تحمل جيد لدودة أوراق الباذنجان',
                            'إنتاج متراكم يتيح قطفاً أسبوعياً منتظماً',
                        ]),
                    'image_path' => 'demo-products/eggplant.svg',
                    'pdf_url' => 'https://example.com/catalog/EGG-BLACK-ROUND.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-EGG-BLACK-ROUND',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 10 غم', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 10, 'price' => 0.85, 'is_active' => true],
                        ['label' => 'علبة 100 غم', 'unit_type' => ProductUnitType::Gram, 'unit_value' => 100, 'price' => 6.75, 'is_active' => true],
                    ],
                ],
            ],
            'طماطم' => [
                [
                    'name' => 'طماطم عنقودية',
                    'sku' => 'TOM-CLUSTER',
                    'description' => "طماطم عنقودية يُحصد العنقود كاملاً متماسكاً، الأنسب لأرفف السوبر ماركت والتصدير.\n"
                        .$bullet([
                            'تماسك عنقودي عالٍ مع عمر تخزين طويل بعد الحصاد',
                            'مقاومة مركبة لفيروس تبرقش الطماطم (ToMV) والذبول الفيوزاريومي (Fol)',
                            'مناسب للزراعة في البيوت المحمية والمكشوفة',
                        ]),
                    'image_path' => 'demo-products/cherry-tomato.svg',
                    'pdf_url' => 'https://example.com/catalog/TOM-CLUSTER.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-TOM-CLUSTER',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 1000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 1000, 'price' => 14.50, 'is_active' => true],
                        ['label' => 'علبة 10000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 10000, 'price' => 135.00, 'is_active' => true],
                    ],
                ],
                [
                    'name' => 'طماطم بيف',
                    'sku' => 'TOM-BEEF',
                    'description' => "طماطم بيف ثمار ضخمة رباعية الفصوص كثيفة اللحم، الخيار الأول للشرائح والمطاعم.\n"
                        .$bullet([
                            'وزن الثمرة من 180 – 250 غم مع تلوين متساوٍ من القلب للقشرة',
                            'قليلة البذور ولحمية عالية بلا فراغات داخلية',
                            'تحمل جيد للبياض الدقيقي والفيروسات الشائعة',
                        ]),
                    'image_path' => 'demo-products/local-tomato.svg',
                    'pdf_url' => 'https://example.com/catalog/TOM-BEEF.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-TOM-BEEF',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 500 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 500, 'price' => 9.75, 'is_active' => true],
                        ['label' => 'علبة 5000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 5000, 'price' => 89.00, 'is_active' => true],
                    ],
                ],
                [
                    'name' => 'طماطم كوكتيل',
                    'sku' => 'TOM-COCKTAIL',
                    'description' => "طماطم كوكتيل بنكهة متوازنة تجمع بين الحلاوة والحموضة المنعشة.\n"
                        .$bullet([
                            'نسبة سكر مرتفعة (بريكْس) تعطي نكهة مميزة للحصاد العنقودي',
                            'ثمار 25 – 35 غم بلون أحمر داكن لامع',
                            'تحمل عالٍ للحرارة مع استمرارية عقد الثمار صيفاً',
                        ]),
                    'image_path' => 'demo-products/plum-tomato.svg',
                    'pdf_url' => 'https://example.com/catalog/TOM-COCKTAIL.pdf',
                    'google_drive_url' => 'https://drive.google.com/drive/folders/DEMO-TOM-COCKTAIL',
                    'is_active' => true,
                    'units' => [
                        ['label' => 'بكيت 1000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 1000, 'price' => 11.25, 'is_active' => true],
                        ['label' => 'علبة 10000 حبة', 'unit_type' => ProductUnitType::Packet, 'unit_value' => 10000, 'price' => 102.00, 'is_active' => true],
                    ],
                ],
            ],
        ];
    }
}
