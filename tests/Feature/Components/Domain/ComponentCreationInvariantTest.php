<?php

namespace Tests\Feature\Components\Domain;

use App\Models\ComponentDefinition;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentCreationInvariantTest extends TestCase
{
    public function testCreateInstanceRejectsAnInactiveDefinitionWithoutWritingAnything(): void
    {
        $actor = User::factory()->superuser()->create();
        $location = ComponentStorageLocation::factory()->stock()->create();
        $definition = ComponentDefinition::factory()->create(['is_active' => false]);

        try {
            app(ComponentLifecycleService::class)->createInstance(
                $this->stockAttributes($definition, $location),
                $actor
            );

            $this->fail('An inactive component definition must not create an instance.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'The selected component definition is not active.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('component_instances', [
            'component_definition_id' => $definition->id,
        ]);
        $this->assertDatabaseCount('component_events', 0);
    }

    public function testCreateInstanceHonorsRequiredAndNotTrackedSerialModes(): void
    {
        $actor = User::factory()->superuser()->create();
        $location = ComponentStorageLocation::factory()->stock()->create();
        $required = ComponentDefinition::factory()->create([
            'serial_tracking_mode' => 'required',
        ]);
        $notTracked = ComponentDefinition::factory()->create([
            'serial_tracking_mode' => 'not_tracked',
        ]);
        $service = app(ComponentLifecycleService::class);

        foreach ([
            [
                'definition' => $required,
                'serial' => null,
                'message' => 'A serial number is required for this component definition.',
            ],
            [
                'definition' => $notTracked,
                'serial' => 'MUST-NOT-BE-STORED',
                'message' => 'This component definition does not track serial numbers.',
            ],
        ] as $case) {
            try {
                $service->createInstance(
                    $this->stockAttributes($case['definition'], $location, $case['serial']),
                    $actor
                );

                $this->fail('The component serial must match its definition tracking mode.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame($case['message'], $exception->getMessage());
            }
        }

        $requiredInstance = $service->createInstance(
            $this->stockAttributes($required, $location, 'REQ-SERIAL-001'),
            $actor
        );
        $notTrackedInstance = $service->createInstance(
            $this->stockAttributes($notTracked, $location),
            $actor
        );

        $this->assertSame('REQ-SERIAL-001', $requiredInstance->serial);
        $this->assertNull($notTrackedInstance->serial);
        $this->assertSame(2, ComponentInstance::query()->count());
        $this->assertSame(2, ComponentEvent::query()->where('event_type', 'created')->count());
    }

    private function stockAttributes(
        ComponentDefinition $definition,
        ComponentStorageLocation $location,
        ?string $serial = null
    ): array {
        return [
            'component_definition_id' => $definition->id,
            'display_name' => $definition->name,
            'serial' => $serial,
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'storage_location_id' => $location->id,
        ];
    }
}
