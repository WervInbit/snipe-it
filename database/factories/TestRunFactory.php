<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestRunFactory extends Factory
{
    protected $model = TestRun::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 week', 'now');
        $end = (clone $start)->modify('+'.rand(1,2).' hours');

        return [
            'asset_id' => Asset::factory(),
            'user_id' => User::factory(),
            'started_at' => $start,
            'finished_at' => $end,
        ];
    }
}
