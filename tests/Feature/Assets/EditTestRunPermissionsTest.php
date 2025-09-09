<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class EditTestRunPermissionsTest extends TestCase
{
    public function test_refurbisher_cannot_edit_others_run(): void
    {
        $asset = Asset::factory()->create();
        $type = TestType::factory()->create();
        $owner = User::factory()->refurbisher()->create();
        $other = User::factory()->refurbisher()->create();
        $run = TestRun::factory()->for($asset)->for($owner)->create();
        $result = TestResult::factory()->for($run)->for($type, 'type')->create([
            'status' => TestResult::STATUS_NVT,
        ]);

        $response = $this->actingAs($other)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_PASS],
            ]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_edit_any_run(): void
    {
        $asset = Asset::factory()->create();
        $type = TestType::factory()->create();
        $owner = User::factory()->refurbisher()->create();
        $admin = User::factory()->admin()->create();
        $run = TestRun::factory()->for($asset)->for($owner)->create([
            'finished_at' => now()->subDay(),
        ]);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create([
            'status' => TestResult::STATUS_FAIL,
        ]);

        $response = $this->actingAs($admin)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_PASS],
            ]
        );

        $response->assertRedirect(route('test-runs.index', $asset->id));
        $run->refresh();
        $this->assertNotNull($run->finished_at);
    }
}
