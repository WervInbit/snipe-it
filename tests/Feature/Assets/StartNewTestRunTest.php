<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class StartNewTestRunTest extends TestCase
{
    public function test_start_new_run_creates_results_and_redirects(): void
    {
        $asset = Asset::factory()->create();
        TestType::factory()->count(3)->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('test-runs.store', $asset->id));
        $run = TestRun::where('asset_id', $asset->id)->latest()->first();

        $response->assertRedirect(route('test-results.edit', [$asset->id, $run->id]));
        $this->assertNotNull($run->started_at);
        $this->assertCount(TestType::count(), $run->results);
        $run->results->each(function ($result) {
            $this->assertEquals(TestResult::STATUS_NVT, $result->status);
            $this->assertNull($result->note);
        });
    }
}
