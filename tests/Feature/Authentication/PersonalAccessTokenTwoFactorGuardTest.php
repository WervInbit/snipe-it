<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Tests\TestCase;

class PersonalAccessTokenTwoFactorGuardTest extends TestCase
{
    public function testPasswordOnlySessionCannotCreatePersonalAccessToken(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->apiUser();

        $this->actingAs($user, 'api')
            ->postJson(route('api.personal-access-token.create'), ['name' => 'blocked-token'])
            ->assertForbidden();

        $this->assertDatabaseMissing('oauth_access_tokens', ['name' => 'blocked-token']);
    }

    public function testPasswordOnlySessionCannotListPersonalAccessTokens(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->apiUser();

        $this->actingAs($user, 'api')
            ->getJson(route('api.personal-access-token.index'))
            ->assertForbidden();
    }

    public function testPasswordOnlySessionCannotDeletePersonalAccessToken(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->apiUser();

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.personal-access-token.delete', 'not-a-token'))
            ->assertForbidden();
    }

    public function testCompletedTwoFactorSessionCanListPersonalAccessTokens(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->apiUser();

        $this->actingAs($user, 'api')
            ->withSession(['2fa_authed' => $user->id])
            ->getJson(route('api.personal-access-token.index'))
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function testOptionalTwoFactorDoesNotBlockUserWhoDidNotOptIn(): void
    {
        $this->settings->set(['two_factor_enabled' => '1']);
        $user = $this->apiUser(['two_factor_optin' => '0']);

        $this->actingAs($user, 'api')
            ->getJson(route('api.personal-access-token.index'))
            ->assertOk();
    }

    public function testPasswordOnlyWebSessionDoesNotReceivePassportCookie(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->apiUser();

        $this->actingAs($user)
            ->get(route('two-factor'))
            ->assertOk()
            ->assertCookieMissing(config('passport.cookie_name'));
    }

    private function apiUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'permissions' => json_encode(['self.api' => '1']),
            'two_factor_optin' => '1',
            'two_factor_enrolled' => '1',
            'two_factor_secret' => 'TESTSECRET',
        ], $attributes));
    }
}
