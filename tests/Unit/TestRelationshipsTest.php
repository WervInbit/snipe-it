<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use Tests\TestCase;

class TestRelationshipsTest extends TestCase
{
    public function test_test_run_asset_relationship()
    {
        $asset = Asset::factory()->create();
        $run = TestRun::factory()->create(['asset_id' => $asset->id]);

        $this->assertTrue($run->asset->is($asset));
    }

    public function test_test_run_results_relationship()
    {
        $run = TestRun::factory()->create();
        $result = TestResult::factory()->create([
            'test_run_id' => $run->id,
            'status' => TestResult::STATUS_PASS,
        ]);

        $this->assertTrue($run->results->first()->is($result));
    }

    public function test_test_result_test_run_relationship()
    {
        $run = TestRun::factory()->create();
        $result = TestResult::factory()->create([
            'test_run_id' => $run->id,
            'status' => TestResult::STATUS_PASS,
        ]);

        $this->assertTrue($result->testRun->is($run));
    }

    public function test_test_result_type_relationship()
    {
        $type = TestType::factory()->create();
        $result = TestResult::factory()->create([
            'test_type_id' => $type->id,
            'status' => TestResult::STATUS_PASS,
        ]);

        $this->assertTrue($result->type->is($type));
    }

    public function test_test_type_results_relationship()
    {
        $type = TestType::factory()->create();
        $result = TestResult::factory()->create([
            'test_type_id' => $type->id,
            'status' => TestResult::STATUS_PASS,
        ]);

        $this->assertTrue($type->results->first()->is($result));
    }

    public function test_asset_tests_relationship()
    {
        $asset = Asset::factory()->create();
        $run = TestRun::factory()->create(['asset_id' => $asset->id]);

        $this->assertTrue($asset->tests->first()->is($run));
    }
}

