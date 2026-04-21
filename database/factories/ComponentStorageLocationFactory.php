<?php

namespace Database\Factories;

use App\Models\ComponentStorageLocation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponentStorageLocationFactory extends Factory
{
    protected $model = ComponentStorageLocation::class;

    public function definition()
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'code' => $this->faker->unique()->slug(),
            'site_location_id' => Location::factory(),
            'type' => ComponentStorageLocation::TYPE_GENERAL,
            'is_active' => true,
        ];
    }

    public function stock(): self
    {
        return $this->state([
            'name' => 'Stock',
            'code' => 'stock-' . strtolower($this->faker->bothify('??##')),
            'type' => ComponentStorageLocation::TYPE_STOCK,
        ]);
    }

    public function verification(): self
    {
        return $this->state([
            'name' => 'Verification',
            'code' => 'verification-' . strtolower($this->faker->bothify('??##')),
            'type' => ComponentStorageLocation::TYPE_VERIFICATION,
        ]);
    }

    public function destruction(): self
    {
        return $this->state([
            'name' => 'Destruction',
            'code' => 'destruction-' . strtolower($this->faker->bothify('??##')),
            'type' => ComponentStorageLocation::TYPE_DESTRUCTION,
        ]);
    }
}
