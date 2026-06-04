<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetExpectedComponentState;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use App\Services\Components\AssetComponentRosterService;
use App\Services\Components\AttachedComponentIssueService;
use App\Services\Components\ComponentExpectedSubcomponentService;
use App\Services\ComponentLifecycleService;
use App\Services\ModelAttributes\ComponentInstanceAttributeManager;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentDerivedAttributeResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_components_drive_numeric_model_values_when_resolved_to_spec(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'Memory Module',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '24',
            'raw_value' => '24',
            'display_order' => 0,
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForModelNumber($modelNumber)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('16', $resolved['ram_capacity_gb']->value);
        $this->assertSame('calculated_components', $resolved['ram_capacity_gb']->source);
        $this->assertSame('24', $resolved['ram_capacity_gb']->manualModelValue);
        $this->assertSame('Memory Module x2', $resolved['ram_capacity_gb']->contributorSummary('calculated_components'));
    }

    public function test_expected_components_drive_text_model_values_when_resolved_to_spec(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $resolution = AttributeDefinition::create([
            'key' => 'display_resolution',
            'label' => 'Display Resolution',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Display 15.6 FHD',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $resolution->id,
            'value' => '1920 x 1080',
            'raw_value' => '1920 x 1080',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'Display 15.6 FHD',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $resolution->id,
            'value' => '1366 x 768',
            'raw_value' => '1366 x 768',
            'display_order' => 0,
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForModelNumber($modelNumber)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('1920 x 1080', $resolved['display_resolution']->value);
        $this->assertSame('calculated_components', $resolved['display_resolution']->source);
        $this->assertSame('1366 x 768', $resolved['display_resolution']->manualModelValue);
        $this->assertSame('Display 15.6 FHD', $resolved['display_resolution']->contributorSummary('calculated_components'));
    }

    public function test_installed_components_drive_numeric_asset_values_even_with_asset_override(): void
    {
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        $asset = Asset::factory()->create();

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'DIMM A',
        ]);
        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'DIMM B',
        ]);
        AssetAttributeOverride::create([
            'asset_id' => $asset->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '20',
            'raw_value' => '20',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('16', $resolved['ram_capacity_gb']->value);
        $this->assertSame('calculated_components', $resolved['ram_capacity_gb']->source);
        $installedContributors = $resolved['ram_capacity_gb']->contributorsFor('calculated_components');
        $this->assertCount(1, $installedContributors);
        $this->assertCount(2, $installedContributors[0]['contributors']);
    }

    public function test_instance_attribute_overrides_definition_attribute_for_asset_resolution(): void
    {
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        $asset = Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'DIMM A',
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($component, [
            [
                'attribute_definition_id' => $capacity->id,
                'value' => '16',
            ],
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('16', $resolved['ram_capacity_gb']->value);
        $this->assertSame('calculated_components', $resolved['ram_capacity_gb']->source);
        $contributors = $resolved['ram_capacity_gb']->contributorsFor('calculated_components');
        $this->assertSame('instance', $contributors[0]['contributors'][0]['attribute_source']);
    }

    public function test_definition_attribute_remains_when_instance_override_is_absent(): void
    {
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        $asset = Asset::factory()->create();
        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'DIMM A',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('8', $resolved['ram_capacity_gb']->value);
        $contributors = $resolved['ram_capacity_gb']->contributorsFor('calculated_components');
        $this->assertSame('definition', $contributors[0]['contributors'][0]['attribute_source']);
    }

    public function test_custom_child_component_instance_attribute_contributes_to_asset_resolution(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $asset = Asset::factory()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $parentDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);
        $customChild = ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => null,
            'display_name' => 'Custom USB-C Daughterboard',
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($customChild, [
            [
                'attribute_definition_id' => $usbPorts->id,
                'value' => '2',
                'resolves_to_spec' => true,
            ],
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('2', $resolved['usb_port_count']->value);
        $this->assertSame('calculated_components', $resolved['usb_port_count']->source);
        $this->assertSame('Custom USB-C Daughterboard', $resolved['usb_port_count']->calculatedExtraContributorSummary());
    }

    public function test_parent_component_attribute_contributes_when_no_child_override_is_materialized(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $boardDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $boardDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);
        $asset = Asset::factory()->create();

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $boardDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('4', $resolved['usb_port_count']->value);
        $this->assertFalse($resolved['usb_port_count']->hasHierarchyOverlapWarnings());
    }

    public function test_expected_subcomponent_template_contributes_until_materialized_child_replaces_it(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $boardDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $portDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $boardDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $portDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '2',
            'raw_value' => '2',
            'resolves_to_spec' => true,
        ]);
        $subcomponentTemplate = ComponentDefinitionSubcomponentTemplate::create([
            'parent_component_definition_id' => $boardDefinition->id,
            'child_component_definition_id' => $portDefinition->id,
            'expected_name' => 'Expected USB-C Port Board',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        $modelTemplate = ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $boardDefinition->id,
            'expected_name' => 'Main Board Assembly',
            'expected_qty' => 1,
            'is_required' => true,
            'sort_order' => 0,
        ]);
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $modelTemplate->id,
            'removed_qty' => 1,
        ]);
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $boardDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);

        $before = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('2', $before['usb_port_count']->value);
        $this->assertTrue($before['usb_port_count']->hasHierarchyOverlapWarnings());
        $this->assertSame('Expected USB-C Port Board', $before['usb_port_count']->calculatedExpectedContributorSummary());

        app(ComponentExpectedSubcomponentService::class)->materializeAttachedChild(
            $parent,
            $subcomponentTemplate,
            User::factory()->superuser()->create(),
            [
                'condition_warning_confirmed' => true,
                'note' => 'Track visible child.',
            ]
        );

        $after = app(EffectiveAttributeResolver::class)->resolveForAsset($asset->fresh())->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('2', $after['usb_port_count']->value);
        $this->assertTrue($after['usb_port_count']->hasHierarchyOverlapWarnings());
    }

    public function test_attached_child_component_attribute_suppresses_parent_attribute_at_the_same_definition(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $boardDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $portDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $boardDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $portDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '1',
            'raw_value' => '1',
            'resolves_to_spec' => true,
        ]);
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $boardDefinition->id,
            'display_name' => 'Main Board Assembly',
        ]);

        ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => $portDefinition->id,
            'display_name' => 'USB-C Port Board',
        ]);
        ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => $portDefinition->id,
            'display_name' => 'USB-C Port Board',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertSame('2', $resolved['usb_port_count']->value);
        $this->assertSame('USB-C Port Board x2', $resolved['usb_port_count']->calculatedExtraContributorSummary());
        $this->assertTrue($resolved['usb_port_count']->hasHierarchyOverlapWarnings());
        $this->assertStringContainsString('Main Board Assembly', $resolved['usb_port_count']->hierarchyOverlapSummary());
        $this->assertStringContainsString('USB-C Port Board', $resolved['usb_port_count']->hierarchyOverlapSummary());
    }

    public function test_damaged_attached_child_component_still_contributes_and_reports_issue_warning(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main Board Assembly',
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => null,
            'display_name' => 'Cracked USB-C Port',
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($child, [
            [
                'attribute_definition_id' => $usbPorts->id,
                'value' => '1',
                'resolves_to_spec' => true,
            ],
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);
        $warningLines = app(AttachedComponentIssueService::class)->warningLinesForAsset($asset);

        $this->assertSame('1', $resolved['usb_port_count']->value);
        $this->assertContains('Cracked USB-C Port - Damaged', $warningLines);
    }

    public function test_detached_child_component_no_longer_contributes_to_asset_resolution(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main Board Assembly',
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => null,
            'display_name' => 'Detached USB-C Port',
        ]);

        app(ComponentInstanceAttributeManager::class)->sync($child, [
            [
                'attribute_definition_id' => $usbPorts->id,
                'value' => '1',
                'resolves_to_spec' => true,
            ],
        ]);

        app(ComponentLifecycleService::class)->moveToStock($child, ComponentStorageLocation::factory()->stock()->create());

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);

        $this->assertFalse($resolved->has('usb_port_count'));
    }

    public function test_definition_backed_child_component_contribution_is_counted_as_extra(): void
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $childDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '2',
            'raw_value' => '2',
            'resolves_to_spec' => true,
        ]);
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main Board Assembly',
        ]);

        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'component_definition_id' => $childDefinition->id,
            'display_name' => 'Tracked USB-C Daughterboard',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);
        $roster = app(AssetComponentRosterService::class)->buildForAsset($asset);

        $this->assertSame('2', $resolved['usb_port_count']->value);
        $this->assertSame('Tracked USB-C Daughterboard', $resolved['usb_port_count']->calculatedExtraContributorSummary());
        $this->assertTrue($roster->rows->contains(fn ($row) => $row->component?->is($child) && $row->classification === 'extra'));
    }

    public function test_matching_tracked_component_stays_extra_until_expected_baseline_is_reduced(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => 'RAM STICK 4000',
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '16',
            'raw_value' => '16',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        $template = ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'RAM STICK 4000',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'RAM STICK 4000',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);
        $roster = app(AssetComponentRosterService::class)->buildForAsset($asset);

        $this->assertSame('48', $resolved['ram_capacity_gb']->value);
        $this->assertSame('32', $resolved['ram_capacity_gb']->formattedCalculatedExpectedSubtotal());
        $this->assertSame('16', $resolved['ram_capacity_gb']->formattedCalculatedExtraSubtotal());
        $this->assertSame('RAM STICK 4000 x2', $resolved['ram_capacity_gb']->calculatedExpectedContributorSummary());
        $this->assertSame('RAM STICK 4000', $resolved['ram_capacity_gb']->calculatedExtraContributorSummary());
        $expectedRows = $roster->rows->filter(fn ($row) => $row->classification === 'expected')->values();

        $this->assertCount(1, $expectedRows);
        $this->assertSame(2, $expectedRows->first()->quantity);
        $this->assertCount(1, $roster->rows->filter(fn ($row) => $row->classification === 'extra'));
        $this->assertCount(0, $roster->rows->filter(fn ($row) => $row->classification === 'expected_tracked'));
    }

    public function test_matching_tracked_component_fills_depleted_expected_baseline(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $componentDefinition = ComponentDefinition::factory()->create([
            'name' => 'RAM STICK 4000',
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '16',
            'raw_value' => '16',
            'sort_order' => 0,
            'resolves_to_spec' => true,
        ]);

        $template = ModelNumberComponentTemplate::create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $componentDefinition->id,
            'expected_name' => 'RAM STICK 4000',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);

        AssetExpectedComponentState::create([
            'asset_id' => $asset->id,
            'model_number_component_template_id' => $template->id,
            'removed_qty' => 1,
        ]);

        ComponentInstance::factory()->installed($asset->id)->create([
            'component_definition_id' => $componentDefinition->id,
            'display_name' => 'RAM STICK 4000',
        ]);

        $resolved = app(EffectiveAttributeResolver::class)->resolveForAsset($asset)->keyBy(fn ($attribute) => $attribute->definition->key);
        $roster = app(AssetComponentRosterService::class)->buildForAsset($asset);

        $this->assertSame('32', $resolved['ram_capacity_gb']->value);
        $this->assertSame('32', $resolved['ram_capacity_gb']->formattedCalculatedExpectedSubtotal());
        $this->assertNull($resolved['ram_capacity_gb']->formattedCalculatedExtraSubtotal());
        $this->assertCount(1, $roster->rows->filter(fn ($row) => $row->classification === 'expected'));
        $this->assertCount(1, $roster->rows->filter(fn ($row) => $row->classification === 'expected_tracked'));
        $this->assertCount(0, $roster->rows->filter(fn ($row) => $row->classification === 'extra'));
    }
}
