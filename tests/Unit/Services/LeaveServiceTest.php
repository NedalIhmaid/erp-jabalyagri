<?php

namespace Tests\Unit\Services;

use App\Enums\HrRequestType;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveService $leaveService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leaveService = app(LeaveService::class);
    }

    public function test_calculate_annual_leave_first_year(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->startOfYear(),
        ]);

        $total = $this->leaveService->calculateAnnualLeaveTotal($user, now()->year);

        $this->assertEquals(14, $total);
    }

    public function test_calculate_annual_leave_without_hire_date(): void
    {
        $user = User::factory()->create([
            'hire_date' => null,
        ]);

        $total = $this->leaveService->calculateAnnualLeaveTotal($user);

        $this->assertEquals(0, $total);
    }

    public function test_calculate_annual_leave_after_10_years(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(11)->startOfYear(),
        ]);

        $total = $this->leaveService->calculateAnnualLeaveTotal($user, now()->year);

        $this->assertEquals(30, $total);
    }

    public function test_calculate_annual_leave_after_5_years(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(6)->startOfYear(),
        ]);

        $total = $this->leaveService->calculateAnnualLeaveTotal($user, now()->year);

        $this->assertEquals(21, $total);
    }

    public function test_calculate_annual_leave_after_2_years(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $total = $this->leaveService->calculateAnnualLeaveTotal($user, now()->year);

        $this->assertEquals(14, $total);
    }

    public function test_get_or_create_balance_creates_new_balance(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $balance = $this->leaveService->getOrCreateBalance($user);

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $user->id,
            'year' => now()->year,
        ]);
        $this->assertEquals(14, (float) $balance->annual_total);
        $this->assertEquals(14, (float) $balance->sick_total);
        $this->assertEquals(0, (float) $balance->annual_used);
    }

    public function test_get_or_create_balance_returns_existing_balance(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $existingBalance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => now()->year,
            'annual_total' => 14,
            'annual_used' => 5,
            'sick_total' => 14,
        ]);

        $balance = $this->leaveService->getOrCreateBalance($user);

        $this->assertEquals($existingBalance->id, $balance->id);
    }

    public function test_can_take_annual_leave_with_sufficient_balance(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::AnnualLeave, 5)
        );
    }

    public function test_cannot_take_annual_leave_with_insufficient_balance(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $balance->update(['annual_used' => 10]);

        $this->assertFalse(
            $this->leaveService->canTakeLeave($user, HrRequestType::AnnualLeave, 5)
        );
    }

    public function test_can_take_sick_leave(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::SickLeave, 10)
        );
    }

    public function test_can_always_take_unpaid_leave(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::UnpaidLeave, 10)
        );
    }

    public function test_can_always_take_early_departure(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::EarlyDeparture, 1)
        );
    }

    public function test_can_always_take_departure_from_annual(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::DepartureFromAnnual, 1)
        );
    }

    public function test_can_take_marriage_leave_when_not_used(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);

        $this->assertTrue(
            $this->leaveService->canTakeLeave($user, HrRequestType::MarriageLeave, 3)
        );
    }

    public function test_cannot_take_marriage_leave_when_used(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $balance->update(['marriage_used' => true]);

        $this->assertFalse(
            $this->leaveService->canTakeLeave($user, HrRequestType::MarriageLeave, 3)
        );
    }

    public function test_deduct_annual_leave(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);
        $this->leaveService->deductLeave($user, HrRequestType::AnnualLeave, 5);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $this->assertEquals(5, (float) $balance->annual_used);
    }

    public function test_deduct_sick_leave(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);
        $this->leaveService->deductLeave($user, HrRequestType::SickLeave, 7);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $this->assertEquals(7, (float) $balance->sick_used);
    }

    public function test_deduct_marriage_leave(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);
        $this->leaveService->deductLeave($user, HrRequestType::MarriageLeave, 3);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $this->assertTrue($balance->marriage_used);
    }

    public function test_get_user_balances(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);

        $balances = $this->leaveService->getUserBalances($user);

        $this->assertArrayHasKey('annual', $balances);
        $this->assertArrayHasKey('sick', $balances);
        $this->assertArrayHasKey('marriage', $balances);
        $this->assertArrayHasKey('maternity', $balances);
        $this->assertArrayHasKey('bereavement', $balances);

        $this->assertArrayHasKey('total', $balances['annual']);
        $this->assertArrayHasKey('used', $balances['annual']);
        $this->assertArrayHasKey('remaining', $balances['annual']);
    }

    public function test_get_user_balances_returns_correct_values(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $this->leaveService->getOrCreateBalance($user);
        $this->leaveService->deductLeave($user, HrRequestType::AnnualLeave, 5);

        $balances = $this->leaveService->getUserBalances($user);

        $this->assertEquals(14, $balances['annual']['total']);
        $this->assertEquals(5, $balances['annual']['used']);
        $this->assertEquals(9, $balances['annual']['remaining']);
        $this->assertEquals(14, $balances['sick']['total']);
        $this->assertEquals(0, $balances['sick']['used']);
    }

    public function test_balance_updates_annual_total_if_hire_date_changed(): void
    {
        $user = User::factory()->create([
            'hire_date' => now()->subYears(2)->startOfYear(),
        ]);

        $balance = $this->leaveService->getOrCreateBalance($user);
        $this->assertEquals(14, (float) $balance->annual_total);

        $user->update(['hire_date' => now()->subYears(11)->startOfYear()]);

        $newBalance = $this->leaveService->getOrCreateBalance($user);
        $this->assertEquals(30, (float) $newBalance->annual_total);
    }
}
