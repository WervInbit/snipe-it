<?php

namespace Database\Factories;

use App\Models\AssetModel;
use App\Models\ModelNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelNumberFactory extends Factory
{
    protected $model = ModelNumber::class;

    public function definition()
    {
        return [
            'model_id' => AssetModel::factory(),
            'code' => strtoupper($this->faker->bothify('MN-####')),
            'label' => $this->faker->words(3, true),
        ];
    }
}
