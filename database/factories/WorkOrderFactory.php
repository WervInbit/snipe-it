<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'work_order_number' => 'WO-' . strtoupper($this->faker->bothify('######')),
            'company_id' => Company::factory(),
            'primary_contact_user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => 'draft',
            'priority' => $this->faker->optional()->randomElement(['low', 'normal', 'high']),
            'visibility_profile' => 'full',
            'portal_visibility_json' => null,
            'intake_date' => today(),
            'due_date' => today()->addWeek(),
            'created_by' => User::factory()->superuser(),
            'updated_by' => User::factory()->superuser(),
        ];
    }
}
