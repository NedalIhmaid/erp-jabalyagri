<?php

namespace Tests\Browser\HrRequest;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\User;
use App\Services\HrRequestService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class HrRequestWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $engineer;
    protected User $salesManager;
    protected User $gm;
    protected HrRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->first();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->first();
        $this->gm = User::where('email', 'gm@aljabali.com')->first();
        $this->service = app(HrRequestService::class);
    }

    #[Test]
    public function employee_can_access_hr_requests_list(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/hr-requests')
                ->assertPathIs('/hr-requests');
        });
    }

    #[Test]
    public function employee_can_open_create_hr_request_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->engineer)
                ->visit('/hr-requests/create')
                ->assertPathIs('/hr-requests/create')
                ->assertVisible('form');
        });
    }

    #[Test]
    public function new_hr_request_has_pending_status(): void
    {
        $request = $this->createHrRequest();

        $this->assertEquals(HrRequestStatus::Pending, $request->status);
    }

    #[Test]
    public function hr_request_appears_in_manager_list(): void
    {
        $request = $this->createHrRequest();

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->salesManager)
                ->visit('/hr-requests')
                ->waitForText($this->engineer->name)
                ->assertSee($this->engineer->name);
        });
    }

    #[Test]
    public function manager_can_approve_pending_request(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager, 'Approved');

        $this->assertEquals(HrRequestStatus::ManagerApproved, $request->fresh()->status);
    }

    #[Test]
    public function gm_cannot_approve_before_manager(): void
    {
        $request = $this->createHrRequest();

        $this->assertFalse($this->service->canApproveAsGM($request, $this->gm));
    }

    #[Test]
    public function gm_can_approve_after_manager_approval(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager, 'OK');
        $this->service->approveAsGM($request->fresh(), $this->gm, 'Final OK');

        $this->assertEquals(HrRequestStatus::Approved, $request->fresh()->status);
    }

    #[Test]
    public function manager_rejection_cancels_request(): void
    {
        $request = $this->createHrRequest();
        $this->service->rejectAsManager($request, $this->salesManager, 'Not approved');

        $this->assertEquals(HrRequestStatus::Rejected, $request->fresh()->status);
    }

    #[Test]
    public function gm_rejection_cancels_request(): void
    {
        $request = $this->createHrRequest();
        $this->service->approveAsManager($request, $this->salesManager);
        $this->service->rejectAsGM($request->fresh(), $this->gm, 'GM declined');

        $this->assertEquals(HrRequestStatus::Rejected, $request->fresh()->status);
    }

    #[Test]
    public function gm_sees_hr_requests_list(): void
    {
        $request = $this->createHrRequest();

        $this->browse(function (Browser $browser) use ($request) {
            $browser->loginAs($this->gm)
                ->visit('/hr-requests')
                ->assertPathIs('/hr-requests');
        });
    }

    #[Test]
    public function annual_leave_request_can_be_created(): void
    {
        $request = $this->createHrRequest(HrRequestType::AnnualLeave);
        $this->assertEquals(HrRequestType::AnnualLeave, $request->type);
    }

    protected function createHrRequest(HrRequestType $type = HrRequestType::SickLeave): HrRequest
    {
        return HrRequest::create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => $type,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'duration_days' => 2,
            'reason' => 'Browser test HR request',
            'status' => HrRequestStatus::Pending,
        ]);
    }
}
