<?php

namespace Tests\Feature\Components\Api;

use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\User;
use Tests\TestCase;

class ComponentCreationInvariantTest extends TestCase
{
    public function testApiCreationRejectsAnInactiveDefinition(): void
    {
        $actor = User::factory()->superuser()->create();
        $definition = ComponentDefinition::factory()->create(['is_active' => false]);

        $this->actingAsForApi($actor)
            ->postJson(route('api.components.store'), [
                'component_definition_id' => $definition->id,
                'condition_code' => ComponentInstance::CONDITION_GOOD,
            ])
            ->assertUnprocessable()
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('component_definition_id');

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testApiCreationReturnsSerialFieldErrorsForTrackingModeViolations(): void
    {
        $actor = User::factory()->superuser()->create();
        $required = ComponentDefinition::factory()->create([
            'serial_tracking_mode' => 'required',
        ]);
        $notTracked = ComponentDefinition::factory()->create([
            'serial_tracking_mode' => 'not_tracked',
        ]);

        foreach ([
            [
                'definition' => $required,
                'serial' => null,
                'message' => 'A serial number is required for this component definition.',
            ],
            [
                'definition' => $notTracked,
                'serial' => 'FORBIDDEN-SERIAL-001',
                'message' => 'This component definition does not track serial numbers.',
            ],
        ] as $case) {
            $this->actingAsForApi($actor)
                ->postJson(route('api.components.store'), [
                    'component_definition_id' => $case['definition']->id,
                    'serial' => $case['serial'],
                    'condition_code' => ComponentInstance::CONDITION_GOOD,
                ])
                ->assertUnprocessable()
                ->assertStatusMessageIs('error')
                ->assertMessagesContains('serial')
                ->assertJsonPath('messages.serial.0', $case['message']);
        }

        $this->assertDatabaseCount('component_instances', 0);
        $this->assertDatabaseCount('component_events', 0);
    }
}
