<?php

namespace Tests\Feature;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Enums\SalesRequestStatus;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User $engineer;
    protected User $secondEngineer;
    protected User $salesManager;
    protected User $generalManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $this->engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $this->secondEngineer = User::where('email', 'engineer4@aljabali.com')->firstOrFail();
        $this->salesManager = User::where('email', 'sales@aljabali.com')->firstOrFail();
        $this->generalManager = User::where('email', 'gm@aljabali.com')->firstOrFail();
    }

    public function test_sales_export_requires_report_role(): void
    {
        SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'request_number' => 'SAR-CTRL-SALES-0001',
            'client_name' => 'Controller Sales Client',
            'status' => SalesRequestStatus::Approved,
            'payment_method' => 'on_account',
        ]);

        $this->actingAs($this->engineer)
            ->get('/reports/sales/export')
            ->assertForbidden();

        $response = $this->actingAs($this->salesManager)
            ->get('/reports/sales/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('SAR-CTRL-SALES-0001', $response->streamedContent());
    }

    public function test_sales_export_filters_by_status(): void
    {
        SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'request_number' => 'SAR-CTRL-SALES-APPROVED',
            'client_name' => 'Approved Client',
            'status' => SalesRequestStatus::Approved,
            'payment_method' => 'on_account',
        ]);

        SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'request_number' => 'SAR-CTRL-SALES-CANCELLED',
            'client_name' => 'Cancelled Client',
            'status' => SalesRequestStatus::Cancelled,
            'payment_method' => 'on_account',
        ]);

        $content = $this->actingAs($this->generalManager)
            ->get('/reports/sales/export?status[]=approved')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('SAR-CTRL-SALES-APPROVED', $content);
        $this->assertStringNotContainsString('SAR-CTRL-SALES-CANCELLED', $content);
    }

    public function test_sales_export_is_scoped_for_sales_manager_team(): void
    {
        SalesApprovalRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'request_number' => 'SAR-CTRL-SALES-OWN-TEAM',
            'client_name' => 'Own Team Client',
            'status' => SalesRequestStatus::Approved,
            'payment_method' => 'on_account',
        ]);

        SalesApprovalRequest::factory()->create([
            'user_id' => $this->secondEngineer->id,
            'request_number' => 'SAR-CTRL-SALES-OTHER-TEAM',
            'client_name' => 'Other Team Client',
            'status' => SalesRequestStatus::Approved,
            'payment_method' => 'on_account',
        ]);

        $content = $this->actingAs($this->salesManager)
            ->get('/reports/sales/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('SAR-CTRL-SALES-OWN-TEAM', $content);
        $this->assertStringNotContainsString('SAR-CTRL-SALES-OTHER-TEAM', $content);
    }

    public function test_hr_export_requires_general_manager(): void
    {
        HrRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Approved,
            'reason' => 'Controller HR export reason',
        ]);

        $this->actingAs($this->salesManager)
            ->get('/reports/hr/export')
            ->assertForbidden();

        $content = $this->actingAs($this->generalManager)
            ->get('/reports/hr/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Controller HR export reason', $content);
    }

    public function test_hr_export_filters_by_type(): void
    {
        HrRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Approved,
            'reason' => 'Annual leave controller export',
        ]);

        HrRequest::factory()->create([
            'user_id' => $this->engineer->id,
            'manager_id' => $this->salesManager->id,
            'type' => HrRequestType::SickLeave,
            'status' => HrRequestStatus::Approved,
            'reason' => 'Sick leave controller export',
        ]);

        $content = $this->actingAs($this->generalManager)
            ->get('/reports/hr/export?type[]=annual_leave')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Annual leave controller export', $content);
        $this->assertStringNotContainsString('Sick leave controller export', $content);
    }

    public function test_daily_visit_export_is_scoped_for_engineers(): void
    {
        DailyVisit::factory()->create([
            'user_id' => $this->engineer->id,
            'client_name' => 'Own Visit Client',
        ]);

        DailyVisit::factory()->create([
            'user_id' => $this->secondEngineer->id,
            'client_name' => 'Other Team Visit Client',
        ]);

        $content = $this->actingAs($this->engineer)
            ->get('/reports/daily-visits/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Own Visit Client', $content);
        $this->assertStringNotContainsString('Other Team Visit Client', $content);
    }

    public function test_daily_visit_export_is_scoped_for_sales_manager_team(): void
    {
        DailyVisit::factory()->create([
            'user_id' => $this->engineer->id,
            'client_name' => 'Managed Team Visit Client',
        ]);

        DailyVisit::factory()->create([
            'user_id' => $this->secondEngineer->id,
            'client_name' => 'Other Sales Manager Visit Client',
        ]);

        $content = $this->actingAs($this->salesManager)
            ->get('/reports/daily-visits/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Managed Team Visit Client', $content);
        $this->assertStringNotContainsString('Other Sales Manager Visit Client', $content);
    }

    public function test_daily_visit_export_rejects_unrelated_roles(): void
    {
        $warehouseKeeper = User::where('email', 'warehouse@aljabali.com')->firstOrFail();

        $this->actingAs($warehouseKeeper)
            ->get('/reports/daily-visits/export')
            ->assertForbidden();
    }
}
