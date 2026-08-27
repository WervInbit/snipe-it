<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    public function test_login_form_renders_inputs(): void
    {
        User::factory()->create();

        $this->browse(function (Browser $browser) {
            $baseUrl = rtrim(config('app.url'), '/');

            $browser->visit("{$baseUrl}/login")
                ->assertPresent('#username')
                ->assertPresent('#password');
        });
    }
}
