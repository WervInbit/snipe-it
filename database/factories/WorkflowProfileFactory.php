<?php

namespace Database\Factories;

use App\Models\WorkflowProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowProfileFactory extends Factory
{
    protected $model = WorkflowProfile::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'is_default' => false,
            'blocks_sale_readiness' => false,
            'display_order' => 0,
        ];
    }
}
