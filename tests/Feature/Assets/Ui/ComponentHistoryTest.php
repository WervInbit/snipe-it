<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetExpectedComponentState;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\ModelNumberComponentTemplate;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\Components\AssetExpectedComponentService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use App\Services\ComponentLifecycleService;
use Tests\TestCase;

class ComponentHistoryTest extends TestCase
{
    public function testAssetAddComponentFormUsesConcreteConditionChoices(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($actor)
            ->get(route('hardware.components.add', $asset))
            ->assertOk()
            ->assertSee('name="condition_code"', false)
            ->assertSee('value="'.ComponentInstance::CONDITION_UNKNOWN.'"', false)
            ->assertSee('value="'.ComponentInstance::CONDITION_GOOD.'"', false)
            ->assertSee('value="'.ComponentInstance::CONDITION_POOR.'"', false)
            ->assertSee('value="'.ComponentInstance::CONDITION_BROKEN.'"', false)
            ->assertDontSee('value="fair"', false);
    }

    public function testAssetComponentConditionCanBeUpdatedFromAssetPage(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'USB-C Port Board',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('USB-C Port Board')
            ->assertSeeText('Broken')
            ->assertSee(route('hardware.components.condition.update', [$asset, $component]), false)
            ->assertSee('name="condition_code"', false)
            ->assertDontSee('value="fair"', false);

        $token = 'asset-component-condition-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->post(route('hardware.components.condition.update', [$asset, $component]), [
                '_token' => $token,
                'condition_code' => ComponentInstance::CONDITION_POOR,
            ])
            ->assertRedirect(route('hardware.show', $asset))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $component->refresh();

        $this->assertSame(ComponentInstance::CONDITION_POOR, $component->condition_code);
        $this->assertSame(ComponentInstance::CONDITION_STATUS_DAMAGED, $component->condition_status);
        $this->assertDatabaseHas('component_events', [
            'component_instance_id' => $component->id,
            'event_type' => 'condition_updated',
            'performed_by' => $actor->id,
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('Poor');
    }

    public function testAssetComponentsTabRendersMobileCardsWithoutDuplicateConditionIds(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $expectedDefinition = ComponentDefinition::factory()->create([
            'name' => 'Battery Pack',
        ]);
        $extraDefinition = ComponentDefinition::factory()->create([
            'name' => 'Expansion SSD',
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $expectedDefinition->id,
            'expected_name' => 'Battery Pack',
        ]);

        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $extraDefinition->id,
            'display_name' => 'Expansion SSD',
            'condition_code' => ComponentInstance::CONDITION_POOR,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);

        $response = $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('data-testid="asset-components-desktop-table"', false)
            ->assertSee('data-testid="asset-components-mobile-list"', false)
            ->assertSee('data-testid="asset-component-card"', false)
            ->assertSee('data-component-card-badge="tracked"', false)
            ->assertSee('data-component-card-badge="condition"', false)
            ->assertDontSee('data-component-card-badge="expected"', false)
            ->assertSee('data-testid="asset-component-card-default-action"', false)
            ->assertSee('data-testid="asset-component-card-more"', false)
            ->assertSee(route('hardware.components.storage.store', [$asset, $component]), false)
            ->assertSee(route('hardware.components.transfer.create', [$asset, $component]), false)
            ->assertSeeText('Model baseline');

        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'id="asset_component_condition_table_'.$component->id.'"'));
        $this->assertSame(1, substr_count($content, 'id="asset_component_condition_mobile_'.$component->id.'"'));
        $this->assertSame(0, substr_count($content, 'id="asset_component_condition_'.$component->id.'"'));
    }

    public function testAssetHistoryShowsInstalledRemovedAndPostRemovalEventsForDeletedComponents(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $stock = ComponentStorageLocation::factory()->stock()->create();

        $component = ComponentInstance::factory()->inTray($actor)->create([
            'display_name' => '32GB DDR4',
            'source_asset_id' => null,
        ]);

        $service = app(ComponentLifecycleService::class);
        $service->installIntoAsset($component, $asset, [
            'performed_by' => $actor,
            'installed_as' => 'RAM Slot 1',
            'note' => 'Installed during intake.',
        ]);
        $service->removeToTray($component->fresh(), $actor, [
            'note' => 'Removed for teardown.',
        ]);
        $service->moveToStock($component->fresh(), $stock, [
            'performed_by' => $actor,
            'note' => 'Moved to stock after removal.',
        ]);

        $component->delete();

        $response = $this->actingAs($actor)->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee($component->component_tag);
        $response->assertSee('data-testid="asset-components-history-mobile-list"', false);
        $response->assertSee('data-testid="asset-component-history-card"', false);
        $response->assertSee('Installed');
        $response->assertSee('Removed To Tray');
        $response->assertSee('Moved To Stock');
        $response->assertSee('Deleted');
    }

    public function testAssetComponentsTabShowsCurrentRosterAndPrimaryAddAction(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'expected_name' => 'Battery Pack',
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('Add / Install Component')
            ->assertSeeText('Current Components')
            ->assertSeeText('Expected')
            ->assertSeeText('Battery Pack');
    }

    public function testAssetComponentsTabLinksExpectedDefinitionNameToDefinitionEditor(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'RAM STICK 4000',
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'RAM STICK 4000',
            'expected_qty' => 1,
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('<a href="'.e(route('settings.component_definitions.edit', $definition)).'">RAM STICK 4000</a>', false);
    }

    public function testAssetComponentsTabShowsExtrasBeforeExpectedBaseline(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Standard Memory',
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'Standard Memory',
        ]);

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $definition->id,
            'display_name' => 'Expansion Memory',
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeInOrder([
                'Extra',
                'Expected baseline',
                'Expected',
            ]);
    }

    public function testAssetComponentsTabKeepsRemovedExpectedRowsVisible(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Standard Memory',
        ]);

        $template = ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'Standard Memory',
            'expected_qty' => 1,
        ]);

        app(AssetExpectedComponentService::class)->materializeToTray($asset, $template, $actor);

        $response = $this->actingAs($actor)->get(route('hardware.show', $asset));

        $response->assertOk()
            ->assertSeeText('Removed')
            ->assertSeeText('Removed from this asset')
            ->assertDontSee('style="opacity: 0.6;"', false)
            ->assertDontSeeText('Expected baseline reduced');
    }

    public function testAssetComponentsTabLinksTrackedNamesAndTagsToComponentDetail(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Standard Memory',
        ]);
        $template = ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'Standard Memory',
            'expected_qty' => 1,
        ]);

        $removedComponent = app(AssetExpectedComponentService::class)->materializeToTray($asset, $template, $actor);
        $extraComponent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $definition->id,
            'display_name' => 'Expansion Memory',
        ]);

        $response = $this->actingAs($actor)->get(route('hardware.show', $asset));

        $response->assertOk()
            ->assertSee('<a href="'.e(route('components.show', $extraComponent)).'">Expansion Memory</a>', false)
            ->assertSee('<a href="'.e(route('components.show', $extraComponent)).'">'.$extraComponent->component_tag.'</a>', false)
            ->assertSee('<a href="'.e(route('components.show', $removedComponent)).'">Standard Memory</a>', false)
            ->assertSee('<a href="'.e(route('components.show', $removedComponent)).'">'.$removedComponent->component_tag.'</a>', false);
    }

    public function testAssetComponentsTabRendersHierarchyExpectedChildrenRemovedChildrenAndIssueBadges(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $stock = ComponentStorageLocation::factory()->stock()->create([
            'name' => 'Bench Stock',
        ]);
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Motherboard Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
        ]);
        $topLevelTemplate = ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $parentDefinition->id,
            'expected_name' => 'Motherboard Assembly',
            'expected_qty' => 1,
        ]);
        $childTemplate = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 3,
        ]);

        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $topLevelTemplate->id,
            'removed_qty' => 1,
        ]);

        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Installed Motherboard',
        ]);

        $subcomponents = app(ComponentExpectedSubcomponentService::class);
        $attachedChild = $subcomponents->materializeAttachedChild($parent, $childTemplate, $actor, [
            'condition_warning_confirmed' => true,
        ]);
        $attachedChild->forceFill([
            'display_name' => 'Damaged USB-C Port Board',
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
        ])->save();

        $removedChild = $subcomponents->materializeAttachedChild($parent, $childTemplate, $actor, [
            'condition_warning_confirmed' => true,
        ]);
        $removedChild->forceFill([
            'display_name' => 'Removed USB-C Port Board',
        ])->save();
        app(ComponentLifecycleService::class)->moveToStock($removedChild->fresh(), $stock, [
            'performed_by' => $actor,
            'note' => 'Removed from the board.',
        ]);

        $response = $this->actingAs($actor)->get(route('hardware.show', $asset));

        $response->assertOk()
            ->assertSee('data-testid="asset-component-row" data-component-classification="expected_tracked" data-component-depth="0"', false)
            ->assertSee('data-testid="asset-component-row" data-component-classification="extra" data-component-depth="1"', false)
            ->assertSee('data-testid="asset-component-expected-child-row"', false)
            ->assertSee('data-testid="asset-component-removed-child-row"', false)
            ->assertSee('data-testid="asset-component-card"', false)
            ->assertSee('data-component-classification="expected_tracked"', false)
            ->assertSee('data-component-classification="extra"', false)
            ->assertSee('data-component-depth="1"', false)
            ->assertSee('data-testid="asset-component-expected-child-card"', false)
            ->assertSee('data-testid="asset-component-removed-child-card"', false)
            ->assertSeeText('Installed Motherboard')
            ->assertSeeText('Damaged USB-C Port Board')
            ->assertSeeText('Removed USB-C Port Board')
            ->assertSeeText('USB-C-PORT')
            ->assertSeeText('Remaining: 1')
            ->assertSeeText('Removed from this parent component')
            ->assertSeeText('Broken')
            ->assertSeeInOrder([
                'Installed Motherboard',
                'Damaged USB-C Port Board',
                'Removed USB-C Port Board',
                'Expected Child',
            ]);
    }

    public function testHardwareDetailsShowExpectedAndExtraBreakdownForCalculatedComponents(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'unit' => 'GB',
            'allow_asset_override' => true,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Standard Memory',
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '16',
            'raw_value' => '16',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'Standard Memory',
            'expected_qty' => 2,
        ]);

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $definition->id,
            'display_name' => 'Expansion Memory',
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('48 GB')
            ->assertSeeText('Default subtotal: 32 GB')
            ->assertSeeText('Default parts: Standard Memory x2')
            ->assertSeeText('Extras/custom subtotal: 16 GB')
            ->assertSeeText('Extras/custom on top: Expansion Memory')
            ->assertDontSeeText('Calculated from components');
    }

    public function testHardwareDetailsCompactRoutineCalculatedComponentBreakdown(): void
    {
        $actor = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'unit' => 'GB',
            'allow_asset_override' => true,
        ]);
        $definition = ComponentDefinition::factory()->create([
            'name' => 'Standard Memory',
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '16',
            'raw_value' => '16',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'component_definition_id' => $definition->id,
            'expected_name' => 'Standard Memory',
            'expected_qty' => 2,
        ]);

        $this->actingAs($actor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('32 GB')
            ->assertSee('class="spec-source-indicator"', false)
            ->assertSee('data-tooltip="true"', false)
            ->assertSee('title="Parts: Standard Memory x2"', false)
            ->assertDontSeeText('Calculated from components')
            ->assertDontSeeText('Default subtotal: 32 GB')
            ->assertDontSeeText('Default parts: Standard Memory x2')
            ->assertDontSee('Expected/default', false);
    }
}
