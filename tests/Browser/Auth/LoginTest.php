<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'ShieldSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);
    }

    #[Test]
    public function unauthenticated_user_sees_login_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertVisible('input[type="email"]')
                ->assertVisible('input[type="password"]');
        });
    }

    #[Test]
    public function login_page_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertVisible('input[type="email"]')
                ->assertVisible('input[type="password"]')
                ->assertVisible('button[type="submit"]')
                ->assertVisible('a[href*="/password-reset/request"]');
        });
    }

    #[Test]
    public function engineer_can_login_and_sees_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[type="email"]', 'engineer@aljabali.com')
                ->type('input[type="password"]', 'password')
                ->press('button[type="submit"]')
                ->pause(2000)
                ->assertDontSee('Unauthorized');
        });
    }

    #[Test]
    public function warehouse_keeper_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[type="email"]', 'warehouse@aljabali.com')
                ->type('input[type="password"]', 'password')
                ->press('button[type="submit"]')
                ->pause(2000)
                ->assertDontSee('Unauthorized');
        });
    }

    #[Test]
    public function general_manager_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[type="email"]', 'gm@aljabali.com')
                ->type('input[type="password"]', 'password')
                ->press('button[type="submit"]')
                ->pause(2000)
                ->assertDontSee('Unauthorized');
        });
    }

    #[Test]
    public function invalid_credentials_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[type="email"]', 'wrong@aljabali.com')
                ->type('input[type="password"]', 'wrongpassword')
                ->press('button[type="submit"]')
                ->pause(1500)
                ->assertVisible('input[type="email"]');  // Still on login page
        });
    }

    #[Test]
    public function inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'engineer@aljabali.com')->first();
        $user->update(['is_active' => false]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[type="email"]', 'engineer@aljabali.com')
                ->type('input[type="password"]', 'password')
                ->press('button[type="submit"]')
                ->assertPathIs('/login');
        });
    }

    #[Test]
    public function user_can_logout(): void
    {
        $user = User::where('email', 'engineer@aljabali.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/engineer-dashboard')
                ->assertAuthenticated();
        });
    }

    #[Test]
    public function language_switcher_toggles_locale(): void
    {
        $user = User::where('email', 'engineer@aljabali.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/engineer-dashboard')
                ->visit('/locale/switch')
                ->assertPathIsNot('/login');
        });
    }
}
