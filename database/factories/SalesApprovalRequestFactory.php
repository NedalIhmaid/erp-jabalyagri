<?php

namespace Database\Factories;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesApprovalRequest>
 */
class SalesApprovalRequestFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(SalesRequestStatus::cases());
        $clients = ['مزارع الوادي الأخضر', 'شركة السنابل الزراعية', 'مزرعة البركة', 'مؤسسة أرض الخير', 'مزارع النخيل'];
        $addresses = ['عمّان - طريق المطار', 'إربد - الحي الشرقي', 'السلط - شارع الستين', 'مادبا - منطقة الفيصلية', 'جرش - طريق سوف'];
        $notes = ['يرغب العميل بتوريد الكميات خلال الأسبوع الحالي.', 'تمت مراجعة الاحتياج مع العميل ميدانياً.', 'الطلب مرتبط بموسم الزراعة الحالي.', 'يرجى اعتماد الأسعار بعد مراجعة الإدارة المالية.'];
        $rejectionReasons = ['تم إلغاء الطلب بناءً على طلب العميل.', 'يحتاج الطلب إلى بيانات إضافية قبل الاعتماد.', 'تعذر اعتماد الطلب بسبب نقص معلومات التسعير.'];

        return [
            'request_number' => 'SAR-' . fake()->dateTimeBetween('-90 days', 'now')->format('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'user_id' => User::factory(),
            'client_name' => fake()->randomElement($clients),
            'client_phone' => fake()->phoneNumber(),
            'client_address' => fake()->randomElement($addresses),
            'payment_method' => fake()->randomElement(['on_account', 'accounting_note', 'check', 'installment', 'cash_checks', 'checks_on_account']),
            'engineer_notes' => fake()->randomElement($notes),
            'total_amount' => fake()->randomFloat(2, 100, 50000),
            'current_stage' => fake()->numberBetween(1, 3),
            'status' => $status,
            'rejection_reason' => $status === SalesRequestStatus::Cancelled || $status === SalesRequestStatus::Rejected ? fake()->randomElement($rejectionReasons) : null,
        ];
    }
}
