<?php

namespace Tests\Feature\Authentication;

use App\Models\SamlNonce;
use App\Models\User;
use App\Services\Saml;
use Mockery;
use Tests\TestCase;

class SamlNonceReplayProtectionTest extends TestCase
{
    public function testUnseenAssertionIdentifierIsReservedBeforeAuthentication(): void
    {
        $user = User::factory()->create();
        $notValidAfter = now()->addMinutes(5);
        $samlData = [
            'nonce' => 'assertion-first-use',
            'assertionNotOnOrAfter' => $notValidAfter->toAtomString(),
            'nameId' => $user->username,
            'attributes' => [],
        ];

        $saml = Mockery::mock(Saml::class);
        $saml->shouldReceive('isEnabled')->once()->andReturnTrue();
        $saml->shouldReceive('samlLogin')
            ->once()
            ->with($samlData)
            ->andReturn($user);
        $this->app->instance(Saml::class, $saml);

        $this->withSession(['saml_login' => $samlData])
            ->get(route('login'))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('saml_nonces', [
            'nonce' => 'assertion-first-use',
        ]);
        $this->assertNotNull(
            SamlNonce::where('nonce', 'assertion-first-use')->value('not_valid_after')
        );
    }

    public function testPreviouslyReservedAssertionIdentifierIsRejectedBeforeAuthentication(): void
    {
        User::factory()->create();
        $notValidAfter = now()->addMinutes(5);
        SamlNonce::create([
            'nonce' => 'assertion-replay',
            'not_valid_after' => $notValidAfter,
        ]);

        $samlData = [
            'nonce' => 'assertion-replay',
            'assertionNotOnOrAfter' => $notValidAfter->toAtomString(),
            'nameId' => 'replay-user',
            'attributes' => [],
        ];

        $saml = Mockery::mock(Saml::class);
        $saml->shouldReceive('isEnabled')->once()->andReturnTrue();
        $saml->shouldNotReceive('samlLogin');
        $this->app->instance(Saml::class, $saml);

        $this->withSession(['saml_login' => $samlData])
            ->get(route('login'))
            ->assertStatus(400);

        $this->assertGuest();
        $this->assertSame(1, SamlNonce::where('nonce', 'assertion-replay')->count());
    }

    public function testAssertionWithoutIdentifierIsRejectedBeforeAuthentication(): void
    {
        User::factory()->create();
        $samlData = [
            'assertionNotOnOrAfter' => now()->addMinutes(5)->toAtomString(),
            'nameId' => 'missing-nonce-user',
            'attributes' => [],
        ];

        $saml = Mockery::mock(Saml::class);
        $saml->shouldReceive('isEnabled')->once()->andReturnTrue();
        $saml->shouldNotReceive('samlLogin');
        $this->app->instance(Saml::class, $saml);

        $this->withSession(['saml_login' => $samlData])
            ->get(route('login'))
            ->assertStatus(400);

        $this->assertGuest();
        $this->assertDatabaseCount('saml_nonces', 0);
    }
}
