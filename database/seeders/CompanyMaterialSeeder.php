<?php

namespace Database\Seeders;

use App\Models\CompanyMaterialCategory;
use App\Models\CompanyMaterial;
use Illuminate\Database\Seeder;

class CompanyMaterialSeeder extends Seeder
{
    /**
     * Category definitions and the ordered keyword rules used to classify
     * imported items. First matching rule wins; anything unmatched falls
     * back to "مواد أخرى" so admins can re-classify by hand.
     */
    private array $categories = [
        'بذور وأصول' => 1,
        'أسمدة وتسميد' => 2,
        'منشطات ومغذيات نباتية' => 3,
        'مبيدات ومكافحة' => 4,
        'مستلزمات ومعدات' => 5,
        'مواد أخرى' => 6,
    ];

    public function run(): void
    {
        foreach ($this->categories as $name => $sort) {
            CompanyMaterialCategory::updateOrCreate(['name' => $name], ['sort' => $sort]);
        }

        $csvPath = __DIR__.'/data/company-materials.csv';
        if (! is_file($csvPath)) {
            $this->command?->warn("company-materials.csv not found — skipping import.");

            return;
        }

        $categories = CompanyMaterialCategory::pluck('id', 'name');
        $file = fopen($csvPath, 'r');
        fgetcsv($file); // header

        while (($row = fgetcsv($file)) !== false) {
            [$name, $unit] = array_pad($row, 2, '');
            if (trim($name) === '') {
                continue;
            }

            CompanyMaterial::updateOrCreate(
                ['name' => trim($name)],
                [
                    'unit' => trim($unit) ?: null,
                    'company_material_category_id' => $categories[$this->classify($name, $unit)],
                ],
            );
        }

        fclose($file);
    }

    private function classify(string $name, string $unit): string
    {
        $n = mb_strtolower($name);

        // Seeds & rootstocks: seeded varieties, rootstocks, seed packets,
        // and planting grains sold by the ton.
        if (preg_match('/بذور|بذرة|اصول|أصول|^اصل|^أصل|شعير|^قمح|تقاوي|بندورة|شمام|لوبيا|ذره|ذرة|فالتيرا|موسبي/u', $n)
            || str_contains($unit, 'بذرة')) {
            return 'بذور وأصول';
        }

        // Tools & supplies: greenhouse sheets, nets, shears, blades,
        // machinery, fasteners, traps, growing media, and the like.
        if (preg_match('/شاش|شبك|مقص|شفرة|زنبرك|موس تطعيم|منشار|جير موتور|جير بوكس|دراي شفت|خيط تربيط|مصائد|ماكنة|ماكينات|فرامة|متفرقات|لاصق|مبرد شف|كونتاكتور|سطل|اترارات|طقم غيار|مع طقم|براغي|برغي|بلاتين|تركتور|دريكسل|جير |jiffy|بيتموس|بيرلايت|cocco|كوكو/u', $n)) {
            return 'مستلزمات ومعدات';
        }

        // Fertilizers: branded bags/liquids, NPK formulations, ammonia,
        // nitrates/sulfates, soil sulfur, and trace-element mixes.
        if (preg_match('/سماد|امونياك|أمونياك|يوريا|\bdap\b|\bdkp\b|\bmpk\b|ام كي بي|نيتريت|نيترات|سلفات|شيلات|كبريت محبب|كبريت تعفير|ميكرومكس|مورسترين|خليط عناصر|جنزارة|\bksc\b|sulfacid|سلفاسيد|sulfammo|سلفامو|top-phos|p4p|هورتال|بلانت كوت|فورت |امير/u', $n)
            || preg_match('/\d+\s*\/\s*\d+\s*\/\s*\d+/', $n)) {
            return 'أسمدة وتسميد';
        }

        // Plant nutrients & stimulants: amino acids, algae, humic acids,
        // vitamins, and micro-elements (copper, iron, zinc, manganese...).
        if (preg_match('/احماض امينية|أحماض أمينية|احماض|امينو|أمينو|طحالب|هيومك|هيوميك|فيتامين|عناصر|نحاس|حديد|زنك|منغنيز|ماغنو|كالماج|فولكروب|folcrop|fertileader|فيرتيليدر|fertiactyl|راديكس|radix|فيلور|armer|ارمر|foscrop|maxfruit|فوسكال|فوسفوريك|suma|سوما|neo boost|نيو بوست|جي زد|ايبو ستار|ايبوستار|firt|هيدروجرين|بي اتش/u', $n)) {
            return 'منشطات ومغذيات نباتية';
        }

        // Pesticides: recognized actives and brand families.
        if (preg_match('/مثرين|امكتين|أبامكتين|ابامكتين|بيرفوس|ديازينون|مانكوزيب|كونازول|دايفازول|ديفازول|بيكونازول|ثياميثوكسام|سايبرمثرين|بيفنزيت|لامبدا|كلورفينر|اميدكلوبريد|بيريدابين|سبينوساد|هيكساثيازوكس|فلورامايت|راوند اب|جلايفوسيت|ستومب|دايمثويت|كاربنديت|فوسفيل|ازوكس|اسيبان|اسيميت|اميزول|بافلينا|باروك|بايرس|بايروميت|بايفيدان|بلتانول|بلوف|بنزول|بيرفكثيون|بيمونت|توباز|توبسين|تشجازول|دايكوثين|دكساثرين|دومينانت|ديفندر|سوبرين|سوراج|سلست|سيغابنز|سيفانتو|فلازون|فيوري|كانيمايت|كريستال|كاستروب|كافارا|ماتش|موديستا|مونسرين|نيج مايت|نيزاجري|نيورون|هيروس|هيمازول|نوكسبورو|اكتوماكس|انتراكيور|التاماتركس|اسفيت|افالوكس|انفاتون|ايماسايد|اينفورس|بادوفا|بوراجرو|بولدوك|بيرغادو|ناتيفو|اراندو|فوسجارد/u', $n)) {
            return 'مبيدات ومكافحة';
        }

        return 'مواد أخرى';
    }
}
