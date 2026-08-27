<?php

namespace Tests\Feature\Integrations;

use App\Listeners\RejectUnsafeMailAddresses;
use App\Listeners\SuppressDisabledMailNotifications;
use App\Models\Ldap;
use App\Models\User;
use App\Notifications\CurrentInventory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class DisabledIntegrationModeTest extends TestCase
{
    public function test_mail_disabled_mode_uses_non_logging_array_transport_and_suppresses_delivery(): void
    {
        config([
            'mail.enabled' => false,
            'mail.default' => 'array',
        ]);

        $user = User::factory()->create();
        $notification = new CurrentInventory($user);

        $this->assertSame('array', config('mail.default'));
        $this->assertFalse(app(SuppressDisabledMailNotifications::class)->handle(
            new NotificationSending($user, $notification, 'mail')
        ));
        $this->assertFalse(app(RejectUnsafeMailAddresses::class)->handle(
            new MessageSending(new Email())
        ));
        $this->get(route('health'))->assertExactJson(['status' => 'ok']);
    }

    public function test_disabled_mail_hides_self_service_reset_and_blocks_direct_request(): void
    {
        config([
            'mail.enabled' => false,
            'auth.ldap_integration_enabled' => false,
        ]);
        $this->settings->set(['custom_forgot_pass_url' => 'https://directory.example.test/reset']);
        User::factory()->create();

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee(route('password.request'), false)
            ->assertDontSee('directory.example.test', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(trans('mail.password_reset_disabled'))
            ->assertDontSee('action="'.url('/password/email').'"', false);

        $this->post(route('password.email'), ['username' => 'nobody'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', trans('mail.password_reset_disabled'));
    }

    public function test_disabled_mail_blocks_admin_reset_inventory_and_test_endpoints(): void
    {
        config(['mail.enabled' => false]);
        $admin = User::factory()->superuser()->create();
        $target = User::factory()->create(['email' => 'target@example.test']);

        Password::shouldReceive('sendResetLink')->never();

        $this->actingAs($admin)
            ->post(route('users.password', ['userId' => $target->id]))
            ->assertRedirect()
            ->assertSessionHas('error', trans('mail.password_reset_disabled'));

        $this->actingAs($admin)
            ->post(route('users.email', ['userId' => $target->id]))
            ->assertRedirect()
            ->assertSessionHas('error', trans('mail.delivery_disabled'));

        Passport::actingAs($admin);
        $this->postJson(route('api.settings.mailtest'))
            ->assertStatus(503)
            ->assertJsonPath('message', trans('mail.delivery_disabled'));
    }

    public function test_runtime_ldap_gate_preserves_local_login_and_blocks_connections(): void
    {
        config(['auth.ldap_integration_enabled' => false]);
        $this->settings->enableLdap();
        $user = User::factory()->create([
            'username' => 'local-emergency-admin',
            'ldap_import' => 0,
        ]);

        $this->assertFalse(\App\Models\Setting::ldapIsActive());

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LDAP integration is disabled for this environment.');
        Ldap::connectToLdap();
    }

    public function test_runtime_ldap_gate_blocks_enablement_import_and_sync(): void
    {
        config(['auth.ldap_integration_enabled' => false]);
        $this->settings->set(['ldap_enabled' => 0]);
        $admin = User::factory()->superuser()->create();

        $this->actingAs($admin)
            ->get(route('settings.ldap.index'))
            ->assertOk()
            ->assertSee(trans('admin/settings/general.ldap_runtime_disabled'))
            ->assertSee('id="ldap_enabled"', false)
            ->assertSee('disabled', false);

        $this->actingAs($admin)
            ->from(route('settings.ldap.index'))
            ->post(route('settings.ldap.save'), ['ldap_enabled' => 1])
            ->assertRedirect(route('settings.ldap.index'))
            ->assertSessionHasErrors('ldap_enabled');

        $this->actingAs($admin)
            ->get(route('ldap/user'))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', trans('admin/settings/general.ldap_runtime_disabled'));

        $this->artisan('snipeit:ldap-sync')
            ->expectsOutput('LDAP is disabled for this environment. Aborting without contacting a directory.')
            ->assertExitCode(1);

        Passport::actingAs($admin);
        $this->getJson(route('api.settings.ldaptest'))
            ->assertStatus(503)
            ->assertJsonPath('message', trans('admin/settings/general.ldap_runtime_disabled'));
    }
}
