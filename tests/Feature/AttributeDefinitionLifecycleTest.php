<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentInstance;
use App\Models\ComponentInstanceAttribute;
use App\Models\ModelNumberAttribute;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeDefinitionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function makeSuperUser(): User
    {
        return User::factory()->superuser()->create();
    }

    private function makeAttribute(array $overrides = []): AttributeDefinition
    {
        return AttributeDefinition::create(array_merge([
            'key' => 'attr_'.uniqid(),
            'label' => 'Example Attribute',
            'datatype' => AttributeDefinition::DATATYPE_TEXT,
            'unit' => null,
            'required_for_category' => false,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ], $overrides));
    }

    public function test_create_auto_generates_key_from_label_when_manual_override_is_off(): void
    {
        $user = $this->makeSuperUser();

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Battery Health',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Battery Health',
            'key' => 'battery_health',
        ]);
    }

    public function test_create_auto_key_appends_numeric_suffix_on_collision(): void
    {
        $user = $this->makeSuperUser();
        $this->makeAttribute([
            'label' => 'Battery Health Existing',
            'key' => 'battery_health',
        ]);

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Battery Health',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Battery Health',
            'key' => 'battery_health_2',
        ]);
    }

    public function test_create_manual_override_sanitizes_key_and_applies_suffix_on_collision(): void
    {
        $user = $this->makeSuperUser();
        $this->makeAttribute([
            'label' => 'Other Attribute',
            'key' => 'custom_key',
        ]);

        $this->actingAs($user)
            ->post(route('attributes.store'), [
                'label' => 'Any Label',
                'key' => 'Custom Key !!!',
                'manual_key_override' => 1,
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'label' => 'Any Label',
            'key' => 'custom_key_2',
        ]);
    }

    public function test_attribute_ui_hides_version_workflow(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->get(route('attributes.index'))
            ->assertOk()
            ->assertDontSeeText('New Version')
            ->assertDontSeeText('Version');

        $this->actingAs($user)
            ->get(route('attributes.edit', $attribute))
            ->assertOk()
            ->assertDontSeeText('New Version')
            ->assertDontSeeText('Create Attribute Version');
    }

    public function test_enum_attribute_edit_supports_pending_options_when_attribute_is_in_use(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'key' => 'port_connector_type',
            'label' => 'Port Connector Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
        ]);
        $option = $attribute->options()->create([
            'value' => 'usb_c',
            'label' => 'USB-C',
            'active' => true,
            'sort_order' => 0,
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'usb_c',
            'raw_value' => 'usb_c',
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession([
                '_old_input' => [
                    'options' => [
                        'new' => [
                            2 => [
                                'value' => 'eSATA',
                                'label' => 'eSATA',
                                'sort_order' => 2,
                                'active' => 1,
                            ],
                        ],
                    ],
                ],
            ])
            ->get(route('attributes.edit', $attribute))
            ->assertOk()
            ->assertSee('data-option-rows', false)
            ->assertSee('data-next-index="3"', false)
            ->assertSee('data-option-usage-warning', false)
            ->assertSee('name="options[new][2][value]"', false)
            ->assertSee('value="eSATA"', false)
            ->assertSeeText('Component definitions: 1')
            ->assertSeeText('Adding a new option only makes it available for future selections.')
            ->assertSeeText('Renaming or removing an existing option can update current records already using that value.');
    }

    public function test_update_can_add_new_option_to_existing_enum_attribute(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'key' => 'port_connector_type',
            'label' => 'Port Connector Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
        ]);

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Port Connector Type',
                'key' => 'port_connector_type',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'allow_asset_override' => 1,
                'options' => [
                    'new' => [
                        [
                            'value' => 'eSATA',
                            'label' => 'eSATA',
                            'sort_order' => 0,
                            'active' => 1,
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('attributes.edit', $attribute));

        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $attribute->id,
            'value' => 'eSATA',
            'label' => 'eSATA',
            'sort_order' => 0,
            'active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_attribute_index_exposes_live_search_hooks(): void
    {
        $user = $this->makeSuperUser();
        $category = Category::factory()->forComponents()->create(['name' => 'Memory']);
        $attribute = $this->makeAttribute([
            'key' => 'ram_speed_mhz',
            'label' => 'RAM Speed',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'unit' => 'MHz',
            'required_for_category' => true,
            'allow_asset_override' => true,
        ]);
        $attribute->categories()->attach($category->id);

        $this->actingAs($user)
            ->get(route('attributes.index'))
            ->assertOk()
            ->assertSee('id="attributes-search-form"', false)
            ->assertSee('data-attribute-live-search', false)
            ->assertSee('data-attribute-search-loading', false)
            ->assertSee('data-attribute-row', false)
            ->assertSee('data-search-text="ram speed ram_speed_mhz int mhz active memory', false)
            ->assertSee('data-attribute-loading', false)
            ->assertSee('data-attribute-no-matches', false);
    }

    public function test_attribute_index_search_filters_server_results_by_category(): void
    {
        $user = $this->makeSuperUser();
        $memory = Category::factory()->forComponents()->create(['name' => 'Memory']);
        $display = Category::factory()->forComponents()->create(['name' => 'Display']);
        $speed = $this->makeAttribute([
            'key' => 'speed_value',
            'label' => 'Speed Value',
        ]);
        $resolution = $this->makeAttribute([
            'key' => 'resolution_value',
            'label' => 'Resolution Value',
        ]);
        $speed->categories()->attach($memory->id);
        $resolution->categories()->attach($display->id);

        $this->actingAs($user)
            ->get(route('attributes.index', ['search' => 'Memory']))
            ->assertOk()
            ->assertSeeText('Speed Value')
            ->assertDontSeeText('Resolution Value');
    }

    public function test_cannot_change_datatype_in_place(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Changed Label',
                'key' => $attribute->key,
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
            ])
            ->assertSessionHasErrors(['datatype']);
    }

    public function test_can_change_key_in_place(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'key' => 'ram_capacity',
        ]);

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Memory Capacity',
                'key' => 'memory_capacity',
                'datatype' => $attribute->datatype,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attribute->id,
            'label' => 'Memory Capacity',
            'key' => 'memory_capacity',
        ]);
    }

    public function test_updating_enum_option_value_propagates_current_rows_but_not_historical_results(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
        ]);
        $option = $attribute->options()->create([
            'value' => 'DDR4',
            'label' => 'DDR4',
            'active' => true,
            'sort_order' => 0,
        ]);

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'display_order' => 0,
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        AssetAttributeOverride::create([
            'asset_id' => $asset->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
        ]);

        $componentDefinition = ComponentDefinition::factory()->create();
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'sort_order' => 0,
        ]);
        $component = ComponentInstance::factory()->create();
        ComponentInstanceAttribute::create([
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'sort_order' => 0,
        ]);

        $historicalResult = TestResult::factory()->create([
            'attribute_definition_id' => $attribute->id,
            'expected_value' => 'DDR4',
            'expected_raw_value' => 'DDR4',
        ]);

        $this->actingAs($user)
            ->put(route('attributes.update', $attribute), [
                'label' => 'Memory Type',
                'key' => 'memory_type',
                'datatype' => $attribute->datatype,
                'allow_custom_values' => 0,
                'allow_asset_override' => 1,
                'options' => [
                    'existing' => [
                        $option->id => [
                            'value' => 'DDR5',
                            'label' => 'DDR5',
                            'sort_order' => 0,
                            'active' => 1,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR5',
            'raw_value' => 'DDR5',
        ]);
        $this->assertDatabaseHas('asset_attribute_overrides', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR5',
            'raw_value' => 'DDR5',
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $componentDefinition->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR5',
            'raw_value' => 'DDR5',
        ]);
        $this->assertDatabaseHas('component_instance_attributes', [
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $attribute->id,
            'attribute_option_id' => $option->id,
            'value' => 'DDR5',
            'raw_value' => 'DDR5',
        ]);

        $historicalResult->refresh();
        $this->assertSame('DDR4', $historicalResult->expected_value);
        $this->assertSame('DDR4', $historicalResult->expected_raw_value);
    }

    public function test_hide_and_unhide_attribute(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();

        $this->actingAs($user)
            ->patch(route('attributes.hide', $attribute))
            ->assertRedirect();

        $attribute->refresh();
        $this->assertNotNull($attribute->hidden_at);

        $this->actingAs($user)
            ->patch(route('attributes.unhide', $attribute))
            ->assertRedirect();

        $attribute->refresh();
        $this->assertNull($attribute->hidden_at);
    }

    public function test_cannot_unhide_deprecated_attribute(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $attribute->forceFill(['deprecated_at' => now(), 'hidden_at' => now()])->save();

        $this->actingAs($user)
            ->patch(route('attributes.unhide', $attribute))
            ->assertRedirect();

        $attribute->refresh();
        $this->assertNotNull($attribute->hidden_at);
    }

    public function test_cannot_delete_attribute_in_use(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $attribute->id,
            'display_order' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('attributes.destroy', $attribute))
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attribute->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_delete_attribute_used_by_component_instance(): void
    {
        $user = $this->makeSuperUser();
        $attribute = $this->makeAttribute();
        $component = ComponentInstance::factory()->create();

        ComponentInstanceAttribute::create([
            'component_instance_id' => $component->id,
            'attribute_definition_id' => $attribute->id,
            'value' => 'Observed value',
            'raw_value' => 'Observed value',
        ]);

        $this->actingAs($user)
            ->delete(route('attributes.destroy', $attribute))
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attribute->id,
            'deleted_at' => null,
        ]);
    }
}
