<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyVisit>
 */
class DailyVisitFactory extends Factory
{
    public function definition(): array
    {
        $clients = ['مزارع الوادي الأخضر', 'شركة السنابل الزراعية', 'مزرعة البركة', 'مؤسسة أرض الخير', 'مزارع النخيل'];
        $locations = ['عمّان', 'إربد', 'السلط', 'مادبا', 'جرش', 'الكرك'];
        $reasons = ['متابعة حالة المحصول', 'عرض منتجات جديدة', 'زيارة متابعة للطلب', 'معاينة احتياج المزرعة', 'حل مشكلة في التربة'];
        $notes = ['تمت الزيارة وتم توثيق احتياجات العميل.', 'العميل مهتم بتجربة المنتجات خلال الموسم الحالي.', 'تم الاتفاق على إرسال عرض سعر محدث.', 'المزرعة تحتاج إلى متابعة إضافية الأسبوع القادم.'];

        return [
            'user_id' => User::factory(),
            'visit_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'client_name' => fake()->randomElement($clients),
            'location_text' => fake()->randomElement($locations),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'client_phone' => fake()->phoneNumber(),
            'visit_reason' => fake()->randomElement($reasons),
            'visit_results' => fake()->randomElement($notes),
        ];
    }
}
