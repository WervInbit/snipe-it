<?php

namespace Tests\Feature\Users;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetAuthorizationTest extends TestCase
{
    public function testViewOnlyUserCannotSendIndividualPasswordReset(): void
    {
        $actor = User::factory()->viewUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')->never();

        $this->actingAs($actor)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertForbidden();
    }

    public function testEditorCanSendIndividualPasswordReset(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $target->email])
            ->andReturn('passwords.sent');

        $this->actingAs($actor)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function testViewOnlyUserCannotSendBulkPasswordReset(): void
    {
        $actor = User::factory()->viewUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')->never();

        $this->actingAs($actor)
            ->post(route('users/bulkedit'), [
                'ids' => [$target->id],
                'bulk_actions' => 'bulkpasswordreset',
            ])
            ->assertForbidden();
    }

    public function testEditorCanSendBulkPasswordReset(): void
    {
        $actor = User::factory()->editUsers()->viewUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $target->email])
            ->andReturn('passwords.sent');

        $this->actingAs($actor)
            ->post(route('users/bulkedit'), [
                'ids' => [$target->id],
                'bulk_actions' => 'bulkpasswordreset',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function testIndividualPasswordResetEnforcesTargetCompanyScope(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = User::factory()->editUsers()->for($companyA)->create();
        $target = $this->resettableUser(['company_id' => $companyB->id]);
        Password::shouldReceive('sendResetLink')->never();

        $this->actingAs($actor)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertForbidden();
    }

    public function testBulkPasswordResetAuthorizesEveryTargetAcrossCompanyScope(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = User::factory()->editUsers()->viewUsers()->for($companyA)->create();
        $target = $this->resettableUser(['company_id' => $companyB->id]);
        Password::shouldReceive('sendResetLink')->never();

        $this->actingAs($actor)
            ->post(route('users/bulkedit'), [
                'ids' => [$target->id],
                'bulk_actions' => 'bulkpasswordreset',
            ])
            ->assertForbidden();
    }

    public function testIndividualPasswordResetRouteReturns429AfterNamedLimit(): void
    {
        config(['auth.password_reset.max_attempts_per_min' => 1]);
        $actor = User::factory()->editUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $target->email])
            ->andReturn('passwords.sent');

        $this->actingAs($actor)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertRedirect();

        $this->actingAs($actor)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertTooManyRequests();
    }

    public function testBulkPasswordResetRouteReturns429AfterNamedLimit(): void
    {
        config(['auth.password_reset.max_attempts_per_min' => 1]);
        $actor = User::factory()->editUsers()->viewUsers()->create();
        $target = $this->resettableUser();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $target->email])
            ->andReturn('passwords.sent');
        $payload = [
            'ids' => [$target->id],
            'bulk_actions' => 'bulkpasswordreset',
        ];

        $this->actingAs($actor)
            ->post(route('users/bulkedit'), $payload)
            ->assertRedirect();

        $this->actingAs($actor)
            ->post(route('users/bulkedit'), $payload)
            ->assertTooManyRequests();
    }

    public function testBulkPasswordResetOptionIsHiddenFromViewOnlyUser(): void
    {
        $this->actingAs(User::factory()->viewUsers()->create())
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('value="bulkpasswordreset"', false);
    }

    public function testBulkPasswordResetOptionIsVisibleToEditor(): void
    {
        $this->actingAs(User::factory()->editUsers()->viewUsers()->create())
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('value="bulkpasswordreset"', false);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function resettableUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'activated' => 1,
            'email' => fake()->unique()->safeEmail(),
            'ldap_import' => 0,
        ], $overrides));
    }
}
