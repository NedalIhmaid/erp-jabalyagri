<?php

namespace Database\Seeders;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\ProductUnit;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    private array $approvalComments = [
        'تمت الموافقة بعد مراجعة البيانات.',
        'الطلب مكتمل ويمكن المتابعة.',
        'تم اعتماد المرحلة الحالية.',
        'لا توجد ملاحظات إضافية.',
    ];

    public function run(): void
    {
        $engineers = User::role('engineer')->get();
        $warehouseKeepers = User::role('warehouse_keeper')->get();
        $purchasingManagers = User::role('purchasing_manager')->get();
        $financialManagers = User::role('financial_manager')->get();

        if ($engineers->isEmpty()) {
            $this->command->error('No users found. Run UserSeeder first.');

            return;
        }

        $units = ProductUnit::where('is_active', true)->get();

        if ($units->isEmpty()) {
            $this->command->error('No product units found. Run ProductCatalogSeeder first.');

            return;
        }

        // Daily visits
        $this->command->info('Creating daily visits...');
        foreach ($engineers as $engineer) {
            DailyVisit::factory()
                ->count(fake()->numberBetween(10, 25))
                ->create(['user_id' => $engineer->id]);
        }
        $this->command->info('✓ Daily visits created');

        // Sales requests across all statuses
        $this->command->info('Creating sales approval requests...');

        $stageActors = [
            1 => $warehouseKeepers,
            2 => $financialManagers,
            3 => $purchasingManagers,
        ];

        foreach ($engineers as $engineer) {
            // Pending (just submitted)
            $this->createRequests($engineer, $units, SalesRequestStatus::Pending, 1, 5, $stageActors, 0);

            // In Progress — at various stages
            for ($stage = 1; $stage <= 3; $stage++) {
                $this->createRequests($engineer, $units, SalesRequestStatus::InProgress, $stage, 3, $stageActors, $stage);
            }

            // Fully approved
            $this->createRequests($engineer, $units, SalesRequestStatus::Approved, 3, 8, $stageActors, 3);

            // Cancelled
            $this->createRequests($engineer, $units, SalesRequestStatus::Cancelled, fake()->numberBetween(1, 3), 3, $stageActors, 0);

            // Returned to engineer
            $this->createRequests($engineer, $units, SalesRequestStatus::Returned, 1, 2, $stageActors, 0);
        }
        $this->command->info('✓ Sales requests created');

        // HR requests
        $this->command->info('Creating HR requests...');
        $allEmployees = User::whereNotNull('manager_id')->get();

        foreach ($allEmployees as $employee) {
            HrRequest::factory()
                ->count(fake()->numberBetween(2, 6))
                ->create([
                    'user_id' => $employee->id,
                    'manager_id' => $employee->manager_id,
                ]);
        }
        $this->command->info('✓ HR requests created');

        $this->command->info('✅ FakeDataSeeder completed successfully!');
    }

    private function createRequests(
        User $engineer,
        $units,
        SalesRequestStatus $status,
        int $stage,
        int $count,
        array $stageActors,
        int $approvedUpToStage
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $request = SalesApprovalRequest::factory()->create([
                'user_id' => $engineer->id,
                'status' => $status,
                'current_stage' => $stage,
            ]);

            // Create 1–4 items per request
            $itemCount = fake()->numberBetween(1, 4);
            $totalAmount = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $unit = $units->random();
                $quantity = fake()->randomFloat(1, 1, 50);
                $unitPrice = (float) $unit->price;
                $total = round($quantity * $unitPrice, 2);
                $totalAmount += $total;

                SalesRequestItem::create([
                    'sales_approval_request_id' => $request->id,
                    'product_id' => $unit->product_id,
                    'product_unit_id' => $unit->id,
                    'product_name' => $unit->product->name,
                    'quantity' => $quantity,
                    'unit' => $unit->label,
                    'unit_price' => $unitPrice,
                    'total_price' => $total,
                ]);
            }

            $request->update(['total_amount' => round($totalAmount, 2)]);

            // Create approval stage records for stages that have been acted on
            for ($s = 1; $s <= min($approvedUpToStage, 3); $s++) {
                $actors = $stageActors[$s] ?? collect();
                $actor = $actors->isNotEmpty() ? $actors->random() : null;

                ApprovalStage::create([
                    'sales_approval_request_id' => $request->id,
                    'stage_number' => $s,
                    'role' => $this->roleForStage($s),
                    'approver_id' => $actor?->id,
                    'action' => ApprovalAction::Approved->value,
                    'comments' => fake()->optional(0.4)->randomElement($this->approvalComments),
                    'acted_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        }
    }

    private function roleForStage(int $stage): string
    {
        return [
            1 => 'warehouse_keeper',
            2 => 'financial_manager',
            3 => 'purchasing_manager',
        ][$stage];
    }
}
