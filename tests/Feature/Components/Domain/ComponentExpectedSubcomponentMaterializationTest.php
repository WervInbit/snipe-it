<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\Components\ComponentExpectedSubcomponentService;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentExpectedSubcomponentMaterializationTest extends TestCase
{
    public function testExpectedSubcomponentMaterializesAsAttachedChildAndUpdatesState(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);

        $child = app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild($parent, $template, $actor, [
            'condition_warning_confirmed' => true,
            'note' => 'Visible during intake.',
        ]);

        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertSame($asset->id, $child->current_asset_id);
        $this->assertSame($asset->id, $child->root_asset_id);
        $this->assertSame(ComponentInstance::STATUS_INSTALLED, $child->status);
        $this->assertSame(ComponentInstance::SOURCE_EXPECTED_BASELINE, $child->source_type);
        $this->assertTrue($child->is_materialized_expected);
        $this->assertSame('Visible during intake.', $child->materialized_reason);
        $this->assertSame($template->id, data_get($child->metadata_json, 'component_definition_subcomponent_template_id'));
        $this->assertSame($parent->id, data_get($child->metadata_json, 'parent_component_instance_id'));

        $this->assertDatabaseHas('component_expected_subcomponent_states', [
            'component_instance_id' => $parent->id,
            'component_definition_subcomponent_template_id' => $template->id,
            'removed_qty' => 0,
            'materialized_qty' => 1,
        ]);

        $createdEvent = $child->fresh('events')->events->firstWhere('event_type', 'created');

        $this->assertNotNull($createdEvent);
        $this->assertSame('Visible during intake.', $createdEvent->note);
        $this->assertSame($template->id, data_get($createdEvent->payload_json, 'component_definition_subcomponent_template_id'));
    }

    public function testExpectedSubcomponentCannotMaterializeBeyondRemainingQuantity(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create();
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'expected_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
        ]);
        $service = app(ComponentExpectedSubcomponentService::class);

        $service->materializeAttachedChild($parent, $template, $actor, [
            'condition_warning_confirmed' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All expected units for this subcomponent have already been materialized or removed.');

        $service->materializeAttachedChild($parent->fresh(), $template, $actor, [
            'condition_warning_confirmed' => true,
        ]);
    }
}
