<?php

namespace Tests\Unit\Models;

use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_balance_can_be_created(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_total' => 14,
            'annual_used' => 0,
            'sick_total' => 14,
            'sick_used' => 0,
            'marriage_used' => false,
            'maternity_used' => 0,
            'bereavement_used' => 0,
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $user->id,
            'year' => 2025,
        ]);
    }

    public function test_leave_balance_has_correct_casts(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_total' => 14,
            'annual_used' => 3.5,
            'sick_total' => 14,
            'sick_used' => 7,
            'marriage_used' => false,
            'maternity_used' => 10,
            'bereavement_used' => 1,
        ]);

        $this->assertEquals(2025, $balance->year);
        $this->assertEquals(3.5, (float) $balance->annual_used);
        $this->assertEquals(7, (float) $balance->sick_used);
        $this->assertFalse($balance->marriage_used);
        $this->assertEquals(10, $balance->maternity_used);
        $this->assertEquals(1, $balance->bereavement_used);
    }

    public function test_annual_remaining_calculation(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_total' => 14,
            'annual_used' => 5,
        ]);

        $this->assertEquals(9, $balance->annual_remaining);
    }

    public function test_annual_remaining_cannot_be_negative(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_total' => 14,
            'annual_used' => 20,
        ]);

        $this->assertEquals(0, $balance->annual_remaining);
    }

    public function test_sick_remaining_calculation(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'sick_total' => 14,
            'sick_used' => 4,
        ]);

        $this->assertEquals(10, $balance->sick_remaining);
    }

    public function test_sick_remaining_cannot_be_negative(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'sick_total' => 14,
            'sick_used' => 20,
        ]);

        $this->assertEquals(0, $balance->sick_remaining);
    }

    public function test_marriage_remaining_when_not_used(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'marriage_used' => false,
        ]);

        $this->assertEquals(3, $balance->marriage_remaining);
    }

    public function test_marriage_remaining_when_used(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'marriage_used' => true,
        ]);

        $this->assertEquals(0, $balance->marriage_remaining);
    }

    public function test_maternity_remaining_calculation(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'maternity_used' => 30,
        ]);

        $this->assertEquals(40, $balance->maternity_remaining);
    }

    public function test_maternity_remaining_cannot_be_negative(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'maternity_used' => 80,
        ]);

        $this->assertEquals(0, $balance->maternity_remaining);
    }

    public function test_bereavement_remaining_calculation(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'bereavement_used' => 1,
        ]);

        // 3 days per event, max 3 remaining
        $this->assertEquals(2, $balance->bereavement_remaining);
    }

    public function test_leave_balance_user_relationship(): void
    {
        $user = User::factory()->create(['name' => 'Employee']);

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
        ]);

        $this->assertEquals('Employee', $balance->user->name);
    }

    public function test_leave_balance_fillable_attributes(): void
    {
        $balance = new LeaveBalance();
        $expected = [
            'user_id', 'year', 'annual_total', 'annual_used',
            'sick_total', 'sick_used', 'marriage_used',
            'maternity_used', 'bereavement_used',
        ];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $balance->getFillable());
        }
    }

    public function test_leave_balance_can_be_updated(): void
    {
        $user = User::factory()->create();

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'year' => 2025,
            'annual_total' => 14,
            'annual_used' => 0,
        ]);

        $balance->update([
            'annual_used' => 5,
        ]);

        $fresh = $balance->fresh();
        $this->assertEquals(5, (float) $fresh->annual_used);
        $this->assertEquals(9, $fresh->annual_remaining);
    }
}
