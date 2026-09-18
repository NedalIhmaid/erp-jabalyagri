<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_has_correct_casts(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'hire_date' => '2020-01-15',
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $user->email_verified_at);
        $this->assertTrue($user->is_active);
        $this->assertInstanceOf('Carbon\Carbon', $user->hire_date);
    }

    public function test_user_manager_relationship(): void
    {
        $manager = User::factory()->create(['name' => 'Manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);

        $this->assertTrue($employee->manager()->exists());
        $this->assertEquals('Manager', $employee->manager->name);
    }

    public function test_user_direct_reports_relationship(): void
    {
        $manager = User::factory()->create();
        $employee1 = User::factory()->create(['manager_id' => $manager->id]);
        $employee2 = User::factory()->create(['manager_id' => $manager->id]);

        $this->assertCount(2, $manager->directReports);
        $this->assertTrue($manager->directReports->contains($employee1));
        $this->assertTrue($manager->directReports->contains($employee2));
    }

    public function test_user_fillable_attributes(): void
    {
        $user = new User();
        $expected = ['name', 'email', 'password', 'phone', 'manager_id', 'is_active', 'locale', 'hire_date'];

        foreach ($expected as $attribute) {
            $this->assertContains($attribute, $user->getFillable());
        }
    }

    public function test_user_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plain-text-password',
        ]);

        $this->assertNotEquals('plain-text-password', $user->password);
        $this->assertTrue(password_verify('plain-text-password', $user->password));
    }

    public function test_user_can_access_panel_when_active(): void
    {
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $panel = mock(\Filament\Panel::class);

        $this->assertTrue($activeUser->canAccessPanel($panel));
        $this->assertFalse($inactiveUser->canAccessPanel($panel));
    }

    public function test_user_without_manager_has_null_manager(): void
    {
        $user = User::factory()->create(['manager_id' => null]);

        $this->assertNull($user->manager_id);
        $this->assertNull($user->manager);
    }

    public function test_user_with_manager_can_access_manager_direct_reports(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create(['manager_id' => $manager->id]);

        $this->assertTrue($manager->directReports->contains($employee));
        $this->assertEquals($manager->id, $employee->manager->id);
    }
}
