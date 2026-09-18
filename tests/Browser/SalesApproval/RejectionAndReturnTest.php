<?php

namespace Tests\Browser\SalesApproval;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class RejectionAndReturnTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $engineer;

    protected User $warehouseKeeper;

    protected User $salesManager;

    protected User $purchasingManager;

    protected User $financialManager;

    protected User $gm;

    protected SalesApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->first();
        $this->warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->first();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->first();
        $this->purchasingManager = User::where('email', 'purchasing@aljabali.com')->first();
        $this->financialManager = User::where('email', 'finance@aljabali.com')->first();
        $this->gm = User::where('email', 'gm@aljabali.com')->first();
        $this->service = app(SalesApprovalService::class);
    }

    #[Test]
    public function rejection_at_stage_1_cancels_request(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Not acceptable');

        $this->assertEquals(SalesRequestStatus::Cancelled, $request->fresh()->status);

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function rejection_at_stage_2_cancels_request(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper);
        $this->service->reject($request->fresh(), $this->financialManager, 'Stage 2 rejected');

        $this->assertEquals(SalesRequestStatus::Cancelled, $request->fresh()->status);
    }

    #[Test]
    public function rejection_at_stage_3_cancels_request(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper);
        $this->service->approve($request->fresh(), $this->financialManager);
        $this->service->reject($request->fresh(), $this->purchasingManager, 'Stage 3 rejected');

        $this->assertEquals(SalesRequestStatus::Cancelled, $request->fresh()->status);
    }

    #[Test]
    public function sales_manager_cannot_act_after_final_approval(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper);
        $this->service->approve($request->fresh(), $this->financialManager);
        $this->service->approve($request->fresh(), $this->purchasingManager);
        $this->assertEquals(SalesRequestStatus::Approved, $request->fresh()->status);
        $this->assertFalse($this->service->canActOnRequest($request->fresh(), $this->salesManager));
    }

    #[Test]
    public function cancelled_request_cannot_be_acted_on(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Rejected');

        $this->assertFalse($this->service->canActOnRequest($request->fresh(), $this->warehouseKeeper));
        $this->assertFalse($this->service->canActOnRequest($request->fresh(), $this->salesManager));
    }

    #[Test]
    public function warehouse_keeper_can_return_request(): void
    {
        $request = $this->createRequest();
        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Please revise');

        $this->assertEquals(SalesRequestStatus::Returned, $request->fresh()->status);

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function sales_manager_cannot_return_request(): void
    {
        $request = $this->createRequest();
        $this->service->approve($request, $this->warehouseKeeper);

        $this->assertFalse($this->service->canReturnRequest($request->fresh(), $this->salesManager));
    }

    #[Test]
    public function engineer_can_view_returned_request(): void
    {
        $request = $this->createRequest();
        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Needs revision');

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests')
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function resubmitted_request_restarts_at_stage_1(): void
    {
        $request = $this->createRequest();
        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Returned');
        $this->service->resetStages($request->fresh());

        $reset = $request->fresh();
        $this->assertEquals(1, $reset->current_stage);
        $this->assertEquals(SalesRequestStatus::InProgress, $reset->status);
    }

    #[Test]
    public function rejection_reason_is_stored(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Invalid documentation');

        $this->assertEquals('Invalid documentation', $request->fresh()->rejection_reason);
    }

    #[Test]
    public function rejected_by_field_is_set_on_rejection(): void
    {
        $request = $this->createRequest();
        $this->service->reject($request, $this->warehouseKeeper, 'Reason');

        $this->assertEquals($this->warehouseKeeper->id, $request->fresh()->rejected_by);
    }

    #[Test]
    public function returned_by_field_is_set_on_return(): void
    {
        $request = $this->createRequest();
        $this->service->returnToEngineer($request, $this->warehouseKeeper, 'Return reason');

        $this->assertEquals($this->warehouseKeeper->id, $request->fresh()->returned_by);
    }

    protected function createRequest(): SalesApprovalRequest
    {
        $request = SalesApprovalRequest::create([
            'user_id' => $this->engineer->id,
            'request_number' => 'REQ-'.now()->format('Ymd').'-'.rand(1000, 9999),
            'client_name' => 'Rejection Test Client',
            'client_phone' => '+962790000099',
            'client_address' => 'Amman, Jordan',
            'payment_method' => 'on_account',
            'total_amount' => 300.00,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        app(SalesApprovalService::class)->initializeStages($request);

        return $request;
    }
}
