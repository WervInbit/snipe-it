<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class AllTestsPassedIndicatorTest extends TestCase
{
    public function testIndicatorShownWhenAllTestsPass(): void
    {
        $asset = Asset::factory()->create();
        $testType = TestType::factory()->create();
        $run = TestRun::factory()->create(['asset_id' => $asset->id]);
        $result = TestResult::factory()->create([
            'test_run_id' => $run->id,
            'test_type_id' => $testType->id,
            'status' => TestResult::STATUS_FAIL,
        ]);

        $asset->refreshTestCompletionFlag();
        $this->assertFalse($asset->tests_completed_ok);

        $user = User::factory()->viewAssets()->editAssets()->create();

        $this->actingAs($user)
            ->put(route('test-results.update', ['asset' => $asset->id, 'testRun' => $run->id]), [
                'status' => [
                    $result->id => TestResult::STATUS_PASS,
                ],
            ])
            ->assertRedirect(route('test-runs.index', $asset->id));

        $asset->refresh();
        $this->assertTrue($asset->tests_completed_ok);

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertSee(trans('tests.all_passed'));
    }

    public function testIndicatorHiddenWhenFailuresExist(): void
    {
        $asset = Asset::factory()->create();
        $run = TestRun::factory()->create(['asset_id' => $asset->id]);
        TestResult::factory()->create([
            'test_run_id' => $run->id,
            'status' => TestResult::STATUS_FAIL,
            'test_type_id' => TestType::factory()->create()->id,
        ]);

        $asset->refreshTestCompletionFlag();
        $this->assertFalse($asset->tests_completed_ok);

        $user = User::factory()->viewAssets()->create();

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertDontSee(trans('tests.all_passed'));
    }
}
