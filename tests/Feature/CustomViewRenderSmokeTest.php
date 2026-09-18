<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomViewRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $engineer;
    protected User $warehouseKeeper;
    protected User $generalManager;
    protected User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->generalManager = $this->makeUser('gm@aljabali.com', 'general_manager');
        $this->salesManager = $this->makeUser('sales@aljabali.com', 'sales_manager');
        $this->warehouseKeeper = $this->makeUser('warehouse@aljabali.com', 'warehouse_keeper');
        $this->engineer = $this->makeUser('engineer@aljabali.com', 'engineer');
    }

    public function test_welcome_page_renders(): void
    {
        $this->get('/welcome')
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertDontSee('Server Error');
    }

    public function test_engineer_dashboard_renders(): void
    {
        $this->actingAs($this->engineer)
            ->get('/engineer-dashboard')
            ->assertOk()
            ->assertDontSee('Server Error');
    }

    public function test_approval_dashboard_renders(): void
    {
        $this->actingAs($this->warehouseKeeper)
            ->get('/approval-dashboard')
            ->assertOk()
            ->assertDontSee('Server Error');
    }

    public function test_general_manager_dashboard_renders(): void
    {
        $this->actingAs($this->generalManager)
            ->get('/general-manager-dashboard')
            ->assertOk()
            ->assertDontSee('Server Error');
    }

    public function test_sales_reports_renders(): void
    {
        $this->actingAs($this->salesManager)
            ->get('/sales-reports')
            ->assertOk()
            ->assertDontSee('Server Error');
    }

    public function test_hr_reports_renders(): void
    {
        $this->actingAs($this->generalManager)
            ->get('/hr-reports')
            ->assertOk()
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
