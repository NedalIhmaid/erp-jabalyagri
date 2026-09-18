<?php

namespace Tests\Feature;

use App\Enums\HrRequestType;
use App\Models\User;
use App\Notifications\HrRequestApprovedNotification;
use App\Notifications\HrRequestRejectedNotification;
use App\Notifications\HrRequestSubmittedNotification;
use App\Notifications\SalesRequestApprovedNotification;
use App\Notifications\SalesRequestRejectedNotification;
use App\Notifications\SalesRequestReturnedNotification;
use App\Notifications\SalesRequestStageAdvancedNotification;
use App\Notifications\SalesRequestSubmittedNotification;
use App\Services\HrRequestService;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $salesService;

    protected HrRequestService $hrService;

    protected User $engineer;

    protected User $warehouseKeeper;

    protected User $salesManager;

    protected User $purchasingManager;

    protected User $financialManager;

    protected User $generalManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->salesService = app(SalesApprovalService::class);
        $this->hrService = app(HrRequestService::class);

        $this->engineer = User::role('engineer')->firstOrFail();
        $this->warehouseKeeper = User::role('warehouse_keeper')->firstOrFail();
        $this->salesManager = User::role('sales_manager')->firstOrFail();
        $this->purchasingManager = User::role('purchasing_manager')->firstOrFail();
        $this->financialManager = User::role('financial_manager')->firstOrFail();
        $this->generalManager = User::role('general_manager')->firstOrFail();
    }

    public function test_sales_submission_and_stage_advance_notifications_are_sent_once(): void
    {
        Notification::fake();

        $request = $this->submitSalesRequest();

        Notification::assertSentToTimes($this->warehouseKeeper, SalesRequestSubmittedNotification::class, 1);
        Notification::assertCount(1);

        $this->salesService->approve($request, $this->warehouseKeeper);

        Notification::assertSentToTimes($this->financialManager, SalesRequestStageAdvancedNotification::class, 1);
        Notification::assertCount(2);
    }

    public function test_sales_resolution_notifications_are_sent_once(): void
    {
        Notification::fake();

        $rejectedRequest = $this->submitSalesRequest();
        $this->salesService->reject($rejectedRequest, $this->warehouseKeeper, 'Missing stock');

        Notification::assertSentToTimes($this->engineer, SalesRequestRejectedNotification::class, 1);
        Notification::assertCount(2);

        Notification::fake();

        $returnedRequest = $this->submitSalesRequest();
        $this->salesService->returnToEngineer($returnedRequest, $this->warehouseKeeper, 'Need more data');

        Notification::assertSentToTimes($this->engineer, SalesRequestReturnedNotification::class, 1);
        Notification::assertCount(2);

        Notification::fake();

        $approvedRequest = $this->submitSalesRequest();
        $this->salesService->approve($approvedRequest, $this->warehouseKeeper);
        $this->salesService->approve($approvedRequest->fresh(), $this->financialManager);
        $this->salesService->approve($approvedRequest->fresh(), $this->purchasingManager);

        Notification::assertSentToTimes($this->engineer, SalesRequestApprovedNotification::class, 1);
        Notification::assertCount(4);
    }

    public function test_hr_submission_and_manager_approval_notifications_are_sent_once(): void
    {
        Notification::fake();

        $request = $this->submitHrRequest();

        Notification::assertSentToTimes($this->salesManager, HrRequestSubmittedNotification::class, 1);
        Notification::assertCount(1);

        $this->hrService->approveAsManager($request, $this->salesManager, 'Looks good');

        Notification::assertSentToTimes($this->generalManager, HrRequestSubmittedNotification::class, 1);
        Notification::assertCount(2);
    }

    public function test_hr_resolution_notifications_are_sent_once(): void
    {
        Notification::fake();

        $managerRejected = $this->submitHrRequest();
        $this->hrService->rejectAsManager($managerRejected, $this->salesManager, 'Busy period');

        Notification::assertSentToTimes($this->engineer, HrRequestRejectedNotification::class, 1);
        Notification::assertCount(2);

        Notification::fake();

        $approved = $this->submitHrRequest();
        $this->hrService->approveAsManager($approved, $this->salesManager, 'Approved');
        $this->hrService->approveAsGM($approved->fresh(), $this->generalManager, 'Final approval');

        Notification::assertSentToTimes($this->engineer, HrRequestApprovedNotification::class, 1);
        Notification::assertCount(3);

        Notification::fake();

        $gmRejected = $this->submitHrRequest();
        $this->hrService->approveAsManager($gmRejected, $this->salesManager, 'Approved');
        $this->hrService->rejectAsGM($gmRejected->fresh(), $this->generalManager, 'Rejected at final stage');

        Notification::assertSentToTimes($this->engineer, HrRequestRejectedNotification::class, 1);
        Notification::assertCount(3);
    }

    protected function submitSalesRequest()
    {
        return $this->salesService->submit([
            'client_name' => 'Notification Client',
            'client_phone' => '+962790000099',
            'client_address' => 'Amman',
            'payment_method' => 'on_account',
            'engineer_notes' => 'Test request',
            'total_amount' => 100,
        ], $this->engineer);
    }

    protected function submitHrRequest()
    {
        return $this->hrService->submit([
            'type' => HrRequestType::UnpaidLeave->value,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'duration_days' => 2,
            'reason' => 'Need time off',
        ], $this->engineer);
    }
}
