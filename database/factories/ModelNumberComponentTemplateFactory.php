<?php

namespace Database\Factories;

use App\Models\ComponentDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelNumberComponentTemplateFactory extends Factory
{
    protected $model = ModelNumberComponentTemplate::class;

    public function definition()
    {
        return [
            'model_number_id' => ModelNumber::factory(),
            'component_definition_id' => ComponentDefinition::factory(),
            'expected_name' => $this->faker->words(3, true),
            'slot_name' => $this->faker->optional()->randomElement(['RAM Slot 1', 'RAM Slot 2', 'SSD Bay', 'Battery']),
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
            'metadata_json' => null,
            'notes' => null,
        ];
    }
}
