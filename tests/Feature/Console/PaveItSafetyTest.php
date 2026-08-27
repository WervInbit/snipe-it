<?php

namespace Tests\Feature\Console;

use App\Models\Asset;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class PaveItSafetyTest extends TestCase
{
    public function testForceCannotBypassDisposableEnvironmentGuard(): void
    {
        config()->set('demo.allow_disposable_data_seeding', false);
        $asset = Asset::factory()->create();

        $this->artisan('snipeit:pave', ['--force' => true])
            ->expectsOutput(
                'Database paving is restricted to local/testing environments with '
                .'SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true.',
            )
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }
}
