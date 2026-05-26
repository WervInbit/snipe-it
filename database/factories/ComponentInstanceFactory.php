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
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
            'condition_code' => ComponentInstance::CONDITION_GOOD,
            'condition_status' => ComponentInstance::CONDITION_STATUS_GOOD,
            'source_type' => ComponentInstance::SOURCE_MANUAL,
            'storage_location_id' => ComponentStorageLocation::factory()->stock(),
            'parent_component_instance_id' => null,
            'root_asset_id' => null,
            'is_materialized_expected' => false,
            'materialized_reason' => null,
            'ancestry_parent_component_instance_id' => null,
            'ancestry_attached_through_at' => null,
            'ancestry_attached_through_event_id' => null,
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
            'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
            'current_asset_id' => $assetId,
            'root_asset_id' => $assetId,
            'parent_component_instance_id' => null,
            'storage_location_id' => null,
            'held_by_user_id' => null,
            'transfer_started_at' => null,
        ]);
    }

    public function asChildOf(ComponentInstance $parent): self
    {
        return $this->state([
            'status' => ComponentInstance::STATUS_INSTALLED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
            'parent_component_instance_id' => $parent->id,
            'current_asset_id' => $parent->current_asset_id,
            'root_asset_id' => $parent->root_asset_id ?: $parent->current_asset_id,
            'storage_location_id' => null,
            'held_by_user_id' => null,
            'transfer_started_at' => null,
        ]);
    }

    public function inTray(?User $holder = null): self
    {
        return $this->state([
            'status' => ComponentInstance::STATUS_IN_TRANSFER,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_TRAY,
            'storage_location_id' => null,
            'held_by_user_id' => $holder?->id ?? User::factory(),
            'transfer_started_at' => now()->subHour(),
        ]);
    }
}
