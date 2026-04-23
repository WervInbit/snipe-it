<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetExpectedComponentState;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentInstance;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumberComponentTemplate;
use App\Services\Components\AssetComponentRosterService;
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
        $this->assertCount(2, $roster->rows->filter(fn ($row) => $row->classification === 'expected'));
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
