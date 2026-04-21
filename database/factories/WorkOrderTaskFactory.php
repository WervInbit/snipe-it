<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAsset;
use App\Models\WorkOrderTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderTaskFactory extends Factory
{
    protected $model = WorkOrderTask::class;

    public function definition()
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'work_order_asset_id' => WorkOrderAsset::factory(),
            'task_type' => 'general',
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => 'pending',
            'customer_visible' => true,
            'customer_status_label' => null,
            'assigned_to' => User::factory(),
            'started_at' => null,
            'completed_at' => null,
            'sort_order' => 0,
            'notes_internal' => null,
            'notes_customer' => null,
        ];
    }
}
