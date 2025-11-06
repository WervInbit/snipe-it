<?php

namespace Tests\Feature\Tests;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveTestViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_view_renders_with_grouped_results(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'TAG-001']);

        $run = TestRun::factory()
            ->for($asset)
            ->for($user)
            ->create();

        $passType = TestType::factory()->create(['name' => 'Screen']);
        $failType = TestType::factory()->create(['name' => 'Battery']);
        $openType = TestType::factory()->create(['name' => 'Keyboard']);

        TestResult::factory()->for($run)->for($passType, 'type')->create([
            'status' => TestResult::STATUS_PASS,
        ]);

        TestResult::factory()->for($run)->for($failType, 'type')->create([
            'status' => TestResult::STATUS_FAIL,
        ]);

        TestResult::factory()->for($run)->for($openType, 'type')->create([
            'status' => TestResult::STATUS_NVT,
        ]);

        $this->actingAs($user)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk()
            ->assertSee('data-group-body="fail"', false)
            ->assertSee($failType->name)
            ->assertSee($passType->name)
            ->assertSee($openType->name)
            ->assertSee(route('hardware.edit', $asset), false);
    }

    public function test_scan_route_redirects_to_active_tests_for_testers(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'TAG-REDIRECT']);
        TestRun::factory()->for($asset)->for($user)->create();

        $this->actingAs($user)
            ->get(route('findbytag/hardware', $asset->asset_tag))
            ->assertRedirect("/hardware/{$asset->id}/tests/active");
    }
}
