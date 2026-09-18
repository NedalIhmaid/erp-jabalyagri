<?php

namespace Tests\Feature;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Enums\SalesRequestStatus;
use App\Models\HrRequest;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SalesApprovalRequest;
use App\Models\SalesRequestItem;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminResourceCustomViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $generalManager;
    protected User $engineer;
    protected User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(ProductCatalogSeeder::class);

        $this->generalManager = $this->makeUser('gm@aljabali.com', 'general_manager');
        $this->engineer = $this->makeUser('engineer@aljabali.com', 'engineer');
        $this->salesManager = $this->makeUser('sales@aljabali.com', 'sales_manager');
    }

    public function test_users_create_and_edit_pages_use_custom_views(): void
    {
        $this->actingAs($this->generalManager)
            ->get('/users/create')
            ->assertOk()
            ->assertSee('custom-user-create-view', false)
            ->assertDontSee('Server Error');

        $this->actingAs($this->generalManager)
            ->get("/users/{$this->engineer->id}/edit")
            ->assertOk()
            ->assertSee('custom-user-edit-view', false)
            ->assertDontSee('Server Error');
    }

    public function test_role_pages_use_custom_views(): void
    {
        $role = Role::where('name', 'engineer')->firstOrFail();

        $this->actingAs($this->generalManager)
            ->get('/shield/roles')
            ->assertOk()
            ->assertSee('custom-role-list-view', false)
            ->assertDontSee('Server Error');

        $this->actingAs($this->generalManager)
            ->get('/shield/roles/create')
            ->assertOk()
            ->assertSee('custom-role-create-view', false)
            ->assertDontSee('Server Error');

        $this->actingAs($this->generalManager)
            ->get("/shield/roles/{$role->id}/edit")
            ->assertOk()
            ->assertSee('custom-role-edit-view', false)
            ->assertDontSee('Server Error');
    }

    public function test_hr_request_view_uses_custom_view(): void
    {
        $request = HrRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Pending,
        ]);

        $this->actingAs($this->generalManager)
            ->get("/hr-requests/{$request->id}")
            ->assertOk()
            ->assertSee('custom-hr-request-view', false)
            ->assertDontSee('Server Error');
    }

    public function test_sales_request_create_and_edit_pages_use_custom_views(): void
    {
        $product = Product::with('productUnits')->firstOrFail();
        $productUnit = $product->productUnits->first();

        $this->actingAs($this->engineer)
            ->get('/sales-approval-requests/create')
            ->assertOk()
            ->assertSee('custom-sales-create-view', false)
            ->assertDontSee('Server Error');

        $request = SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'payment_method' => 'on_account',
            'status' => SalesRequestStatus::Returned,
            'current_stage' => 1,
        ]);

        SalesRequestItem::create([
            'sales_approval_request_id' => $request->id,
            'product_id' => $product->id,
            'product_unit_id' => $productUnit?->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit' => $productUnit?->label ?? 'وحدة',
            'unit_price' => (float) ($productUnit?->price ?? 0),
            'total_price' => round(2 * (float) ($productUnit?->price ?? 0), 2),
        ]);

        $this->actingAs($this->engineer)
            ->get("/sales-approval-requests/{$request->id}/edit")
            ->assertOk()
            ->assertSee('custom-sales-edit-view', false)
            ->assertDontSee('Server Error');
    }

    protected function makeUser(string $email, string $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
            'locale' => 'ar',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
