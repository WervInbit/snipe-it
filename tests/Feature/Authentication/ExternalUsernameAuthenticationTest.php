<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use App\Services\Saml;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ExternalUsernameAuthenticationTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    public function testRemoteUserRequiresExactStoredUsername(): void
    {
        $this->enableRemoteUser();
        User::factory()->create(['username' => 'remote-user']);

        $this->withServerVariables(['REMOTE_USER' => 'Remote-User'])
            ->get(route('login'))
            ->assertOk();

        $this->assertGuest();
    }

    public function testRemoteUserStillAuthenticatesOnExactMatch(): void
    {
        $this->enableRemoteUser();
        $user = User::factory()->create(['username' => 'remote-user']);

        $this->withServerVariables(['REMOTE_USER' => 'remote-user'])
            ->get(route('login'))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function testSamlLoginRequiresExactStoredUsername(): void
    {
        User::factory()->create(['username' => 'saml-user']);

        $this->assertNull((new Saml())->samlLogin([
            'nameId' => 'Saml-User',
            'attributes' => [],
        ]));
    }

    public function testSamlLoginStillResolvesExactUsername(): void
    {
        $user = User::factory()->create(['username' => 'saml-user']);

        $resolved = (new Saml())->samlLogin([
            'nameId' => 'saml-user',
            'attributes' => [],
        ]);

        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->id);
    }

    #[Group('ldap')]
    public function testLdapIdentityVariantCannotTakeOverExistingLocalUser(): void
    {
        $this->settings->enableLdap()->set([
            'ldap_tls' => 0,
            'ldap_username_field' => 'uid',
            'ldap_fname_field' => 'givenname',
            'ldap_lname_field' => 'sn',
            'ldap_email' => 'mail',
            'ldap_auth_filter_query' => 'uid=',
            'ldap_filter' => '&',
            'ldap_pw_sync' => 0,
        ]);

        $localUser = User::factory()->create([
            'username' => 'ldap-user',
            'first_name' => 'Original',
            'ldap_import' => 1,
        ]);

        $this->getFunctionMock('App\\Models', 'ldap_connect')
            ->expects($this->once())
            ->willReturn('ldap-connection');
        $this->getFunctionMock('App\\Models', 'ldap_set_option')
            ->expects($this->exactly(3))
            ->willReturn(true);
        $this->getFunctionMock('App\\Models', 'ldap_bind')
            ->expects($this->once())
            ->willReturn(true);
        $this->getFunctionMock('App\\Models', 'ldap_search')
            ->expects($this->once())
            ->willReturn(true);
        $this->getFunctionMock('App\\Models', 'ldap_first_entry')
            ->expects($this->once())
            ->willReturn(true);
        $this->getFunctionMock('App\\Models', 'ldap_get_attributes')
            ->expects($this->once())
            ->willReturn([
                'count' => 4,
                'uid' => ['count' => 1, 0 => 'LDAP-USER'],
                'givenname' => ['count' => 1, 0 => 'External'],
                'sn' => ['count' => 1, 0 => 'Identity'],
                'mail' => ['count' => 1, 0 => 'external@example.com'],
            ]);

        $this->post(route('login'), [
            'username' => 'LDAP-USER',
            'password' => 'ldap-only-password',
        ])->assertStatus(302);

        $this->assertNotSame($localUser->id, Auth::id());
        $this->assertSame('Original', $localUser->fresh()->first_name);
    }

    private function enableRemoteUser(): void
    {
        $this->settings->set([
            'login_remote_user_enabled' => 1,
            'login_remote_user_header_name' => 'REMOTE_USER',
            'login_common_disabled' => 0,
            'saml_enabled' => 0,
        ]);
    }
}
