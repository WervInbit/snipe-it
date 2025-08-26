<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class TestAuditLogsTest extends TestCase
{
    public function test_audit_entries_created_for_test_run_events(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $this->actingAs($user);

        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $run->id,
            'actor_id' => $user->id,
            'field' => 'asset_id',
            'before' => null,
            'after' => (string) $asset->id,
        ]);

        $original = $run->finished_at;
        $newFinish = now()->addHour();
        $run->update(['finished_at' => $newFinish]);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $run->id,
            'actor_id' => $user->id,
            'field' => 'finished_at',
            'before' => $original ? $original->format('Y-m-d H:i:s') : null,
            'after' => $newFinish->format('Y-m-d H:i:s'),
        ]);

        $runId = $run->id;
        $assetId = $asset->id;
        $run->delete();

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $runId,
            'field' => 'asset_id',
            'before' => (string) $assetId,
            'after' => null,
        ]);
    }

    public function test_audit_entry_created_for_test_result_update(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $this->actingAs($user);

        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
        ]);
        $type = TestType::factory()->create();
        $result = TestResult::factory()->create([
            'test_run_id' => $run->id,
            'test_type_id' => $type->id,
            'note' => null,
        ]);

        $result->update(['note' => 'Checked']);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $result->id,
            'actor_id' => $user->id,
            'field' => 'note',
            'before' => null,
            'after' => 'Checked',
        ]);
    }
}
