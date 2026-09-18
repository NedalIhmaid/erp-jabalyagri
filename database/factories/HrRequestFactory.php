<?php

namespace Database\Factories;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HrRequest>
 */
class HrRequestFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(HrRequestType::class);
        $status = fake()->randomElement(HrRequestStatus::cases());
        $startDate = fake()->dateTimeBetween('-30 days', 'now');
        $endDate = fake()->dateTimeBetween($startDate, '+14 days');

        $isDeparture = in_array($type, [HrRequestType::EarlyDeparture, HrRequestType::DepartureFromAnnual]);
        $reasons = ['ظرف عائلي طارئ', 'مراجعة طبية', 'استراحة سنوية مخططة', 'متابعة معاملة شخصية', 'السفر لمدة قصيرة'];
        $comments = ['لا مانع من الموافقة.', 'تمت مراجعة الطلب.', 'الموافقة حسب رصيد الإجازات.', 'يرجى تنسيق المهام قبل المغادرة.'];

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $isDeparture ? null : $endDate,
            'start_time' => $isDeparture ? fake()->time('H:i') : null,
            'end_time' => $isDeparture ? fake()->time('H:i') : null,
            'duration_days' => $isDeparture ? null : fake()->randomFloat(1, 1, 30),
            'reason' => fake()->randomElement($reasons),
            'status' => $status,
            'manager_id' => User::factory(),
            'manager_action_at' => in_array($status, [HrRequestStatus::ManagerApproved, HrRequestStatus::Approved, HrRequestStatus::Rejected]) ? fake()->dateTimeBetween($startDate, 'now') : null,
            'manager_comments' => fake()->optional()->randomElement($comments),
            'gm_action_at' => $status === HrRequestStatus::Approved || $status === HrRequestStatus::Rejected ? fake()->dateTimeBetween($startDate, 'now') : null,
            'gm_comments' => fake()->optional()->randomElement($comments),
        ];
    }
}
