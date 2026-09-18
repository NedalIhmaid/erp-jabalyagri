<?php

namespace Database\Seeders;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\Product;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductCatalogSeeder::class);

        $engineer = User::whereHas('roles', fn ($q) => $q->where('name', 'engineer'))->first();
        $warehouse = User::whereHas('roles', fn ($q) => $q->where('name', 'warehouse_keeper'))->first();
        $salesManager = User::whereHas('roles', fn ($q) => $q->where('name', 'sales_manager'))->first();
        $purchasingManager = User::whereHas('roles', fn ($q) => $q->where('name', 'purchasing_manager'))->first();
        $financialManager = User::whereHas('roles', fn ($q) => $q->where('name', 'financial_manager'))->first();
        $catalog = Product::with('productUnits')->get()->keyBy('name');

        if (! $engineer) {
            $this->command->error('Engineer user not found. Run UserSeeder first.');

            return;
        }

        // Create daily visits
        $this->command->info('Creating daily visits...');
        $clients = ['مزارع الوادي الأخضر', 'شركة السنابل الزراعية', 'مزرعة البركة', 'مؤسسة أرض الخير', 'مزارع النخيل'];
        $locations = ['عمّان', 'إربد', 'السلط', 'مادبا', 'جرش'];
        $visitReasons = ['متابعة حالة المحصول', 'عرض منتجات جديدة', 'زيارة متابعة للطلب', 'معاينة احتياج المزرعة', 'حل مشكلة في التربة'];
        $visitNotes = ['تمت الزيارة وتم توثيق احتياجات العميل.', 'العميل مهتم بتجربة المنتجات خلال الموسم الحالي.', 'تم الاتفاق على إرسال عرض سعر محدث.', 'تحتاج المزرعة إلى متابعة إضافية.'];

        for ($i = 0; $i < 15; $i++) {
            DailyVisit::create([
                'user_id' => $engineer->id,
                'visit_date' => now()->subDays(rand(0, 30)),
                'client_name' => $clients[$i % 5],
                'location_text' => $locations[$i % 5],
                'latitude' => 31.9 + (rand(0, 100) / 1000),
                'longitude' => 35.9 + (rand(0, 100) / 1000),
                'client_phone' => '+96279'.rand(1000000, 9999999),
                'visit_reason' => $visitReasons[$i % 5],
                'visit_results' => $visitNotes[$i % 4],
            ]);
        }
        $this->command->info('✓ 15 daily visits created');

        // Create sales approval requests with different statuses
        $this->command->info('Creating sales approval requests...');
        $statuses = [
            SalesRequestStatus::Pending,
            SalesRequestStatus::InProgress,
            SalesRequestStatus::InProgress,
            SalesRequestStatus::Approved,
            SalesRequestStatus::Approved,
            SalesRequestStatus::Approved,
            SalesRequestStatus::Cancelled,
            SalesRequestStatus::Returned,
        ];

        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[$i % count($statuses)];
            $total = rand(100, 5000);

            $request = SalesApprovalRequest::create([
                'request_number' => 'SAR-'.now()->format('Ymd').'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'user_id' => $engineer->id,
                'client_name' => $clients[$i % 5],
                'client_phone' => '+96279'.rand(1000000, 9999999),
                'client_address' => 'عمّان، الأردن',
                'payment_method' => ['on_account', 'accounting_note', 'installment'][$i % 3],
                'engineer_notes' => 'تمت مراجعة احتياج العميل ميدانياً.',
                'total_amount' => $total,
                'current_stage' => $status === SalesRequestStatus::Approved ? 3 : ($status === SalesRequestStatus::Cancelled ? rand(1, 3) : 1),
                'status' => $status->value,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            // Create items
            $product = $catalog->values()->get($i % max($catalog->count(), 1));
            $productUnit = $product?->productUnits()->where('is_active', true)->first();
            $quantity = rand(10, 100);
            $unitPrice = (float) ($productUnit?->price ?? rand(5, 50));

            SalesRequestItem::create([
                'sales_approval_request_id' => $request->id,
                'product_id' => $product?->id,
                'product_unit_id' => $productUnit?->id,
                'product_name' => $product?->name ?? 'منتج من الكتالوج',
                'quantity' => $quantity,
                'unit' => $productUnit?->label ?? ['كغم', 'لتر', 'وحدة', 'علبة'][$i % 4],
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
            ]);

            // Create approval stages if in progress or approved
            if (in_array($status, [SalesRequestStatus::InProgress, SalesRequestStatus::Approved])) {
                $stages = [
                    ['stage_number' => 1, 'role' => 'warehouse_keeper', 'approver_id' => $warehouse?->id],
                    ['stage_number' => 2, 'role' => 'financial_manager', 'approver_id' => $financialManager?->id],
                    ['stage_number' => 3, 'role' => 'purchasing_manager', 'approver_id' => $purchasingManager?->id],
                ];

                foreach ($stages as $stage) {
                    $action = $status === SalesRequestStatus::Approved ? 'approved' :
                        ($stage['stage_number'] < $request->current_stage ? 'approved' : 'pending');

                    ApprovalStage::create([
                        'sales_approval_request_id' => $request->id,
                        'stage_number' => $stage['stage_number'],
                        'role' => $stage['role'],
                        'approver_id' => $stage['approver_id'],
                        'action' => $action,
                        'comments' => $action === 'approved' ? 'تمت الموافقة.' : null,
                        'acted_at' => $action === 'approved' ? now()->subDays(rand(0, 20)) : null,
                    ]);
                }
            }
        }
        $this->command->info('✓ 20 sales approval requests created');

        // Create HR requests
        $this->command->info('Creating HR requests...');
        $hrStatuses = [
            HrRequestStatus::Pending,
            HrRequestStatus::ManagerApproved,
            HrRequestStatus::Approved,
            HrRequestStatus::Rejected,
        ];
        $hrTypes = [
            HrRequestType::AnnualLeave,
            HrRequestType::SickLeave,
            HrRequestType::UnpaidLeave,
            HrRequestType::EarlyDeparture,
            HrRequestType::MarriageLeave,
        ];

        $users = User::whereIn('email', [
            'engineer@aljabali.com',
            'warehouse@aljabali.com',
            'sales@aljabali.com',
        ])->get();

        for ($i = 0; $i < 12; $i++) {
            $user = $users[$i % $users->count()];
            $status = $hrStatuses[$i % count($hrStatuses)];
            $type = $hrTypes[$i % count($hrTypes)];

            HrRequest::create([
                'user_id' => $user->id,
                'type' => $type->value,
                'start_date' => now()->addDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(5, 40)),
                'duration_days' => rand(1, 10),
                'reason' => 'ظرف عائلي طارئ',
                'status' => $status->value,
                'manager_id' => $salesManager?->id,
                'manager_action_at' => in_array($status, [HrRequestStatus::ManagerApproved, HrRequestStatus::Approved]) ? now()->subDays(rand(0, 15)) : null,
                'gm_action_at' => $status === HrRequestStatus::Approved ? now()->subDays(rand(0, 10)) : null,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }
        $this->command->info('✓ 12 HR requests created');

        $this->command->info('✅ TestDataSeeder completed successfully!');
    }
}
