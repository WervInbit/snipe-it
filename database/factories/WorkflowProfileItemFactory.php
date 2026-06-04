<?php

namespace Database\Factories;

use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowProfileItemFactory extends Factory
{
    protected $model = WorkflowProfileItem::class;

    public function definition(): array
    {
        return [
            'workflow_profile_id' => WorkflowProfile::factory(),
            'workflow_item_id' => TestType::factory(),
            'sort_order' => 0,
            'is_required' => true,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
        ];
    }
}
