<?php

namespace Database\Factories;

use App\Models\TestAudit;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestAuditFactory extends Factory
{
    protected $model = TestAudit::class;

    public function definition()
    {
        return [
            'auditable_type' => TestResult::class,
            'auditable_id' => TestResult::factory(),
            'user_id' => User::factory(),
            'field' => $this->faker->word(),
            'before' => $this->faker->sentence(),
            'after' => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
