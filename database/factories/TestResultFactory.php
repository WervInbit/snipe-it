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
            'test_run_id' => TestRun::factory(),
            'test_type_id' => TestType::factory(),
            'status' => $this->faker->randomElement(['pass', 'fail', 'skip']),
            'note' => $this->faker->sentence(),
        ];
    }
}
