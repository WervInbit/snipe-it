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
        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'tests.execute' => '1',
                'tests.edit_runs' => '1',
            ]),
        ]);
        $run = TestRun::factory()->for($asset)->for($user)->create([
            'finished_at' => now()->subDay(),
        ]);
        $result = TestResult::factory()->for($run)->for($type, 'type')
            ->create(['status' => TestResult::STATUS_FAIL]);

        $oldFinished = $run->finished_at;
        $finishedAuditCount = $run->audits()->where('field', 'finished_at')->count();

        $this->actingAs($user)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_PASS],
                'note' => [$result->id => 'fixed'],
            ]
        )->assertRedirect(route('test-runs.index', $asset->id));

        $run->refresh();

        $this->assertDatabaseHas('workflow_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $result->id,
            'field' => 'status',
            'before' => TestResult::STATUS_FAIL,
            'after' => TestResult::STATUS_PASS,
        ]);

        $this->assertDatabaseHas('workflow_audits', [
            'auditable_type' => TestResult::class,
            'auditable_id' => $result->id,
            'field' => 'note',
            'after' => 'fixed',
        ]);

        $this->assertSame(
            $finishedAuditCount,
            $run->audits()->where('field', 'finished_at')->count()
        );
        $this->assertTrue($oldFinished->equalTo($run->finished_at));
    }
}
