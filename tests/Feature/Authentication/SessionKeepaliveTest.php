<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Tests\TestCase;

class SessionKeepaliveTest extends TestCase
{
    public function testKeepaliveRequiresAuthentication(): void
    {
        User::factory()->create();
        $token = 'session-keepalive-token';

        $this->withSession(['_token' => $token])
            ->postJson(route('session.keepalive'), ['_token' => $token])
            ->assertUnauthorized();
    }

    public function testAuthenticatedKeepaliveRefreshesSession(): void
    {
        config(['session.lifetime' => 30]);

        $token = 'session-keepalive-token';

        $response = $this->actingAs(User::factory()->create())
            ->withSession(['_token' => $token])
            ->postJson(route('session.keepalive'), ['_token' => $token]);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'expires_in' => 1800,
            ])
            ->assertSessionHas('last_keepalive_at');

        $this->assertIsInt(session('last_keepalive_at'));
    }

    public function testAuthenticatedPagesRenderIdleWarningModal(): void
    {
        config([
            'session.lifetime' => 30,
            'session.idle_client_warning' => true,
            'session.idle_warning_seconds' => 60,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.index'));

        $response->assertOk();
        $response->assertSee('data-testid="session-expiring-modal"', false);
        $response->assertSee('keepaliveUrl', false);
        $response->assertSee(trans('general.stay_signed_in'));
    }
}
