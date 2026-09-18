<?php

namespace Database\Factories;

use App\Enums\ApprovalAction;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalStage>
 */
class ApprovalStageFactory extends Factory
{
    public function definition(): array
    {
        $roleMap = [
            1 => 'warehouse_keeper',
            2 => 'financial_manager',
            3 => 'purchasing_manager',
        ];

        $stageNumber = fake()->numberBetween(1, 3);

        return [
            'sales_approval_request_id' => SalesApprovalRequest::factory(),
            'stage_number' => $stageNumber,
            'role' => $roleMap[$stageNumber],
            'approver_id' => User::factory(),
            'action' => fake()->randomElement(ApprovalAction::cases()),
            'comments' => fake()->optional()->paragraph(),
            'acted_at' => fake()->optional()->dateTime(),
        ];
    }
}
