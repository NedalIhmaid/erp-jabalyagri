<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveAccrualTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $hireDate): User
    {
        return User::factory()->create([
            'hire_date' => $hireDate,
            'is_active' => true,
            'locale'    => 'ar',
        ]);
    }

    public function test_annual_bracket_follows_jordan_labor_law(): void
    {
        Carbon::setTestNow('2026-06-01');
        $service = app(LeaveService::class);

        $junior = $this->makeUser('2024-01-01'); // ~2 years
        $mid    = $this->makeUser('2019-01-01'); // ~7 years
        $senior = $this->makeUser('2014-01-01'); // ~12 years

        $this->assertSame(14, $service->annualBracketDays($junior, 2026));
        $this->assertSame(21, $service->annualBracketDays($mid, 2026));
        $this->assertSame(30, $service->annualBracketDays($senior, 2026));
    }

    public function test_monthly_accrual_for_current_year(): void
    {
        // On 1 June, 5 full months have elapsed since 1 Jan.
        Carbon::setTestNow('2026-06-01');
        $service = app(LeaveService::class);

        $user = $this->makeUser('2024-01-01'); // bracket = 14
        // 14 / 12 * 5 = 5.833... -> 5.8
        $this->assertSame(5.8, $service->accruedAnnual($user, 2026));
    }

    public function test_prior_year_is_fully_accrued(): void
    {
        Carbon::setTestNow('2026-06-01');
        $service = app(LeaveService::class);

        $user = $this->makeUser('2020-01-01'); // bracket 21 in 2025
        $this->assertSame(
            $service->calculateAnnualLeaveTotal($user, 2025),
            $service->accruedAnnual($user, 2025)
        );
    }

    public function test_future_year_accrues_nothing(): void
    {
        Carbon::setTestNow('2026-06-01');
        $service = app(LeaveService::class);

        $user = $this->makeUser('2020-01-01');
        $this->assertSame(0.0, (float) $service->accruedAnnual($user, 2027));
    }

    public function test_accrual_capped_at_prorated_total_in_hire_year(): void
    {
        // Hired mid-year: accrual must never exceed the prorated annual total.
        Carbon::setTestNow('2026-12-31');
        $service = app(LeaveService::class);

        $user  = $this->makeUser('2026-07-01');
        $total = $service->calculateAnnualLeaveTotal($user, 2026);

        $this->assertLessThanOrEqual($total, $service->accruedAnnual($user, 2026));
    }
}
