<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function test_unauthenticated_visit_shows_login_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertVisible('input[type="email"]')
                ->assertVisible('input[type="password"]');
        });
    }
}
