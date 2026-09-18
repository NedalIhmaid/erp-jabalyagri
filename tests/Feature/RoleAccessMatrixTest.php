<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(UserSeeder::class);

        Product::factory()->create();
    }

    #[DataProvider('matrixProvider')]
    public function test_role_access_matrix(string $email, string $path, int $expected): void
    {
        $user = User::where('email', $email)->firstOrFail();

        $this->actingAs($user)
            ->get($path)
            ->assertStatus($expected);
    }

    public static function matrixProvider(): array
    {
        $ok = 200;
        $forbidden = 403;
        $redirect = 302;

        return [
            // engineer@ — field engineer
            'engineer: engineer-dashboard' => ['engineer@aljabali.com', '/engineer-dashboard', $ok],
            'engineer: daily-visits' => ['engineer@aljabali.com', '/daily-visits', $ok],
            'engineer: sales-approval-requests' => ['engineer@aljabali.com', '/sales-approval-requests', $ok],
            'engineer: hr-requests' => ['engineer@aljabali.com', '/hr-requests', $ok],
            'engineer: products' => ['engineer@aljabali.com', '/products', $ok],
            'engineer: product view' => ['engineer@aljabali.com', '/products/1', $ok],
            'engineer: product create (forbidden)' => ['engineer@aljabali.com', '/products/create', $forbidden],
            'engineer: product edit (forbidden)' => ['engineer@aljabali.com', '/products/1/edit', $forbidden],
            'engineer: audit-log (forbidden)' => ['engineer@aljabali.com', '/audit-log', $forbidden],
            'engineer: approval-dashboard (forbidden)' => ['engineer@aljabali.com', '/approval-dashboard', $forbidden],
            'engineer: general-manager-dashboard (forbidden)' => ['engineer@aljabali.com', '/general-manager-dashboard', $forbidden],
            'engineer: sales-reports (forbidden)' => ['engineer@aljabali.com', '/sales-reports', $forbidden],
            'engineer: users (forbidden)' => ['engineer@aljabali.com', '/users', $forbidden],

            // warehouse@ — warehouse keeper
            'warehouse: approval-dashboard' => ['warehouse@aljabali.com', '/approval-dashboard', $ok],
            'warehouse: sales-approval-requests' => ['warehouse@aljabali.com', '/sales-approval-requests', $ok],
            'warehouse: products' => ['warehouse@aljabali.com', '/products', $ok],
            'warehouse: product create' => ['warehouse@aljabali.com', '/products/create', $ok],
            'warehouse: audit-log (forbidden)' => ['warehouse@aljabali.com', '/audit-log', $forbidden],
            'warehouse: engineer-dashboard (forbidden)' => ['warehouse@aljabali.com', '/engineer-dashboard', $forbidden],
            'warehouse: general-manager-dashboard (forbidden)' => ['warehouse@aljabali.com', '/general-manager-dashboard', $forbidden],
            'warehouse: users (forbidden)' => ['warehouse@aljabali.com', '/users', $forbidden],

            // sales@ — sales manager
            'sales: approval-dashboard' => ['sales@aljabali.com', '/approval-dashboard', $ok],
            'sales: sales-reports' => ['sales@aljabali.com', '/sales-reports', $ok],
            'sales: sales-approval-requests' => ['sales@aljabali.com', '/sales-approval-requests', $ok],
            'sales: daily-visits' => ['sales@aljabali.com', '/daily-visits', $ok],
            'sales: hr-requests' => ['sales@aljabali.com', '/hr-requests', $ok],
            'sales: products' => ['sales@aljabali.com', '/products', $ok],
            'sales: product view' => ['sales@aljabali.com', '/products/1', $ok],
            'sales: product create (forbidden)' => ['sales@aljabali.com', '/products/create', $forbidden],
            'sales: product edit (forbidden)' => ['sales@aljabali.com', '/products/1/edit', $forbidden],
            'sales: audit-log (forbidden)' => ['sales@aljabali.com', '/audit-log', $forbidden],
            'sales: hr-reports (forbidden)' => ['sales@aljabali.com', '/hr-reports', $forbidden],
            'sales: users (forbidden)' => ['sales@aljabali.com', '/users', $forbidden],
            'sales: general-manager-dashboard (forbidden)' => ['sales@aljabali.com', '/general-manager-dashboard', $forbidden],

            // purchasing@ — purchasing manager
            'purchasing: approval-dashboard' => ['purchasing@aljabali.com', '/approval-dashboard', $ok],
            'purchasing: sales-approval-requests' => ['purchasing@aljabali.com', '/sales-approval-requests', $ok],
            'purchasing: products' => ['purchasing@aljabali.com', '/products', $ok],
            'purchasing: product create (forbidden)' => ['purchasing@aljabali.com', '/products/create', $forbidden],
            'purchasing: audit-log (forbidden)' => ['purchasing@aljabali.com', '/audit-log', $forbidden],
            'purchasing: sales-reports (forbidden)' => ['purchasing@aljabali.com', '/sales-reports', $forbidden],
            'purchasing: users (forbidden)' => ['purchasing@aljabali.com', '/users', $forbidden],

            // finance@ — financial manager
            'finance: approval-dashboard' => ['finance@aljabali.com', '/approval-dashboard', $ok],
            'finance: sales-approval-requests' => ['finance@aljabali.com', '/sales-approval-requests', $ok],
            'finance: products' => ['finance@aljabali.com', '/products', $ok],
            'finance: product create (forbidden)' => ['finance@aljabali.com', '/products/create', $forbidden],
            'finance: audit-log (forbidden)' => ['finance@aljabali.com', '/audit-log', $forbidden],
            'finance: hr-reports (forbidden)' => ['finance@aljabali.com', '/hr-reports', $forbidden],
            'finance: users (forbidden)' => ['finance@aljabali.com', '/users', $forbidden],

            // gm@ — general manager
            'gm: general-manager-dashboard' => ['gm@aljabali.com', '/general-manager-dashboard', $ok],
            'gm: sales-reports' => ['gm@aljabali.com', '/sales-reports', $ok],
            'gm: hr-reports' => ['gm@aljabali.com', '/hr-reports', $ok],
            'gm: audit-log' => ['gm@aljabali.com', '/audit-log', $ok],
            'gm: users' => ['gm@aljabali.com', '/users', $ok],
            'gm: daily-visits' => ['gm@aljabali.com', '/daily-visits', $ok],
            'gm: hr-requests' => ['gm@aljabali.com', '/hr-requests', $ok],
            'gm: sales-approval-requests' => ['gm@aljabali.com', '/sales-approval-requests', $ok],
            'gm: products' => ['gm@aljabali.com', '/products', $ok],
            'gm: product create' => ['gm@aljabali.com', '/products/create', $ok],
            'gm: product edit' => ['gm@aljabali.com', '/products/1/edit', $ok],
            'gm: engineer-dashboard (forbidden)' => ['gm@aljabali.com', '/engineer-dashboard', $forbidden],
            'gm: approval-dashboard (forbidden)' => ['gm@aljabali.com', '/approval-dashboard', $forbidden],
        ];
    }
}
