<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\UserSeeder;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ShieldSeeder::class);
        $this->seed(UserSeeder::class);
    }

    public function test_request_password_reset_page_is_reachable(): void
    {
        $this->get('/password-reset/request')->assertOk();
    }

    public function test_login_page_includes_forgot_password_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('/password-reset/request', false);
    }

    public function test_submitting_request_sends_notification_for_active_panel_user(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'engineer@aljabali.com')
            ->call('request')
            ->assertHasNoErrors();

        $user = User::where('email', 'engineer@aljabali.com')->first();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
