<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class StatusHistoryTest extends TestCase
{
    public function testStatusChangesAreLogged(): void
    {
        $user = User::factory()->editAssets()->create();
        $this->actingAs($user);

        $statusA = Statuslabel::factory()->create();
        $statusB = Statuslabel::factory()->create();
        $statusC = Statuslabel::factory()->create();

        $asset = Asset::factory()->create([
            'status_id' => $statusA->id,
        ]);

        $asset->status_id = $statusB->id;
        $asset->save();

        $asset->status_id = $statusC->id;
        $asset->save();

        $history = $asset->statusHistory()->orderBy('id')->get();

        $this->assertCount(2, $history);
        $this->assertEquals($statusA->id, $history[0]->old_status_id);
        $this->assertEquals($statusB->id, $history[0]->new_status_id);
        $this->assertEquals($statusB->id, $history[1]->old_status_id);
        $this->assertEquals($statusC->id, $history[1]->new_status_id);
        $this->assertEquals($user->id, $history[0]->changed_by);
        $this->assertNotNull($history[0]->changed_at);
    }
}

