<?php

namespace Database\Factories;

use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentExpectedSubcomponentState;
use App\Models\ComponentInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponentExpectedSubcomponentStateFactory extends Factory
{
    protected $model = ComponentExpectedSubcomponentState::class;

    public function definition(): array
    {
        return [
            'component_instance_id' => ComponentInstance::factory(),
            'component_definition_subcomponent_template_id' => ComponentDefinitionSubcomponentTemplate::factory(),
            'removed_qty' => 0,
            'materialized_qty' => 0,
        ];
    }
}
