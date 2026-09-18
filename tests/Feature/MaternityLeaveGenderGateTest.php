<?php

namespace Tests\Feature;

use App\Enums\HrRequestType;
use App\Models\User;
use App\Services\HrRequestService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaternityLeaveGenderGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_male_user_cannot_submit_maternity_leave(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $engineer = User::where('email', 'engineer@aljabali.com')->firstOrFail();
        $this->assertSame('male', $engineer->gender?->value);

        $this->expectException(\InvalidArgumentException::class);

        app(HrRequestService::class)->submit([
            'type' => HrRequestType::MaternityLeave->value,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'duration_days' => 56,
            'reason' => 'Maternity',
        ], $engineer);
    }

    public function test_female_user_cannot_submit_paternity_leave(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $female = User::factory()->create([
            'email' => 'woman@aljabali.com',
            'gender' => 'female',
            'hire_date' => now()->subYear()->toDateString(),
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $female->assignRole('engineer');

        $this->expectException(\InvalidArgumentException::class);

        app(HrRequestService::class)->submit([
            'type' => HrRequestType::PaternityLeave->value,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'duration_days' => 3,
            'reason' => 'Paternity',
        ], $female);
    }
}
