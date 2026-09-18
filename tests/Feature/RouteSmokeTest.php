<?php

namespace Tests\Feature;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Enums\SalesRequestStatus;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\LeaveBalance;
use App\Models\Product;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $engineer;
    protected User $warehouseKeeper;
    protected User $salesManager;
    protected User $generalManager;
    protected DailyVisit $dailyVisit;
    protected HrRequest $hrRequest;
    protected LeaveBalance $leaveBalance;
    protected Product $product;
    protected Role $role;
    protected SalesApprovalRequest $salesRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(ProductCatalogSeeder::class);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $this->warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->firstOrFail();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->firstOrFail();
        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
        $this->product = Product::with('productUnits')->firstOrFail();
        $this->role = Role::where('name', 'engineer')->firstOrFail();

        $this->dailyVisit = DailyVisit::factory()->create([
            'user_id' => $this->engineer->id,
        ]);

        $this->hrRequest = HrRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
        ]);

        $this->leaveBalance = LeaveBalance::create([
            'user_id' => $this->engineer->id,
            'year' => now()->year,
        ]);

        $this->salesRequest = SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'payment_method' => 'on_account',
            'status' => SalesRequestStatus::Returned,
            'current_stage' => 1,
        ]);

        $productUnit = $this->product->productUnits->first();

        SalesRequestItem::create([
            'sales_approval_request_id' => $this->salesRequest->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $productUnit?->id,
            'product_name' => $this->product->name,
            'quantity' => 2,
            'unit' => $productUnit?->label ?? 'وحدة',
            'unit_price' => (float) ($productUnit?->price ?? 0),
            'total_price' => round(2 * (float) ($productUnit?->price ?? 0), 2),
        ]);
    }

    #[DataProvider('routeProvider')]
    public function test_route_responds_without_server_error(string $label, string $userProperty, string $pathKey, int $expectedStatus = 200): void
    {
        /** @var User $user */
        $user = $this->{$userProperty};
        $path = $this->paths()[$pathKey];

        $this->actingAs($user)
            ->get($path)
            ->assertStatus($expectedStatus)
            ->assertDontSee('Server Error');
    }

    public function test_welcome_route_renders_for_guests(): void
    {
        $this->get('/welcome')
            ->assertOk()
            ->assertDontSee('Server Error');
    }

    public static function routeProvider(): array
    {
        return [
            ['dashboard', 'generalManager', 'dashboard', 302],
            ['approval dashboard', 'warehouseKeeper', 'approval-dashboard'],
            ['audit log', 'generalManager', 'audit-log'],
            ['daily visits index', 'engineer', 'daily-visits.index'],
            ['daily visits create', 'engineer', 'daily-visits.create'],
            ['daily visits view', 'engineer', 'daily-visits.view'],
            ['daily visits edit', 'engineer', 'daily-visits.edit'],
            ['engineer dashboard', 'engineer', 'engineer-dashboard'],
            ['general manager dashboard', 'generalManager', 'general-manager-dashboard'],
            ['hr reports', 'generalManager', 'hr-reports'],
            ['hr requests index', 'engineer', 'hr-requests.index'],
            ['hr requests create', 'engineer', 'hr-requests.create'],
            ['hr requests view', 'generalManager', 'hr-requests.view'],
            ['hr requests edit', 'engineer', 'hr-requests.edit'],
            ['leave balances index', 'salesManager', 'leave-balances.index'],
            ['leave balances edit', 'salesManager', 'leave-balances.edit'],
            ['locale switch', 'engineer', 'locale.switch', 302],
            ['products index', 'generalManager', 'products.index'],
            ['products create', 'generalManager', 'products.create'],
            ['products view', 'generalManager', 'products.view'],
            ['products edit', 'generalManager', 'products.edit'],
            ['daily visits export', 'generalManager', 'reports.daily-visits.export'],
            ['hr export', 'generalManager', 'reports.hr.export'],
            ['sales export', 'generalManager', 'reports.sales.export'],
            ['sales requests index', 'engineer', 'sales-approval-requests.index'],
            ['sales requests create', 'engineer', 'sales-approval-requests.create'],
            ['sales requests view', 'engineer', 'sales-approval-requests.view'],
            ['sales requests edit', 'engineer', 'sales-approval-requests.edit'],
            ['sales reports', 'salesManager', 'sales-reports'],
            ['sales request pdf', 'engineer', 'sales-requests.pdf'],
            ['settings', 'engineer', 'settings'],
            ['roles index', 'generalManager', 'shield.roles.index'],
            ['roles create', 'generalManager', 'shield.roles.create'],
            ['roles view', 'generalManager', 'shield.roles.view'],
            ['roles edit', 'generalManager', 'shield.roles.edit'],
            ['users index', 'generalManager', 'users.index'],
            ['users create', 'generalManager', 'users.create'],
            ['users view', 'generalManager', 'users.view'],
            ['users edit', 'generalManager', 'users.edit'],
        ];
    }

    protected function paths(): array
    {
        return [
            'dashboard' => '/',
            'approval-dashboard' => '/approval-dashboard',
            'audit-log' => '/audit-log',
            'daily-visits.index' => '/daily-visits',
            'daily-visits.create' => '/daily-visits/create',
            'daily-visits.view' => "/daily-visits/{$this->dailyVisit->id}",
            'daily-visits.edit' => "/daily-visits/{$this->dailyVisit->id}/edit",
            'engineer-dashboard' => '/engineer-dashboard',
            'general-manager-dashboard' => '/general-manager-dashboard',
            'hr-reports' => '/hr-reports',
            'hr-requests.index' => '/hr-requests',
            'hr-requests.create' => '/hr-requests/create',
            'hr-requests.view' => "/hr-requests/{$this->hrRequest->id}",
            'hr-requests.edit' => "/hr-requests/{$this->hrRequest->id}/edit",
            'leave-balances.index' => '/leave-balances',
            'leave-balances.edit' => "/leave-balances/{$this->leaveBalance->id}/edit",
            'locale.switch' => '/locale/switch',
            'products.index' => '/products',
            'products.create' => '/products/create',
            'products.view' => "/products/{$this->product->id}",
            'products.edit' => "/products/{$this->product->id}/edit",
            'reports.daily-visits.export' => '/reports/daily-visits/export',
            'reports.hr.export' => '/reports/hr/export',
            'reports.sales.export' => '/reports/sales/export',
            'sales-approval-requests.index' => '/sales-approval-requests',
            'sales-approval-requests.create' => '/sales-approval-requests/create',
            'sales-approval-requests.view' => "/sales-approval-requests/{$this->salesRequest->id}",
            'sales-approval-requests.edit' => "/sales-approval-requests/{$this->salesRequest->id}/edit",
            'sales-reports' => '/sales-reports',
            'sales-requests.pdf' => "/sales-requests/{$this->salesRequest->id}/pdf",
            'settings' => '/settings',
            'shield.roles.index' => '/shield/roles',
            'shield.roles.create' => '/shield/roles/create',
            'shield.roles.view' => "/shield/roles/{$this->role->id}",
            'shield.roles.edit' => "/shield/roles/{$this->role->id}/edit",
            'users.index' => '/users',
            'users.create' => '/users/create',
            'users.view' => "/users/{$this->engineer->id}",
            'users.edit' => "/users/{$this->engineer->id}/edit",
        ];
    }
}
