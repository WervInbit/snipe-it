<?php

namespace Database\Factories;

use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestResultFactory extends Factory
{
    protected $model = TestResult::class;

    public function definition()
    {
        return [
            'workflow_run_id' => TestRun::factory(),
            'workflow_item_id' => TestType::factory(),
            'status' => $this->faker->randomElement(['pass', 'fail', 'nvt']),
            'note' => $this->faker->sentence(),
            'is_required' => true,
            'result_label_mode' => 'pass_fail',
            'sort_order' => 0,
        ];
    }
}
