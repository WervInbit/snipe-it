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

        $user = User::factory()->superuser()->create([
            'email' => 'dusk-superuser@example.test',
            'username' => 'dusk-superuser',
        ]);

        $this->browse(function (Browser $browser) use ($user, $baseUrl) {
            $browser->loginAs($user)
                ->visit($baseUrl)
                ->waitFor('.dashboard-refurb-filter-row', 15)
                ->assertSee('Stand-by')
                ->assertSee('Being Processed')
                ->assertSee('QA Hold');
        });
    }
}
