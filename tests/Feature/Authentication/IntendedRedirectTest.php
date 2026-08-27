<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

class IntendedRedirectTest extends TestCase
{
    public function testDirectLoginFallsBackToDashboard(): void
    {
        $user = User::factory()->create(['username' => 'direct-login']);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionMissing('url.intended');
    }

    public function testDirectAdminLoginAlsoFallsBackToDashboard(): void
    {
        $user = User::factory()->superuser()->create(['username' => 'direct-admin-login']);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionMissing('url.intended');
    }

    public function testProtectedSettingsLinkIsResumedAfterLogin(): void
    {
        $user = User::factory()->superuser()->create(['username' => 'settings-login']);
        $intendedUrl = route('settings.index');

        $this->get($intendedUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $intendedUrl);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])
            ->assertRedirect($intendedUrl)
            ->assertSessionMissing('url.intended');
    }

    public function testTwoFactorChallengePreservesQrDeepLink(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->twoFactorUser();
        $intendedUrl = route('scan.resolve', ['code' => 'AST-QR-123']);

        $this->get($intendedUrl)->assertRedirect(route('login'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect($intendedUrl);

        $this->get($intendedUrl)
            ->assertRedirect(route('two-factor'))
            ->assertSessionHas('url.intended', $intendedUrl);

        Google2FA::shouldReceive('verifyKey')
            ->once()
            ->with('TESTSECRET', '123456')
            ->andReturnTrue();

        $this->post(route('two-factor'), ['two_factor_secret' => '123456'])
            ->assertRedirect($intendedUrl)
            ->assertSessionHas('2fa_authed', $user->id)
            ->assertSessionMissing('url.intended');
    }

    public function testTwoFactorEnrollmentPreservesProtectedDeepLink(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->twoFactorUser(['two_factor_enrolled' => '0']);
        $intendedUrl = route('settings.index');

        $this->get($intendedUrl)->assertRedirect(route('login'));

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect($intendedUrl);

        $this->get($intendedUrl)
            ->assertRedirect(route('two-factor-enroll'))
            ->assertSessionHas('url.intended', $intendedUrl);

        Google2FA::shouldReceive('verifyKey')
            ->once()
            ->with('TESTSECRET', '123456')
            ->andReturnTrue();

        $this->post(route('two-factor'), ['two_factor_secret' => '123456'])
            ->assertRedirect($intendedUrl)
            ->assertSessionMissing('url.intended');

        $this->assertSame(1, (int) $user->fresh()->two_factor_enrolled);
    }

    public function testLogoutClearsObsoleteIntendedUrl(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                '2fa_authed' => $user->id,
                'url.intended' => route('settings.index'),
            ])
            ->post(route('logout.post'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('2fa_authed')
            ->assertSessionMissing('url.intended');

        $this->assertGuest();
    }

    private function twoFactorUser(array $attributes = []): User
    {
        return User::factory()->superuser()->create(array_merge([
            'username' => 'two-factor-login',
            'two_factor_optin' => '1',
            'two_factor_enrolled' => '1',
            'two_factor_secret' => 'TESTSECRET',
        ], $attributes));
    }
}
