<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        $history = $asset->statusEvents()->reorder('id')->get();

        $this->assertCount(3, $history);
        $this->assertNull($history[0]->from_status_id);
        $this->assertEquals($statusA->id, $history[0]->to_status_id);
        $this->assertEquals($statusA->id, $history[1]->from_status_id);
        $this->assertEquals($statusB->id, $history[1]->to_status_id);
        $this->assertEquals($statusB->id, $history[2]->from_status_id);
        $this->assertEquals($statusC->id, $history[2]->to_status_id);
        $this->assertEquals($user->id, $history[0]->triggered_by);
        $this->assertNotNull($history[0]->created_at);
    }

    public function testLegacyStatusHistoryBackfillIsIdempotent(): void
    {
        $user = User::factory()->create();
        $statusA = Statuslabel::factory()->create();
        $statusB = Statuslabel::factory()->create();
        $asset = Asset::factory()->create(['status_id' => $statusB->id]);
        $changedAt = now()->subDay()->startOfSecond();

        $legacyId = DB::table('asset_status_history')->insertGetId([
            'asset_id' => $asset->id,
            'old_status_id' => $statusA->id,
            'new_status_id' => $statusB->id,
            'changed_by' => $user->id,
            'changed_at' => $changedAt,
        ]);

        $migration = require database_path(
            'migrations/2026_07_28_120000_backfill_legacy_asset_status_history.php',
        );

        $migration->up();
        $migration->up();

        $events = DB::table('asset_status_events')
            ->where('legacy_history_id', $legacyId)
            ->get();

        $this->assertCount(1, $events);
        $this->assertSame($asset->id, $events[0]->asset_id);
        $this->assertSame($statusA->id, $events[0]->from_status_id);
        $this->assertSame($statusB->id, $events[0]->to_status_id);
        $this->assertSame($user->id, $events[0]->triggered_by);
        $this->assertNull($events[0]->note);
        $this->assertSame($changedAt->toDateTimeString(), $events[0]->created_at);
    }
}

