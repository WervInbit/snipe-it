<?php

namespace Tests\Feature\Components\Console;

use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeTrayComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function testTrayAgingOnlyEscalatesStaleComponentsAndMarksHistoryAsAutomatic(): void
    {
        $user = User::factory()->superuser()->create();
        $staleComponent = ComponentInstance::factory()->inTray($user)->create([
            'transfer_started_at' => now()->subHours(30),
        ]);
        $freshComponent = ComponentInstance::factory()->inTray($user)->create([
            'transfer_started_at' => now()->subHours(1),
        ]);

        $this->artisan('components:age-tray')
            ->assertExitCode(0);

        $staleComponent->refresh();
        $freshComponent->refresh();

        $this->assertSame(ComponentInstance::STATUS_NEEDS_VERIFICATION, $staleComponent->status);
        $this->assertSame(ComponentInstance::STATUS_IN_TRANSFER, $freshComponent->status);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $staleComponent->id,
            'event_type' => 'flagged_needs_verification',
        ]);

        $staleEvent = $staleComponent->events()->firstOrFail();
        $this->assertTrue((bool) data_get($staleEvent->payload_json, 'aged_from_transfer'));

        $this->actingAs($user)
            ->get(route('components.show', $staleComponent))
            ->assertOk()
            ->assertSee('Auto-Flagged Needs Verification');
    }
}
