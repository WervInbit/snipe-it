<?php

namespace Tests\Feature\Users\Api;

use App\Models\User;
use Database\Factories\UserFactory;
use Tests\TestCase;

class TwoFactorResetAuthorizationTest extends TestCase
{
    public function testGranularEditorCannotResetAdminTwoFactorSecret(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = $this->twoFactorUser(User::factory()->admin());

        $this->assertResetForbidden($actor, $target);
    }

    public function testAdminCannotResetSuperuserTwoFactorSecret(): void
    {
        $actor = User::factory()->admin()->editUsers()->create();
        $target = $this->twoFactorUser(User::factory()->superuser());

        $this->assertResetForbidden($actor, $target);
    }

    public function testDemoLockPreventsTwoFactorReset(): void
    {
        config(['app.lock_passwords' => true]);

        $actor = User::factory()->superuser()->create();
        $target = $this->twoFactorUser(User::factory());

        $this->assertResetForbidden($actor, $target);
    }

    public function testGranularEditorCanResetOrdinaryUserTwoFactorSecret(): void
    {
        $actor = User::factory()->editUsers()->create();
        $target = $this->twoFactorUser(User::factory());

        $this->actingAsForApi($actor)
            ->postJson(route('api.users.two_factor_reset'), ['id' => $target->id])
            ->assertOk()
            ->assertJson([
                'message' => trans('admin/settings/general.two_factor_reset_success'),
            ]);

        $target->refresh();
        $this->assertNull($target->two_factor_secret);
        $this->assertSame(0, (int) $target->two_factor_enrolled);
        $this->assertDatabaseHas('action_logs', [
            'action_type' => '2FA reset',
            'target_type' => User::class,
            'target_id' => $target->id,
            'created_by' => $actor->id,
        ]);
    }

    private function assertResetForbidden(User $actor, User $target): void
    {
        $this->actingAsForApi($actor)
            ->postJson(route('api.users.two_factor_reset'), ['id' => $target->id])
            ->assertForbidden()
            ->assertJson(['message' => trans('general.unauthorized')]);

        $target->refresh();
        $this->assertSame('TESTSECRET', $target->two_factor_secret);
        $this->assertSame(1, (int) $target->two_factor_enrolled);
        $this->assertDatabaseMissing('action_logs', [
            'action_type' => '2FA reset',
            'target_type' => User::class,
            'target_id' => $target->id,
        ]);
    }

    private function twoFactorUser(UserFactory $factory): User
    {
        return $factory->create([
            'two_factor_secret' => 'TESTSECRET',
            'two_factor_enrolled' => 1,
            'two_factor_optin' => 1,
        ]);
    }
}
