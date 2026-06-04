<?php

namespace Database\Factories;

use App\Models\TestType;
use App\Models\WorkflowProfileItem;
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
            'display_order' => 0,
            'tooltip' => $this->faker->sentence(),
            'instructions' => $this->faker->paragraph(),
            'attribute_definition_id' => null,
            'category' => null,
            'applies_to_all' => false,
            'is_required' => true,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
        ];
    }
}
