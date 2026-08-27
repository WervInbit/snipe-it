<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentDefinitionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testAuthorizedUserCanViewDefinitionsPage(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();

        $this->actingAs($user)
            ->get(route('settings.component_definitions.index'))
            ->assertOk()
            ->assertSeeText('Component Definitions')
            ->assertDontSeeText('Tracking');
    }

    public function testDefinitionsPageExposesLiveSearchHooks(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $category = Category::factory()->forComponents()->create(['name' => 'Ports']);
        ComponentDefinition::factory()->create([
            'name' => 'USB-C Port',
            'part_code' => 'USB-C-01',
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.index'))
            ->assertOk()
            ->assertSee('id="component-definition-search-form"', false)
            ->assertSee('data-component-definition-live-search', false)
            ->assertSee('data-component-definition-search-loading', false)
            ->assertSee('data-component-definition-row', false)
            ->assertSee('data-search-text="usb-c port usb-c-01', false)
            ->assertSee('data-component-definition-loading', false)
            ->assertSee('data-component-definition-no-matches', false);
    }

    public function testDefinitionsSearchStillFiltersServerResults(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        ComponentDefinition::factory()->create(['name' => 'USB-C Port']);
        ComponentDefinition::factory()->create(['name' => 'Battery 45 Wh']);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.index', ['search' => 'USB-C']))
            ->assertOk()
            ->assertSeeText('USB-C Port')
            ->assertDontSeeText('Battery 45 Wh');
    }

    public function testAuthorizedUserCanCreateDefinition(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $category = Category::factory()->forComponents()->create();
        $manufacturer = Manufacturer::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.component_definitions.store'), [
                'name' => '16GB DDR4 SODIMM',
                'category_id' => $category->id,
                'manufacturer_id' => $manufacturer->id,
                'part_code' => 'RAM-16GB',
                'serial_tracking_mode' => 'optional',
                'is_active' => '1',
            ]);

        $definition = ComponentDefinition::query()->where('name', '16GB DDR4 SODIMM')->first();

        $response->assertRedirect(route('settings.component_definitions.edit', $definition))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_definitions', [
            'name' => '16GB DDR4 SODIMM',
            'part_code' => 'RAM-16GB',
        ]);
    }

    public function testDefinitionCanPersistSharedAttributeContributions(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $category = Category::factory()->forComponents()->create();
        $manufacturer = Manufacturer::factory()->create();
        $capacity = AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $memoryType = AttributeDefinition::create([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_asset_override' => true,
        ]);
        $memoryTypeOption = $memoryType->options()->create([
            'value' => 'DDR4',
            'label' => 'DDR4',
            'active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('settings.component_definitions.store'), [
                'name' => '8GB DDR4 SODIMM',
                'category_id' => $category->id,
                'manufacturer_id' => $manufacturer->id,
                'spec_display_label' => '8GB DDR4',
                'is_active' => '1',
                'attribute_contributions' => [
                    [
                        'attribute_definition_id' => $capacity->id,
                        'value' => '8',
                        'resolves_to_spec' => '1',
                    ],
                    [
                        'attribute_definition_id' => $memoryType->id,
                        'value' => 'DDR4',
                        'resolves_to_spec' => '1',
                        'include_in_component_label' => '1',
                    ],
                ],
            ]);

        $definition = ComponentDefinition::query()->where('name', '8GB DDR4 SODIMM')->firstOrFail();

        $response->assertRedirect(route('settings.component_definitions.edit', $definition))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_definitions', [
            'id' => $definition->id,
            'spec_display_label' => '8GB DDR4',
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $memoryType->id,
            'attribute_option_id' => $memoryTypeOption->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'resolves_to_spec' => true,
            'include_in_component_label' => true,
        ]);
    }

    public function testDefinitionCanPersistExpectedSubcomponentTemplates(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('settings.component_definitions.store'), [
                'name' => 'Main Board Assembly',
                'is_active' => '1',
                'expected_subcomponents' => [
                    [
                        'child_component_definition_id' => $childDefinition->id,
                        'expected_name' => '',
                        'expected_qty' => 2,
                        'is_required' => '1',
                        'notes' => 'One on each side.',
                    ],
                    [
                        'child_component_definition_id' => '',
                        'expected_name' => 'Thermal Pad',
                        'expected_qty' => 1,
                        'is_required' => '0',
                        'notes' => 'Freeform expected child.',
                    ],
                ],
            ]);

        $definition = ComponentDefinition::query()->where('name', 'Main Board Assembly')->firstOrFail();

        $response->assertRedirect(route('settings.component_definitions.edit', $definition))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $definition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 2,
            'is_required' => 1,
            'sort_order' => 0,
            'notes' => 'One on each side.',
        ]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $definition->id,
            'child_component_definition_id' => null,
            'expected_name' => 'Thermal Pad',
            'expected_qty' => 1,
            'is_required' => 0,
            'sort_order' => 1,
            'notes' => 'Freeform expected child.',
        ]);
    }

    public function testDefinitionCanReorderAndDeleteExpectedSubcomponentTemplatesWithoutDeletingInstances(): void
    {
        $user = User::factory()
            ->manageComponentDefinitions()
            ->manageComponentDefinitionLifecycle()
            ->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $firstChildDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        $secondChildDefinition = ComponentDefinition::factory()->create([
            'name' => 'Speaker Connector',
        ]);
        $firstTemplate = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $firstChildDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'expected_qty' => 1,
            'sort_order' => 0,
        ]);
        $secondTemplate = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $secondChildDefinition->id,
            'expected_name' => 'Speaker Connector',
            'expected_qty' => 1,
            'sort_order' => 1,
        ]);
        $trackedInstance = ComponentInstance::factory()->create([
            'component_definition_id' => $firstChildDefinition->id,
            'display_name' => 'Tracked USB-C Port Board',
        ]);

        $this->actingAs($user)
            ->put(route('settings.component_definitions.update', $parentDefinition), [
                'name' => 'Main Board Assembly',
                'is_active' => '1',
                'expected_subcomponents' => [
                    [
                        'id' => $secondTemplate->id,
                        'child_component_definition_id' => $secondChildDefinition->id,
                        'expected_name' => 'Speaker Connector',
                        'expected_qty' => 2,
                        'is_required' => '1',
                    ],
                    [
                        'id' => $firstTemplate->id,
                        'child_component_definition_id' => $firstChildDefinition->id,
                        'expected_name' => 'USB-C Port Board',
                        'expected_qty' => 1,
                        'is_required' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.component_definitions.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'id' => $secondTemplate->id,
            'expected_qty' => 2,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'id' => $firstTemplate->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put(route('settings.component_definitions.update', $parentDefinition), [
                'name' => 'Main Board Assembly',
                'is_active' => '1',
                'expected_subcomponents' => [
                    [
                        'id' => $secondTemplate->id,
                        'child_component_definition_id' => $secondChildDefinition->id,
                        'expected_name' => 'Speaker Connector',
                        'expected_qty' => 2,
                        'is_required' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.component_definitions.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('component_definition_subcomponent_templates', [
            'id' => $firstTemplate->id,
        ]);
        $this->assertDatabaseHas('component_instances', [
            'id' => $trackedInstance->id,
            'component_definition_id' => $firstChildDefinition->id,
        ]);
    }

    public function testDefinitionEditFormRendersExpectedSubcomponentEditor(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Main Board Assembly',
        ]);
        $category = Category::factory()->forComponents()->create(['name' => 'Ports']);
        $manufacturer = Manufacturer::factory()->create(['name' => 'Inbit Parts']);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
        ]);
        ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'USB-C Port Board',
            'notes' => 'One on each side.',
        ]);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.edit', $parentDefinition))
            ->assertOk()
            ->assertSeeText('Expected Subcomponents')
            ->assertSee('data-subcomponent-template-row', false)
            ->assertSee('data-subcomponent-definition-id', false)
            ->assertSee('data-subcomponent-definition-search', false)
            ->assertSee('data-subcomponent-search-results', false)
            ->assertSee('Search component definitions...')
            ->assertSee('Start typing a component definition name, part code, category, or manufacturer, then pick a match.')
            ->assertSee('USB-C Port Board (USB-C-PORT)')
            ->assertSee('data-subcomponent-notes-toggle', false)
            ->assertSee('data-subcomponent-notes-panel', false)
            ->assertSee('class="collapse component-definition-subcomponent-notes in"', false)
            ->assertSeeText('Notes added')
            ->assertSeeText('One on each side.')
            ->assertSeeText('Add Expected Subcomponent');
    }

    public function testDefinitionEditFormShowsHierarchyOverlapWarning(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count',
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);
        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Motherboard Assembly',
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $parentDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionAttribute::create([
            'component_definition_id' => $childDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '1',
            'raw_value' => '1',
            'resolves_to_spec' => true,
        ]);
        ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.edit', $parentDefinition))
            ->assertOk()
            ->assertSee('data-testid="component-definition-hierarchy-overlap-warning"', false)
            ->assertSeeText('Hierarchy overlap warning')
            ->assertSeeText('Motherboard Assembly')
            ->assertSeeText('Left USB-C Port Board')
            ->assertSeeText('USB Port Count')
            ->assertSeeText('Parent: 4')
            ->assertSeeText('Child: 1');
    }

    public function testDefinitionCreateFormUsesQuicksearchContributionPicker(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        AttributeDefinition::create([
            'key' => 'ram_capacity_gb',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'allow_asset_override' => true,
        ]);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.create'))
            ->assertOk()
            ->assertSee('data-contribution-attribute-search', false)
            ->assertSee('data-contribution-search-results', false)
            ->assertSee('Search attributes...')
            ->assertSee('Start typing an attribute label or key, then pick a match.');
    }

    public function testContributionRowRequiresValidAttributeSelection(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();

        $response = $this->actingAs($user)
            ->from(route('settings.component_definitions.create'))
            ->post(route('settings.component_definitions.store'), [
                'name' => '8GB DDR4 SODIMM',
                'is_active' => '1',
                'attribute_contributions' => [
                    [
                        'attribute_search' => 'RAM Capacity',
                        'attribute_definition_id' => '',
                        'value' => '8',
                    ],
                ],
            ]);

        $response->assertRedirect(route('settings.component_definitions.create'))
            ->assertSessionHasErrors(['attribute_contributions.0.attribute_definition_id']);
    }

    public function testContributionValueErrorsStayBoundToTheEditedRow(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $memoryType = AttributeDefinition::create([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ]);
        $memoryType->options()->create([
            'value' => 'DDR4',
            'label' => 'DDR4',
            'active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->from(route('settings.component_definitions.create'))
            ->post(route('settings.component_definitions.store'), [
                'name' => '8GB Memory Module',
                'is_active' => '1',
                'attribute_contributions' => [
                    [
                        'attribute_search' => 'Memory Type (memory_type)',
                        'attribute_definition_id' => $memoryType->id,
                        'value' => 'LPDDR5',
                    ],
                ],
            ]);

        $response->assertRedirect(route('settings.component_definitions.create'))
            ->assertSessionHasErrors(['attribute_contributions.0.value']);
    }

    public function testEditFormRendersFixedEnumContributionAsSelect(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();
        $memoryType = AttributeDefinition::create([
            'key' => 'memory_type',
            'label' => 'Memory Type',
            'datatype' => AttributeDefinition::DATATYPE_ENUM,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ]);
        $memoryType->options()->createMany([
            [
                'value' => 'DDR4',
                'label' => 'DDR4',
                'active' => true,
                'sort_order' => 0,
            ],
            [
                'value' => 'DDR5',
                'label' => 'DDR5',
                'active' => true,
                'sort_order' => 1,
            ],
        ]);

        $definition = ComponentDefinition::factory()->create([
            'name' => 'Memory Module',
        ]);
        $definition->attributeContributions()->create([
            'attribute_definition_id' => $memoryType->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
            'attribute_option_id' => $memoryType->options()->first()->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('settings.component_definitions.edit', $definition))
            ->assertOk()
            ->assertSee('name="attribute_contributions[0][value]"', false)
            ->assertSee('<select name="attribute_contributions[0][value]"', false)
            ->assertDontSee('list="attribute_contributions_0_value_options"', false)
            ->assertSeeText('Use one of the defined options.');
    }

    public function testUnauthorizedUserIsBlockedFromDefinitionsSettings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.component_definitions.index'))
            ->assertForbidden();
    }

    public function testDefinitionFormDoesNotExposeCompanyScoping(): void
    {
        $user = User::factory()->manageComponentDefinitions()->create();

        $this->actingAs($user)
            ->get(route('settings.component_definitions.create'))
            ->assertOk()
            ->assertDontSee('name="company_id"', false)
            ->assertDontSee('id="company_id"', false)
            ->assertDontSee('name="serial_tracking_mode"', false)
            ->assertDontSee('id="serial_tracking_mode"', false)
            ->assertDontSeeText('Serial Tracking');
    }
}
