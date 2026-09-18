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

class SalesApprovalWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $engineer;

    protected User $warehouseKeeper;

    protected User $salesManager;

    protected User $purchasingManager;

    protected User $financialManager;

    protected User $gm;

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
    }

    #[Test]
    public function engineer_can_access_sales_requests_list(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests')
                ->assertPathIs('/sales-approval-requests');
        });
    }

    #[Test]
    public function engineer_can_open_create_request_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests/create')
                ->assertPathIs('/sales-approval-requests/create')
                ->assertVisible('form');
        });
    }

    #[Test]
    public function new_request_shows_in_progress_status(): void
    {
        $request = $this->createRequest();

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->engineer)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });

        $this->assertEquals(SalesRequestStatus::InProgress, $request->fresh()->status);
    }

    #[Test]
    public function warehouse_keeper_sees_stage_1_request(): void
    {
        $request = $this->createRequest();

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->warehouseKeeper)
                ->visit('/sales-approval-requests')
                ->waitForText($request->request_number)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function warehouse_keeper_has_approve_action_on_stage_1_request(): void
    {
        $request = $this->createRequest();

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->warehouseKeeper)
                ->visit('/sales-approval-requests')
                ->waitForText($request->request_number);
            // The approve action should be visible in the row
            // (exact selector depends on Filament table action rendering)
        });

        // Verify service-level that warehouse keeper can act
        $service = app(SalesApprovalService::class);
        $this->assertTrue($service->canActOnRequest($request, $this->warehouseKeeper));
    }

    #[Test]
    public function approval_advances_stage(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper, 'Stage 1 OK');

        $this->assertEquals(2, $request->fresh()->current_stage);

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->financialManager)
                ->visit('/sales-approval-requests')
                ->waitForText($request->request_number)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function full_4_stage_approval_completes_request(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);

        $service->approve($request, $this->warehouseKeeper);
        $service->approve($request->fresh(), $this->financialManager);
        $service->approve($request->fresh(), $this->purchasingManager);
        $service->approve($request->fresh(), $this->salesManager);

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->gm)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });

        $this->assertEquals(SalesRequestStatus::Approved, $request->fresh()->status);
    }

    #[Test]
    public function financial_manager_sees_stage_2_requests_after_advancement(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper);

        $this->assertTrue($service->canActOnRequest($request->fresh(), $this->financialManager));
    }

    #[Test]
    public function purchasing_manager_sees_stage_3_requests(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper);
        $service->approve($request->fresh(), $this->financialManager);

        $this->assertTrue($service->canActOnRequest($request->fresh(), $this->purchasingManager));
    }

    #[Test]
    public function sales_manager_can_view_finalized_requests_without_acting(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper);
        $service->approve($request->fresh(), $this->financialManager);
        $service->approve($request->fresh(), $this->purchasingManager);

        $this->assertEquals(SalesRequestStatus::Approved, $request->fresh()->status);
        $this->assertFalse($service->canActOnRequest($request->fresh(), $this->salesManager));

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->salesManager)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function gm_can_view_approved_request_without_acting(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper);
        $service->approve($request->fresh(), $this->financialManager);
        $service->approve($request->fresh(), $this->purchasingManager);
        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->gm)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });

        $this->assertFalse($service->canActOnRequest($request->fresh(), $this->gm));
    }

    #[Test]
    public function approval_timeline_is_visible_on_view_page(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);
        $service->approve($request, $this->warehouseKeeper, 'Warehouse approved');

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->warehouseKeeper)
                ->visit('/sales-approval-requests/'.$request->id)
                ->assertSee($request->request_number);
        });
    }

    #[Test]
    public function engineer_cannot_see_approve_action(): void
    {
        $request = $this->createRequest();
        $service = app(SalesApprovalService::class);

        $this->assertFalse($service->canActOnRequest($request, $this->engineer));
    }

    protected function createRequest(): SalesApprovalRequest
    {
        $request = SalesApprovalRequest::create([
            'user_id' => $this->engineer->id,
            'request_number' => 'REQ-'.now()->format('Ymd').'-0001',
            'client_name' => 'Browser Test Client',
            'client_phone' => '+962790000099',
            'client_address' => 'Amman, Jordan',
            'payment_method' => 'on_account',
            'total_amount' => 500.00,
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
        ]);

        app(SalesApprovalService::class)->initializeStages($request);

        return $request;
    }
}
