<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
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
