<?php

namespace Tests\Feature\Components\Ui;

use App\Models\ComponentDefinition;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class EditComponentTest extends TestCase
{
    public function testAuthorizedUserCanOpenTheComponentEditPage(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.edit', $component))
            ->assertOk()
            ->assertSee(__('Edit Component'))
            ->assertSee($component->component_tag)
            ->assertSee($component->serial);
    }

    public function testUserWithoutUpdatePermissionCannotOpenOrSubmitTheEditForm(): void
    {
        $component = ComponentInstance::factory()->create();
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->get(route('components.edit', $component))
            ->assertForbidden();

        $this->actingAs($actor)
            ->put(route('components.update', $component), [
                'display_name' => 'Unauthorized change',
            ])
            ->assertForbidden();

        $this->assertNotSame('Unauthorized change', $component->fresh()->getRawOriginal('display_name'));
    }

    public function testEditFormUpdatesMetadataSerialAndConditionWithAuditEvents(): void
    {
        $component = ComponentInstance::factory()->create();
        $supplier = Supplier::factory()->create();
        $actor = User::factory()->superuser()->create();
        $originalLifecycle = $component->lifecycle_status;
        $originalPlacement = [
            $component->current_asset_id,
            $component->parent_component_instance_id,
            $component->storage_location_id,
        ];

        $this->actingAs($actor)
            ->put(route('components.update', $component), [
                'component_definition_id' => $component->component_definition_id,
                'display_name' => 'Verified replacement display',
                'serial' => 'EDIT-SERIAL-001',
                'condition_code' => ComponentInstance::CONDITION_POOR,
                'supplier_id' => $supplier->id,
                'purchase_cost' => '49.9500',
                'received_at' => '2026-07-20',
                'notes' => 'Inspected during intake.',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success');

        $component->refresh();

        $this->assertSame('Verified replacement display', $component->getRawOriginal('display_name'));
        $this->assertSame('EDIT-SERIAL-001', $component->serial);
        $this->assertSame(ComponentInstance::CONDITION_POOR, $component->condition_code);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertSame($supplier->id, $component->supplier_id);
        $this->assertSame('49.9500', $component->purchase_cost);
        $this->assertSame('2026-07-20', $component->received_at?->format('Y-m-d'));
        $this->assertSame('Inspected during intake.', $component->notes);
        $this->assertSame($actor->id, $component->updated_by);
        $this->assertSame($originalLifecycle, $component->lifecycle_status);
        $this->assertSame($originalPlacement, [
            $component->current_asset_id,
            $component->parent_component_instance_id,
            $component->storage_location_id,
        ]);

        $this->assertSame(
            ['serial_updated', 'condition_updated', 'metadata_updated'],
            ComponentEvent::query()
                ->where('component_instance_id', $component->id)
                ->whereIn('event_type', ['serial_updated', 'condition_updated', 'metadata_updated'])
                ->orderBy('id')
                ->pluck('event_type')
                ->all()
        );
    }

    public function testTerminalComponentEditFailsClosedWithoutMutation(): void
    {
        $component = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_DESTROYED_RECYCLED,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_DESTROYED,
            'storage_location_id' => null,
            'destroyed_at' => now(),
        ]);
        $originalName = $component->getRawOriginal('display_name');

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.edit', $component))
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('error');

        $this->from(route('components.show', $component))
            ->actingAs(User::factory()->superuser()->create())
            ->put(route('components.update', $component), [
                'component_definition_id' => $component->component_definition_id,
                'display_name' => 'Rewritten terminal record',
                'serial' => $component->serial,
                'condition_code' => $component->condition_code,
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('error');

        $this->assertSame($originalName, $component->fresh()->getRawOriginal('display_name'));
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'metadata_updated',
        ]);
    }

    public function testDefinitionChangeWithLiveChildrenRollsBackTheWholeEdit(): void
    {
        $component = ComponentInstance::factory()->create();
        ComponentInstance::factory()->asChildOf($component)->create();
        $replacementDefinition = ComponentDefinition::factory()->create();
        $originalName = $component->getRawOriginal('display_name');
        $originalSerial = $component->serial;

        $this->from(route('components.edit', $component))
            ->actingAs(User::factory()->superuser()->create())
            ->put(route('components.update', $component), [
                'component_definition_id' => $replacementDefinition->id,
                'display_name' => 'Must roll back',
                'serial' => 'MUST-ROLL-BACK',
                'condition_code' => ComponentInstance::CONDITION_BROKEN,
            ])
            ->assertRedirect(route('components.edit', $component))
            ->assertSessionHas('error');

        $component->refresh();
        $this->assertSame($originalName, $component->getRawOriginal('display_name'));
        $this->assertSame($originalSerial, $component->serial);
        $this->assertSame(ComponentInstance::CONDITION_GOOD, $component->condition_code);
        $this->assertNotSame($replacementDefinition->id, $component->component_definition_id);
        $this->assertDatabaseMissing('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'serial_updated',
        ]);
    }
}
