<?php

namespace Tests\Feature;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Enums\SalesRequestStatus;
use App\Models\AuditLog;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\HrRequestService;
use App\Services\SalesApprovalService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $salesApprovalService;

    protected HrRequestService $hrRequestService;

    protected AuditLogger $auditLogger;

    protected User $engineer;

    protected User $warehouseKeeper;

    protected User $salesManager;

    protected User $generalManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $this->salesApprovalService = app(SalesApprovalService::class);
        $this->hrRequestService = app(HrRequestService::class);
        $this->auditLogger = app(AuditLogger::class);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $this->warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->firstOrFail();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->firstOrFail();
        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();

        $this->engineer->update(['hire_date' => now()->subYear()->toDateString()]);
    }

    public function test_sales_approval_writes_exactly_one_audit_row(): void
    {
        $request = $this->makeSalesRequest();

        $this->salesApprovalService->approve($request, $this->warehouseKeeper, 'Stock verified');

        $this->assertSame(1, AuditLog::count());

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('sales', $log->log_name);
        $this->assertSame('sales.stage.approved', $log->description);
        $this->assertSame($this->warehouseKeeper->id, $log->causer_id);
        $this->assertSame(2, $log->getExtraProperty('after.current_stage'));
    }

    public function test_sales_rejection_writes_exactly_one_audit_row(): void
    {
        $request = $this->makeSalesRequest();

        $this->salesApprovalService->reject($request, $this->warehouseKeeper, 'Out of stock');

        $this->assertSame(1, AuditLog::count());

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('sales.request.rejected', $log->description);
        $this->assertSame('cancelled', $log->getExtraProperty('after.status'));
        $this->assertStringNotContainsString('Out of stock', json_encode($log->properties->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_sales_return_writes_exactly_one_audit_row(): void
    {
        $request = $this->makeSalesRequest();

        $this->salesApprovalService->returnToEngineer($request, $this->warehouseKeeper, 'Missing info');

        $this->assertSame(1, AuditLog::count());

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('sales.request.returned', $log->description);
        $this->assertSame('returned', $log->getExtraProperty('after.status'));
        $this->assertStringNotContainsString('Missing info', json_encode($log->properties->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_sales_resubmission_writes_exactly_one_audit_row(): void
    {
        $request = $this->makeSalesRequest([
            'status' => SalesRequestStatus::Returned,
            'returned_by' => $this->warehouseKeeper->id,
        ]);

        $this->salesApprovalService->resetStages($request, $this->engineer);

        $this->assertSame(1, AuditLog::count());

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('sales.request.resubmitted', $log->description);
        $this->assertSame('in_progress', $log->getExtraProperty('after.status'));
    }

    public function test_hr_submission_and_manager_decisions_are_logged_without_sensitive_text(): void
    {
        $request = $this->hrRequestService->submit([
            'type' => HrRequestType::AnnualLeave->value,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'duration_days' => 2,
            'reason' => 'Private family matter',
        ], $this->engineer);

        $submitLog = AuditLog::query()->latest()->firstOrFail();
        $this->assertSame('hr.request.submitted', $submitLog->description);
        $this->assertStringNotContainsString('Private family matter', json_encode($submitLog->properties->toArray(), JSON_THROW_ON_ERROR));

        AuditLog::query()->delete();

        $this->hrRequestService->approveAsManager($request->fresh(), $this->salesManager, 'Approved by manager');

        $this->assertSame(1, AuditLog::count());
        $managerLog = AuditLog::query()->firstOrFail();
        $this->assertSame('hr.request.manager_approved', $managerLog->description);
        $this->assertStringNotContainsString('Approved by manager', json_encode($managerLog->properties->toArray(), JSON_THROW_ON_ERROR));

        AuditLog::query()->delete();

        $request->update([
            'status' => HrRequestStatus::Pending,
            'manager_action_at' => null,
            'manager_comments' => null,
        ]);

        $this->hrRequestService->rejectAsManager($request->fresh(), $this->salesManager, 'Busy period');

        $this->assertSame(1, AuditLog::count());
        $rejectionLog = AuditLog::query()->firstOrFail();
        $this->assertSame('hr.request.manager_rejected', $rejectionLog->description);
        $this->assertStringNotContainsString('Busy period', json_encode($rejectionLog->properties->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_hr_gm_approval_and_rejection_create_hr_and_leave_entries(): void
    {
        $request = HrRequest::create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'duration_days' => 2,
            'status' => HrRequestStatus::ManagerApproved,
        ]);

        $this->hrRequestService->approveAsGM($request, $this->generalManager, 'Final approval');

        $this->assertSame(2, AuditLog::count());
        $this->assertSame(
            ['hr.request.approved', 'leave.balance.deducted'],
            AuditLog::query()->orderBy('description')->pluck('description')->all(),
        );

        $payload = AuditLog::query()->pluck('properties')->map(fn ($properties) => json_encode($properties->toArray(), JSON_THROW_ON_ERROR))->implode("\n");
        $this->assertStringNotContainsString('Final approval', $payload);

        AuditLog::query()->delete();

        $this->hrRequestService->rejectAsGM($request->fresh(), $this->generalManager, 'Reverse approval');

        $this->assertSame(2, AuditLog::count());
        $this->assertSame(
            ['hr.request.rejected', 'leave.balance.restored'],
            AuditLog::query()->orderBy('description')->pluck('description')->all(),
        );

        $payload = AuditLog::query()->pluck('properties')->map(fn ($properties) => json_encode($properties->toArray(), JSON_THROW_ON_ERROR))->implode("\n");
        $this->assertStringNotContainsString('Reverse approval', $payload);
    }

    protected function makeSalesRequest(array $overrides = []): SalesApprovalRequest
    {
        $request = SalesApprovalRequest::create(array_merge([
            'request_number' => 'SAR-AUDIT-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => $this->engineer->id,
            'client_name' => 'Audit Client',
            'client_phone' => '+962790000111',
            'client_address' => 'Amman',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ], $overrides));

        $this->salesApprovalService->initializeStages($request);

        return $request->fresh();
    }
}
