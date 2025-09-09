<?php

namespace Database\Factories;

use App\Models\TestType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TestTypeFactory extends Factory
{
    protected $model = TestType::class;

    public function definition()
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'tooltip' => $this->faker->sentence(),
            'category' => 'computer',
        ];
    }
}
