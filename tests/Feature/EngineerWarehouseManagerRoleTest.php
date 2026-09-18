<?php

namespace Tests\Feature;

use App\Enums\SalesRequestStatus;
use App\Filament\Pages\ApprovalDashboard;
use App\Filament\Pages\EngineerDashboard;
use App\Filament\Resources\SalesApprovalRequestResource;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestSubmittedNotification;
use App\Policies\SalesApprovalRequestPolicy;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EngineerWarehouseManagerRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $engineerWarehouseManager;

    protected User $otherEngineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->engineerWarehouseManager = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $this->engineerWarehouseManager->assignRole('warehouse_keeper');

        $this->otherEngineer = User::where('email', 'engineer2@aljabali.com')->firstOrFail();
    }

    public function test_user_can_hold_engineer_and_warehouse_manager_roles_together(): void
    {
        $this->assertTrue($this->engineerWarehouseManager->hasRole('engineer'));
        $this->assertTrue($this->engineerWarehouseManager->hasRole('warehouse_keeper'));
        $this->assertTrue($this->engineerWarehouseManager->hasSalesApprovalRole());
    }

    public function test_dual_role_user_can_access_both_dashboards_and_defaults_to_approvals(): void
    {
        $this->actingAs($this->engineerWarehouseManager);

        $this->assertTrue(EngineerDashboard::canAccess());
        $this->assertTrue(ApprovalDashboard::canAccess());
        $this->assertSame(ApprovalDashboard::getUrl(), AdminPanelProvider::resolveHomeUrl());
    }

    public function test_dual_role_user_sees_and_can_approve_other_engineers_stage_one_requests(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'user_id' => $this->otherEngineer->id,
            'warehouse_keeper_id' => $this->engineerWarehouseManager->id,
            'status' => SalesRequestStatus::InProgress,
            'current_stage' => 1,
        ]);

        app(SalesApprovalService::class)->initializeStages($request);
        $this->actingAs($this->engineerWarehouseManager);

        $this->assertTrue(app(SalesApprovalRequestPolicy::class)->view(
            $this->engineerWarehouseManager,
            $request
        ));
        $this->assertTrue(app(SalesApprovalService::class)->canActOnRequest(
            $request,
            $this->engineerWarehouseManager
        ));
        $this->assertTrue(SalesApprovalRequestResource::getEloquentQuery()->whereKey($request)->exists());
    }

    public function test_engineer_only_user_remains_scoped_to_their_own_requests(): void
    {
        $request = SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineerWarehouseManager->id,
        ]);

        $this->actingAs($this->otherEngineer);

        $this->assertFalse(app(SalesApprovalRequestPolicy::class)->view($this->otherEngineer, $request));
        $this->assertFalse(SalesApprovalRequestResource::getEloquentQuery()->whereKey($request)->exists());
    }

    public function test_dual_role_user_receives_new_warehouse_request_notifications(): void
    {
        Notification::fake();

        app(SalesApprovalService::class)->submit([
            'client_name' => 'Dual-role notification client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
            'warehouse_keeper_id' => $this->engineerWarehouseManager->id,
        ], $this->otherEngineer);

        Notification::assertSentToTimes(
            $this->engineerWarehouseManager,
            SalesRequestSubmittedNotification::class,
            1
        );
    }
}
