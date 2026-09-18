<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    /**
     * Permission matrix per role.
     * All actions in SalesApprovalService/HrRequestService are gated by service-layer checks,
     * so Shield only needs to control resource/page visibility.
     */
    private array $matrix = [
        'employee' => [
            // Owns their own HR requests only — no other module access
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
        ],

        'engineer' => [
            // Owns their visits and requests
            'ViewAny:DailyVisit', 'View:DailyVisit', 'Create:DailyVisit', 'Update:DailyVisit', 'Delete:DailyVisit',
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest', 'Create:SalesApprovalRequest', 'Update:SalesApprovalRequest',
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
            // Widgets on engineer dashboard
            'View:DashboardStatsWidget', 'View:EngineerDashboardStats',
        ],

        'warehouse_keeper' => [
            // Approves stage 1 sales requests
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest',
            // Maintains product catalog details
            'ViewAny:Product', 'View:Product', 'Update:Product',
            // Owns their HR requests
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
            // Approval dashboard
            'View:ApprovalDashboard',
            // Widgets
            'View:DashboardStatsWidget', 'View:PendingApprovalsWidget', 'View:SalesStatsOverview', 'View:SalesChart',
        ],

        'sales_manager' => [
            // View-only access to sales requests
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest',
            // Views direct-report engineer visits
            'ViewAny:DailyVisit', 'View:DailyVisit',
            // Approves stage 1 HR requests + creates own
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
            // Approval dashboard + reports
            'View:ApprovalDashboard', 'View:SalesReports',
            // Widgets
            'View:DashboardStatsWidget', 'View:PendingApprovalsWidget', 'View:SalesStatsOverview', 'View:SalesChart',
            'View:HrRequestsOverview',
        ],

        'purchasing_manager' => [
            // Approves stage 3 sales requests
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest',
            // Owns their HR requests
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
            // Approval dashboard
            'View:ApprovalDashboard',
            // Widgets
            'View:DashboardStatsWidget', 'View:PendingApprovalsWidget', 'View:SalesStatsOverview', 'View:SalesChart',
        ],

        'financial_manager' => [
            // Approves stage 2 sales requests
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest',
            // Owns their HR requests
            'ViewAny:HrRequest', 'View:HrRequest', 'Create:HrRequest', 'Update:HrRequest',
            // Approval dashboard
            'View:ApprovalDashboard',
            // Widgets
            'View:DashboardStatsWidget', 'View:PendingApprovalsWidget', 'View:SalesStatsOverview', 'View:SalesChart',
            'View:HrRequestsOverview',
        ],

        'general_manager' => [
            // View-only access to sales requests
            'ViewAny:SalesApprovalRequest', 'View:SalesApprovalRequest',
            // Approves stage 2 HR requests
            'ViewAny:HrRequest', 'View:HrRequest',
            // View daily visits for reports
            'ViewAny:DailyVisit', 'View:DailyVisit',
            // Full user management
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User',
            // Role management
            'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role', 'DeleteAny:Role',
            // Reports and GM dashboard
            'View:GeneralManagerDashboard', 'View:SalesReports', 'View:HrReports', 'View:AuditLog',
            // Widgets
            'View:DashboardStatsWidget', 'View:GMOverviewWidget', 'View:SalesStatsOverview', 'View:SalesChart',
            'View:HrRequestsOverview',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->matrix as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->command->warn("Role [{$roleName}] not found — skipping.");

                continue;
            }

            $permissionModels = collect($permissions)->map(
                fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
            );

            $role->syncPermissions($permissionModels);

            $this->command->info('Assigned '.$permissionModels->count()." permissions to [{$roleName}].");
        }
    }
}
