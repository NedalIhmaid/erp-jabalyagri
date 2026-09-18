<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AdminSafetyService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSafetyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminSafetyService $adminSafetyService;

    protected User $generalManager;

    protected User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $this->adminSafetyService = app(AdminSafetyService::class);
        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->firstOrFail();
    }

    public function test_cannot_delete_yourself(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureUserDeletionAllowed($this->generalManager, $this->generalManager);
    }

    public function test_cannot_delete_the_last_active_general_manager(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureUserDeletionAllowed($this->generalManager, $this->salesManager);
    }

    public function test_cannot_deactivate_yourself(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureUserUpdateAllowed(
            $this->generalManager,
            ['is_active' => false, 'roles' => $this->generalManager->roles->modelKeys()],
            $this->generalManager,
        );
    }

    public function test_cannot_remove_your_own_general_manager_role(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureUserUpdateAllowed(
            $this->generalManager,
            ['is_active' => true, 'roles' => [Role::where('name', 'sales_manager')->firstOrFail()->id]],
            $this->generalManager,
        );
    }

    public function test_cannot_remove_the_last_general_manager_role_from_an_active_user(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureUserUpdateAllowed(
            $this->generalManager,
            ['is_active' => true, 'roles' => [Role::where('name', 'financial_manager')->firstOrFail()->id]],
            $this->salesManager,
        );
    }

    public function test_cannot_delete_core_roles(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureRoleDeletionAllowed(
            Role::where('name', 'general_manager')->firstOrFail(),
        );
    }

    public function test_cannot_rename_core_roles(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureRoleUpdateAllowed(
            Role::where('name', 'general_manager')->firstOrFail(),
            ['name' => 'renamed_general_manager'],
        );
    }

    public function test_cannot_delete_roles_that_are_assigned_to_users(): void
    {
        $assignedRole = Role::create(['name' => 'assigned_role', 'guard_name' => 'web']);
        $this->salesManager->assignRole($assignedRole);

        $this->expectException(Halt::class);

        $this->adminSafetyService->ensureRoleDeletionAllowed($assignedRole);
    }

    public function test_rejects_leave_balances_when_annual_used_exceeds_total(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->validateLeaveBalanceData([
            'annual_total' => 10,
            'annual_used' => 11,
            'sick_total' => 14,
            'sick_used' => 1,
        ]);
    }

    public function test_rejects_leave_balances_when_sick_used_exceeds_total(): void
    {
        $this->expectException(Halt::class);

        $this->adminSafetyService->validateLeaveBalanceData([
            'annual_total' => 14,
            'annual_used' => 2,
            'sick_total' => 5,
            'sick_used' => 6,
        ]);
    }
}
