<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
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
                'is_active' => '1',
                'attribute_contributions' => [
                    [
                        'attribute_definition_id' => $capacity->id,
                        'value' => '8',
                    ],
                    [
                        'attribute_definition_id' => $memoryType->id,
                        'value' => 'DDR4',
                    ],
                ],
            ]);

        $definition = ComponentDefinition::query()->where('name', '8GB DDR4 SODIMM')->firstOrFail();

        $response->assertRedirect(route('settings.component_definitions.edit', $definition))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $capacity->id,
            'value' => '8',
            'raw_value' => '8',
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $definition->id,
            'attribute_definition_id' => $memoryType->id,
            'attribute_option_id' => $memoryTypeOption->id,
            'value' => 'DDR4',
            'raw_value' => 'DDR4',
        ]);
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
