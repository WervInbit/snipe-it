<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\WorkOrderAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderAssetFactory extends Factory
{
    protected $model = WorkOrderAsset::class;

    public function definition()
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'asset_id' => Asset::factory(),
            'customer_label' => $this->faker->optional()->words(2, true),
            'asset_tag_snapshot' => strtoupper($this->faker->bothify('AST-#####')),
            'serial_snapshot' => strtoupper($this->faker->bothify('SER-#####')),
            'qr_reference' => null,
            'status' => 'pending',
            'sort_order' => 0,
        ];
    }
}
