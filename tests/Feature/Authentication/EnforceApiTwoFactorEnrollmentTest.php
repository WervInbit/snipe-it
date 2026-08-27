<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EnforceApiTwoFactorEnrollmentTest extends TestCase
{
    public function testDisabledTwoFactorAllowsUnenrolledApiUser(): void
    {
        $this->settings->set(['two_factor_enabled' => '']);
        $user = $this->unenrolledUser();
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))->assertOk();
    }

    public function testZeroTwoFactorSettingIsTreatedAsDisabled(): void
    {
        $this->settings->set(['two_factor_enabled' => '0']);
        $user = $this->unenrolledUser();
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))->assertOk();
    }

    public function testOptionalTwoFactorAllowsUserWhoDidNotOptIn(): void
    {
        $this->settings->set(['two_factor_enabled' => '1']);
        $user = $this->unenrolledUser();
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))->assertOk();
    }

    public function testOptionalTwoFactorBlocksOptedInUnenrolledUser(): void
    {
        $this->settings->set(['two_factor_enabled' => '1']);
        $user = $this->unenrolledUser(['two_factor_optin' => '1']);
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))
            ->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'messages' => trans('auth/message.two_factor.please_enroll'),
                'payload' => null,
            ]);
    }

    public function testRequiredTwoFactorBlocksUnenrolledApiUser(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = $this->unenrolledUser();
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))
            ->assertForbidden()
            ->assertJson(['status' => 'error']);
    }

    public function testRequiredTwoFactorAllowsEnrolledApiUser(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        $user = User::factory()->create([
            'two_factor_enrolled' => '1',
            'two_factor_optin' => '1',
            'two_factor_secret' => 'TESTSECRET',
        ]);
        Passport::actingAs($user);

        $this->getJson(route('api.users.me'))->assertOk();
    }

    public function testMissingTokenRemainsAnAuthenticationFailure(): void
    {
        $this->settings->set(['two_factor_enabled' => '2']);
        User::factory()->create();

        $this->getJson(route('api.users.me'))->assertUnauthorized();
    }

    private function unenrolledUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'two_factor_enrolled' => '0',
            'two_factor_optin' => '0',
            'two_factor_secret' => null,
        ], $attributes));
    }
}
