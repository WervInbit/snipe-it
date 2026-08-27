<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{

    public function testRateLimit()
    {
        config(['app.api_throttle_per_minute' => 10]);
        $user = User::factory()->create();
        $this->clearApiLimitFor($user);

        $this->actingAsForApi($user)
            ->getJson(route('api.users.me'))
            ->assertOk()
            ->assertHeader('X-Ratelimit-Limit', config('app.api_throttle_per_minute'))
            ->assertHeader('X-Ratelimit-Remaining', 9);
    }

    public function testRateLimitDecreasesRemaining()
    {
        config(['app.api_throttle_per_minute' => 5]);
        $expected_remaining = (config('app.api_throttle_per_minute') - 1);
        $admin = User::factory()->create();
        $this->clearApiLimitFor($admin);

        for ($x = 0; $x < 5; $x++) {

            $this->actingAsForApi($admin)
                ->getJson(route('api.users.me'))
                ->assertOk()
                ->assertHeader('X-Ratelimit-Remaining', $expected_remaining--);

        }

        $response = $this->actingAsForApi($admin)
            ->getJson(route('api.users.me'))
            ->assertStatus(429);

        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
        $response->assertJsonPath('retryAfter', $retryAfter);
    }

    public function testRateLimitDecreasesRemainingOverSixty()
    {
        config(['app.api_throttle_per_minute' => 80]);
        $expected_remaining = (config('app.api_throttle_per_minute') - 1);
        $admin = User::factory()->create();
        $this->clearApiLimitFor($admin);

        for ($x = 0; $x < 5; $x++) {

            $this->actingAsForApi($admin)
                ->getJson(route('api.users.me'))
                ->assertOk()
                ->assertHeader('X-Ratelimit-Remaining', $expected_remaining--);

        }

        $response = $this->actingAsForApi($admin)
            ->getJson(route('api.users.me'))
            ->assertStatus(200)
            ->assertHeader('X-Ratelimit-Remaining', $expected_remaining);

        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
    }

    private function clearApiLimitFor(User $user): void
    {
        RateLimiter::clear(md5('api'.$user->getAuthIdentifier()));
    }
}
