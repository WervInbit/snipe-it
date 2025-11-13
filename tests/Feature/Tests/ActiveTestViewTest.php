<?php

namespace Tests\Feature\Tests;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActiveTestViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_view_renders_compact_layout(): void
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

        $response = $this->actingAs($user)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk();

        $response
            ->assertSee(trans('tests.two_column_toggle'))
            ->assertSee($failType->name)
            ->assertSee($passType->name)
            ->assertSee($openType->name)
            ->assertSee('data-action="set-pass"', false)
            ->assertSee('data-action="set-fail"', false)
            ->assertSee('data-testid="test-item-', false);
    }

    public function test_active_view_contains_note_and_photo_drawers(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'TAG-DRAWERS']);

        $run = TestRun::factory()
            ->for($asset)
            ->for($user)
            ->create();

        $type = TestType::factory()->create(['name' => 'Camera focus']);

        $result = TestResult::factory()->for($run)->for($type, 'type')->create([
            'status' => TestResult::STATUS_NVT,
        ]);

        $this->actingAs($user)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk()
            ->assertSee('data-action="toggle-note"', false)
            ->assertSee('data-action="toggle-photos"', false)
            ->assertSee('id="note-' . $result->id . '"', false)
            ->assertSee('id="photos-' . $result->id . '"', false);
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

    public function test_run_owner_without_asset_edit_can_update_results(): void
    {
        $user = $this->makeUserWithPermissions([
            'tests.execute' => '1',
            'assets.view' => '1',
            'refurbisher' => '1',
        ]);

        $asset = Asset::factory()->create(['asset_tag' => 'TAG-OWNED']);
        $run = TestRun::factory()->for($asset)->for($user)->create();
        TestResult::factory()->for($run)->create([
            'status' => TestResult::STATUS_NVT,
        ]);

        $this->actingAs($user)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk()
            ->assertSee('canUpdate: true', false);
    }

    public function test_non_owner_refurbisher_cannot_update_foreign_run(): void
    {
        $owner = $this->makeUserWithPermissions([
            'tests.execute' => '1',
            'assets.view' => '1',
            'refurbisher' => '1',
        ]);

        $viewer = $this->makeUserWithPermissions([
            'tests.execute' => '1',
            'assets.view' => '1',
            'refurbisher' => '1',
        ]);

        $asset = Asset::factory()->create(['asset_tag' => 'TAG-FOREIGN']);
        $run = TestRun::factory()->for($asset)->for($owner)->create();
        TestResult::factory()->for($run)->create([
            'status' => TestResult::STATUS_PASS,
        ]);

        $this->actingAs($viewer)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk()
            ->assertSee('canUpdate: false', false);
    }

    public function test_asset_editor_can_update_foreign_run(): void
    {
        $owner = $this->makeUserWithPermissions([
            'tests.execute' => '1',
            'assets.view' => '1',
        ]);

        $editor = $this->makeUserWithPermissions([
            'tests.execute' => '1',
            'assets.view' => '1',
            'assets.edit' => '1',
        ]);

        $asset = Asset::factory()->create(['asset_tag' => 'TAG-FOREIGN-EDIT']);
        $run = TestRun::factory()->for($asset)->for($owner)->create();
        TestResult::factory()->for($run)->create([
            'status' => TestResult::STATUS_FAIL,
        ]);

        $this->actingAs($editor)
            ->get("/hardware/{$asset->id}/tests/active")
            ->assertOk()
            ->assertSee('canUpdate: true', false);
    }

    private function makeUserWithPermissions(array $permissions): User
    {
        return User::factory()->create([
            'permissions' => json_encode($permissions),
        ]);
    }
}
