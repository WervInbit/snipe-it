<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class TestAuditLoggingTest extends TestCase
{
    public function test_changes_to_result_and_run_are_audited(): void
    {
        $asset = Asset::factory()->create();
        $type = TestType::factory()->create();
        $user = User::factory()->refurbisher()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create([
            'finished_at' => now()->subDay(),
        ]);
        $result = TestResult::factory()->for($run)->for($type, 'type')
            ->create(['status' => TestResult::STATUS_FAIL]);

        $oldFinished = $run->finished_at;

        $this->actingAs($user)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_PASS],
                'note' => [$result->id => 'fixed'],
            ]
        )->assertRedirect(route('test-runs.index', $asset->id));

        $run->refresh();

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $result->id,
            'field' => 'status',
            'before' => TestResult::STATUS_FAIL,
            'after' => TestResult::STATUS_PASS,
        ]);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $result->id,
            'field' => 'note',
            'after' => 'fixed',
        ]);

        $this->assertDatabaseHas('test_audits', [
            'auditable_type' => TestRun::class,
            'auditable_id' => $run->id,
            'field' => 'finished_at',
            'before' => $oldFinished->format('Y-m-d H:i:s'),
            'after' => $run->finished_at->format('Y-m-d H:i:s'),
        ]);
    }
}
