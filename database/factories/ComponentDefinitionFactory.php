<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ComponentDefinitionFactory extends Factory
{
    protected $model = ComponentDefinition::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory()->forComponents(),
            'manufacturer_id' => Manufacturer::factory(),
            'model_number' => strtoupper($this->faker->bothify('MDL-####')),
            'part_code' => strtoupper($this->faker->bothify('PART-#####')),
            'spec_summary' => $this->faker->sentence(),
            'metadata_json' => null,
            'serial_tracking_mode' => 'optional',
            'is_active' => true,
            'created_by' => User::factory()->superuser(),
            'updated_by' => User::factory()->superuser(),
        ];
    }
}
