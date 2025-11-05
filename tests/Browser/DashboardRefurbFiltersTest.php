<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardRefurbFiltersTest extends DuskTestCase
{
    public function test_refurb_status_filters_render_for_superuser(): void
    {
        $baseUrl = rtrim(config('app.url'), '/');

        if (parse_url($baseUrl, PHP_URL_HOST) !== 'dev.snipe.inbit') {
            $this->markTestIncomplete('Configure APP_URL for Dusk to point at https://dev.snipe.inbit so the dashboard can load under HTTPS.');
        }

        $user = User::factory()->superuser()->create([
            'email' => 'dusk-superuser@example.test',
            'username' => 'dusk-superuser',
        ]);

        $this->browse(function (Browser $browser) use ($user, $baseUrl) {
            $browser->visit("{$baseUrl}/login")
                ->type('username', $user->username)
                ->type('password', 'password')
                ->click('#submit')
                ->waitForLocation('/start', 15)
                ->click('@start-dashboard')
                ->waitForLocation('/', 15)
                ->waitFor('.dashboard-refurb-filter-row', 15)
                ->assertSee('Stand-by')
                ->assertSee('In verwerking')
                ->assertSee('QA-wacht');
        });
    }
}
