<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\License;
use App\Models\Location;
use App\Models\User;

class ActionlogFactory extends Factory
{
    protected $model = Actionlog::class;

    /**
     * Generic default: a simple note.
     */
    public function definition(): array
    {
        return [
            'action_type' => 'note',
            'target_id'   => null,
            'target_type' => null,
            'item_id'     => Asset::factory(),
            'item_type'   => Asset::class,
            'note'        => $this->faker->sentence,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    public function filesUploaded(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get()),
            'action_type' => 'uploaded',
            'item_type' => User::class,
            'filename' => (string) $this->faker->unixTime('now'),
        ]);
    }

    public function acceptedSignature(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get()),
            'action_type' => 'accepted',
            'item_id' => Asset::factory(),
            'item_type' => Asset::class,
            'target_type' => User::class,
            'accept_signature' => (string) $this->faker->unixTime('now'),
        ]);
    }

    public function acceptedEula(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get()),
            'action_type' => 'accepted',
            'item_id' => Asset::factory(),
            'item_type' => Asset::class,
            'target_type' => User::class,
            'filename' => (string) $this->faker->unixTime('now'),
        ]);
    }

    public function userUpdated(): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get()),
            'action_type' => 'update',
            'target_type' => User::class,
            'item_type' => User::class,
        ]);
    }

    /**
     * Checkout a random asset to a random user.
     * Uses polymorphic item (item_type/item_id).
     */
    public function assetCheckoutToUser(): static
    {
        return $this->state(function () {
            $target = User::inRandomOrder()->first();
            $asset  = Asset::inRandomOrder()->RTD()->first() ?: Asset::inRandomOrder()->first();

            // Guard against nulls; if both exist, reflect assignment on the asset
            if ($asset && $target) {
                $asset->update([
                    'assigned_to'   => $target->id,
                    'assigned_type' => User::class,
                    'location_id'   => $target->location_id,
                ]);
            }

            return [
                'action_type' => 'checkout',
                'target_id'   => $target?->id,
                'target_type' => $target ? User::class : null,
                'item_id'     => $asset?->id,
                'item_type'   => $asset ? Asset::class : null,
                'note'        => $this->faker->sentence,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        });
    }

    /**
     * Checkout a random asset to a random location.
     */
    public function assetCheckoutToLocation(): static
    {
        return $this->state(function () {
            $location = Location::inRandomOrder()->first();
            $asset    = Asset::inRandomOrder()->RTD()->first() ?: Asset::inRandomOrder()->first();

            if ($asset && $location) {
                // Move the asset; clear user assignment
                $asset->update([
                    'location_id'   => $location->id,
                    'assigned_to'   => null,
                    'assigned_type' => null,
                ]);
            }

            return [
                'action_type' => 'checkout',
                'target_id'   => $location?->id,
                'target_type' => $location ? Location::class : null,
                'item_id'     => $asset?->id,
                'item_type'   => $asset ? Asset::class : null,
                'note'        => $this->faker->sentence,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        });
    }

    /**
     * Checkout a random license to a random user.
     * IMPORTANT: Do NOT try to update columns on licenses; this schema
     * does not have assigned_to/assigned_type on the licenses table.
     * We only log the action via action_logs (item_type/item_id).
     */
    public function licenseCheckoutToUser(): static
    {
        return $this->state(function () {
            $target  = User::inRandomOrder()->first();
            $license = License::inRandomOrder()->first();

            // No $license->update([...]) here — those columns don't exist.
            return [
                'action_type' => 'checkout',
                'target_id'   => $target?->id,
                'target_type' => $target ? User::class : null,
                'item_id'     => $license?->id,
                'item_type'   => $license ? License::class : null,
                'note'        => $this->faker->sentence,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        });
    }
}
