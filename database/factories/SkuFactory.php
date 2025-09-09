<?php

namespace Database\Factories;

use App\Models\Sku;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sku>
 */
class SkuFactory extends Factory
{
    protected $model = Sku::class;

    public function definition()
    {
        return [
            'model_id' => AssetModel::factory(),
            'name' => $this->faker->unique()->word(),
        ];
    }
}
