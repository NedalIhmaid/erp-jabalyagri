<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SalesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesRequestNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected SalesApprovalService $salesService;

    protected User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $this->salesService = app(SalesApprovalService::class);
        $this->engineer = User::role('engineer')->firstOrFail();
    }

    public function test_sales_request_numbers_increment_per_day(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $first = $this->submitRequest();
        $second = $this->submitRequest();

        $this->assertSame('SAR-20260418-0001', $first->request_number);
        $this->assertSame('SAR-20260418-0002', $second->request_number);

        Carbon::setTestNow();
    }

    public function test_sales_request_number_uses_highest_existing_suffix_for_the_day(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $this->submitRequest();
        $this->submitRequest();

        Carbon::setTestNow('2026-04-19 08:00:00');
        $nextDay = $this->submitRequest();

        Carbon::setTestNow('2026-04-18 11:00:00');
        $sameDay = $this->submitRequest();

        $this->assertSame('SAR-20260419-0001', $nextDay->request_number);
        $this->assertSame('SAR-20260418-0003', $sameDay->request_number);

        Carbon::setTestNow();
    }

    public function test_burst_submissions_produce_unique_request_numbers(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $this->submitRequest()->request_number;
        }

        $this->assertCount(10, array_unique($numbers));
        $this->assertSame('SAR-20260418-0001', $numbers[0]);
        $this->assertSame('SAR-20260418-0010', $numbers[9]);

        Carbon::setTestNow();
    }

    public function test_collision_is_recovered_via_unique_index_retry(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        \App\Models\SalesApprovalRequest::create([
            'user_id' => $this->engineer->id,
            'request_number' => 'SAR-20260418-0001',
            'client_name' => 'Preexisting',
            'payment_method' => 'on_account',
            'total_amount' => 50,
            'status' => \App\Enums\SalesRequestStatus::InProgress,
            'current_stage' => 1,
        ]);

        $fresh = $this->submitRequest();
        $this->assertSame('SAR-20260418-0002', $fresh->request_number);

        Carbon::setTestNow();
    }

    protected function submitRequest()
    {
        return $this->salesService->submit([
            'client_name' => 'Numbering Client',
            'payment_method' => 'on_account',
            'total_amount' => 100,
        ], $this->engineer);
    }
}
