<?php

namespace Database\Factories;

use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ComponentInstanceFactory extends Factory
{
    protected $model = ComponentInstance::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'qr_uid' => (string) Str::uuid(),
            'component_definition_id' => ComponentDefinition::factory(),
            'display_name' => $this->faker->words(3, true),
            'serial' => strtoupper($this->faker->bothify('SERIAL-######')),
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'condition_code' => ComponentInstance::CONDITION_GOOD,
            'source_type' => ComponentInstance::SOURCE_MANUAL,
            'storage_location_id' => ComponentStorageLocation::factory()->stock(),
            'supplier_id' => Supplier::factory(),
            'purchase_cost' => $this->faker->randomFloat(2, 2, 250),
            'received_at' => now()->subDay(),
            'metadata_json' => null,
            'notes' => null,
            'created_by' => User::factory()->superuser(),
            'updated_by' => User::factory()->superuser(),
        ];
    }

    public function installed(int $assetId): self
    {
        return $this->state([
            'status' => ComponentInstance::STATUS_INSTALLED,
            'current_asset_id' => $assetId,
            'storage_location_id' => null,
            'held_by_user_id' => null,
            'transfer_started_at' => null,
        ]);
    }

    public function inTray(?User $holder = null): self
    {
        return $this->state([
            'status' => ComponentInstance::STATUS_IN_TRANSFER,
            'storage_location_id' => null,
            'held_by_user_id' => $holder?->id ?? User::factory(),
            'transfer_started_at' => now()->subHour(),
        ]);
    }
}
