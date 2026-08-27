<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CheckUserIsActivatedApiTest extends TestCase
{
    public function testActivatedUserCanUseApi(): void
    {
        Passport::actingAs(User::factory()->create(['activated' => 1]));

        $this->getJson(route('api.users.me'))->assertOk();
    }

    public function testDeactivatedUserApiTokenIsRefused(): void
    {
        Passport::actingAs(User::factory()->create(['activated' => 0]));

        $this->getJson(route('api.users.me'))
            ->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'messages' => trans('general.unauthorized'),
                'payload' => null,
            ]);
    }

    public function testApiPathReturnsJsonForDeactivatedUserWithoutAcceptHeader(): void
    {
        Passport::actingAs(User::factory()->create(['activated' => 0]));

        $this->get(route('api.users.me'))
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertJson(['status' => 'error']);
    }

    public function testDeactivatedUserCannotWriteViaApi(): void
    {
        Passport::actingAs(User::factory()->superuser()->create(['activated' => 0]));

        $this->postJson(route('api.users.store'), [
            'first_name' => 'Should',
            'last_name' => 'Not Persist',
            'username' => 'deactivated-user-write',
            'password' => 'This-Is-A-Long-Password-123!',
            'password_confirmation' => 'This-Is-A-Long-Password-123!',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('users', ['username' => 'deactivated-user-write']);
    }

    public function testDeactivatedAdminCannotReactivateItselfViaApi(): void
    {
        $user = User::factory()->admin()->editUsers()->create(['activated' => 0]);
        Passport::actingAs($user);

        $this->patchJson(route('api.users.update', $user), [
            'activated' => 1,
        ])->assertUnauthorized();

        $this->assertSame(0, (int) $user->fresh()->activated);
    }
}
