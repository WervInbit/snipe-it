<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;

class ActionlogFactory extends Factory
{
    protected $model = Actionlog::class;

    public function definition(): array
    {
        return $this->state(function () {
            $target = User::inRandomOrder()->first();

            // Try to pick an RTD asset, fall back to any asset
            $asset = Asset::inRandomOrder()->RTD()->first();
            if (!$asset) {
                $asset = Asset::inRandomOrder()->first();
            }

            // If either asset or target is missing, skip update safely
            if ($asset && $target) {
                $asset->update([
                    'assigned_to'   => $target->id,
                    'assigned_type' => User::class,
                    'location_id'   => $target->location_id,
                ]);
            }

            return [
                'action_type'   => 'checkout',
                'target_id'     => $target?->id,
                'target_type'   => User::class,
                'asset_id'      => $asset?->id,
                'created_at'    => now(),
                'updated_at'    => now(),
                'note'          => $this->faker->sentence,
            ];
        });
    }
}
