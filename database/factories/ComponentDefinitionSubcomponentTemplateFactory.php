<?php

namespace Database\Factories;

use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponentDefinitionSubcomponentTemplateFactory extends Factory
{
    protected $model = ComponentDefinitionSubcomponentTemplate::class;

    public function definition(): array
    {
        return [
            'parent_component_definition_id' => ComponentDefinition::factory(),
            'child_component_definition_id' => ComponentDefinition::factory(),
            'expected_name' => $this->faker->words(3, true),
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
            'metadata_json' => null,
            'notes' => null,
        ];
    }
}
