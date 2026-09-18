<?php

namespace Tests\Feature;

use App\Enums\HrRequestType;
use App\Models\AuditLog;
use App\Models\HrRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogger $auditLogger;

    protected LeaveService $leaveService;

    protected User $generalManager;

    protected User $salesManager;

    protected User $financialManager;

    protected User $purchasingManager;

    protected User $warehouseKeeper;

    protected User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(UserSeeder::class);

        $this->auditLogger = app(AuditLogger::class);
        $this->leaveService = app(LeaveService::class);

        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->firstOrFail();
        $this->financialManager = User::where('email', 'finance@aljabali.com')->firstOrFail();
        $this->purchasingManager = User::where('email', 'purchasing@aljabali.com')->firstOrFail();
        $this->warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->firstOrFail();
        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
    }

    public function test_only_general_manager_can_access_audit_log_page(): void
    {
        $this->actingAs($this->generalManager)
            ->get('/audit-log')
            ->assertOk();

        foreach ([
            $this->salesManager,
            $this->financialManager,
            $this->purchasingManager,
            $this->warehouseKeeper,
            $this->engineer,
        ] as $user) {
            $this->actingAs($user)
                ->get('/audit-log')
                ->assertForbidden();
        }
    }

    public function test_audit_log_shows_links_only_when_viewer_can_access_the_subject(): void
    {
        $balance = $this->leaveService->getOrCreateBalance($this->engineer);
        $before = $this->auditLogger->leaveBalanceState($balance);
        $balance->update(['annual_used' => 1.0]);
        $this->auditLogger->logLeaveBalanceAdjusted($balance->fresh('user'), $this->generalManager, $before);

        $this->actingAs($this->salesManager)
            ->get('/audit-log')
            ->assertForbidden();

        // The GM is the only role that can view the audit log, but leave balances
        // are now restricted to the sales manager — so the subject link is suppressed
        // even though the audited entry (and the employee name) is still shown.
        $this->actingAs($this->generalManager)
            ->get('/audit-log')
            ->assertOk()
            ->assertSee($balance->user->name)
            ->assertDontSee("/leave-balances/{$balance->id}/edit", false);
    }

    public function test_admin_and_leave_audit_payloads_capture_safe_before_after_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Audit User',
            'email' => 'audit-user@example.test',
            'is_active' => true,
            'locale' => 'en',
            'manager_id' => $this->salesManager->id,
            'hire_date' => now()->toDateString(),
        ]);
        $user->assignRole('engineer');

        $this->auditLogger->logUserCreated($user->fresh('roles'), $this->generalManager);

        $beforeUser = $this->auditLogger->userState($user->fresh('roles'));
        $user->update(['locale' => 'ar', 'is_active' => false]);
        $user->syncRoles(['warehouse_keeper']);
        $this->auditLogger->logUserUpdated($user->fresh('roles'), $this->generalManager, $beforeUser);

        $userLogs = AuditLog::query()
            ->where('description', 'like', 'admin.user.%')
            ->orderBy('description')
            ->get();

        $this->assertSame(['admin.user.created', 'admin.user.updated'], $userLogs->pluck('description')->all());
        $this->assertSame(['engineer'], $userLogs->last()->getExtraProperty('before.roles'));
        $this->assertSame(['warehouse_keeper'], $userLogs->last()->getExtraProperty('after.roles'));

        $role = Role::create(['name' => 'audit_role', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View:AuditLog', 'guard_name' => 'web']));
        $this->auditLogger->logRoleCreated($role->fresh('permissions'), $this->generalManager);

        $beforeRole = $this->auditLogger->roleState($role->fresh('permissions'));
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View:SalesReports', 'guard_name' => 'web']));
        $this->auditLogger->logRoleUpdated($role->fresh('permissions'), $this->generalManager, $beforeRole);

        $roleLogs = AuditLog::query()
            ->where('description', 'like', 'admin.role.%')
            ->orderBy('description')
            ->get();

        $this->assertSame(['admin.role.created', 'admin.role.updated'], $roleLogs->pluck('description')->all());
        $this->assertSame(1, $roleLogs->last()->getExtraProperty('before.permission_count'));
        $this->assertSame(2, $roleLogs->last()->getExtraProperty('after.permission_count'));

        $request = HrRequest::create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'duration_days' => 1,
            'status' => 'approved',
        ]);

        $balance = $this->leaveService->getOrCreateBalance($this->engineer);
        $beforeLeave = $this->auditLogger->leaveBalanceState($balance);
        $balance->update(['annual_used' => 3.0]);
        $this->auditLogger->logLeaveBalanceAdjusted(
            $balance->fresh('user'),
            $this->generalManager,
            $beforeLeave,
            context: [
                'related_hr_request_id' => $request->id,
                'related_hr_request_label' => "#{$request->id}",
            ],
        );

        $leaveLog = AuditLog::query()->where('description', 'leave.balance.adjusted')->latest()->firstOrFail();
        $this->assertEquals(0.0, $leaveLog->getExtraProperty('before.annual_used'));
        $this->assertEquals(3.0, $leaveLog->getExtraProperty('after.annual_used'));
        $this->assertSame($request->id, $leaveLog->getExtraProperty('context.related_hr_request_id'));
    }
}
