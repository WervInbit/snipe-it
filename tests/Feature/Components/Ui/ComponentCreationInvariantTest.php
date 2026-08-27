<?php

namespace Tests\Feature\Components\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use Tests\TestCase;

class ComponentCreationInvariantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testLooseBrowserCreationRejectsAnInactiveDefinition(): void
    {
        $actor = User::factory()->superuser()->create();
        $location = ComponentStorageLocation::factory()->stock()->create();
        $definition = ComponentDefinition::factory()->create(['is_active' => false]);

        $this->actingAs($actor)
            ->post(route('components.store'), [
                'component_definition_id' => $definition->id,
                'source_type' => ComponentInstance::SOURCE_MANUAL,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
                'storage_location_id' => $location->id,
            ])
            ->assertSessionHas(
                'error',
                'The selected component definition is not active.'
            );

        $this->assertDatabaseMissing('component_instances', [
            'component_definition_id' => $definition->id,
        ]);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testAssetRegistrationRejectsARequiredDefinitionWithoutASerial(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $definition = ComponentDefinition::factory()->create([
            'serial_tracking_mode' => 'required',
        ]);

        $this->actingAs($actor)
            ->post(route('hardware.components.register', $asset), [
                'creation_mode' => 'definition',
                'component_definition_id' => $definition->id,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertSessionHas(
                'error',
                'A serial number is required for this component definition.'
            );

        $this->assertDatabaseMissing('component_instances', [
            'component_definition_id' => $definition->id,
        ]);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testChildRegistrationRejectsASerialForANotTrackedDefinition(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $definition = ComponentDefinition::factory()->create([
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
            'serial_tracking_mode' => 'not_tracked',
        ]);

        $this->actingAs($actor)
            ->post(route('components.children.store', $parent), [
                'creation_mode' => 'definition',
                'component_definition_id' => $definition->id,
                'serial' => 'FORBIDDEN-SERIAL-001',
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertSessionHas(
                'error',
                'This component definition does not track serial numbers.'
            );

        $this->assertDatabaseMissing('component_instances', [
            'parent_component_instance_id' => $parent->id,
            'component_definition_id' => $definition->id,
        ]);
        $this->assertDatabaseCount('component_events', 0);
    }
}
