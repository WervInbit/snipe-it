<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    public function testActiveGoogleUserCanAuthenticate(): void
    {
        $user = User::factory()->create([
            'username' => 'active@example.com',
            'avatar' => 'old-avatar.png',
        ]);
        $this->mockGoogleIdentity('active@example.com', 'new-avatar.png');

        $this->get(route('google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('new-avatar.png', $user->fresh()->avatar);
    }

    public function testActiveGoogleUserResumesIntendedUrl(): void
    {
        $user = User::factory()->create([
            'username' => 'active@example.com',
        ]);
        $this->mockGoogleIdentity('active@example.com', 'new-avatar.png');
        $intendedUrl = route('scan.resolve', ['code' => 'AST-QR-123']);

        $this->withSession(['url.intended' => $intendedUrl])
            ->get(route('google.callback'))
            ->assertRedirect($intendedUrl)
            ->assertSessionMissing('url.intended');

        $this->assertAuthenticatedAs($user);
    }

    public function testDeactivatedGoogleUserIsRejectedWithoutMutation(): void
    {
        $user = User::factory()->create([
            'username' => 'inactive@example.com',
            'activated' => 0,
            'avatar' => 'old-avatar.png',
        ]);
        $this->mockGoogleIdentity('inactive@example.com', 'new-avatar.png');

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => trans('auth/message.account_not_activated'),
            ]);

        $this->assertGuest();
        $this->assertSame('old-avatar.png', $user->fresh()->avatar);
    }

    public function testDeletedGoogleUserIsRejected(): void
    {
        $user = User::factory()->deleted()->create([
            'username' => 'deleted@example.com',
            'avatar' => 'old-avatar.png',
        ]);
        $this->mockGoogleIdentity('deleted@example.com', 'new-avatar.png');

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => trans('auth/general.google_login_failed'),
            ]);

        $this->assertGuest();
        $this->assertSame(
            'old-avatar.png',
            User::withTrashed()->findOrFail($user->id)->avatar
        );
    }

    public function testGoogleIdentityRequiresExactStoredUsername(): void
    {
        $user = User::factory()->create([
            'username' => 'case-sensitive@example.com',
            'avatar' => 'old-avatar.png',
        ]);
        $this->mockGoogleIdentity('Case-Sensitive@example.com', 'new-avatar.png');

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => trans('auth/general.google_login_failed'),
            ]);

        $this->assertGuest();
        $this->assertSame('old-avatar.png', $user->fresh()->avatar);
    }

    private function mockGoogleIdentity(string $email, string $avatar): void
    {
        $socialUser = new SocialiteUser();
        $socialUser->email = $email;
        $socialUser->avatar = $avatar;

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
