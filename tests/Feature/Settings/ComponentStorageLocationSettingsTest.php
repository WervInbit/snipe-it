<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentStorageLocationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testAuthorizedUserCanViewStorageLocationsPage(): void
    {
        $user = User::factory()->manageComponentStorageLocations()->create();

        $this->actingAs($user)
            ->get(route('settings.component_storage_locations.index'))
            ->assertOk()
            ->assertSeeText('Component Storage Locations');
    }

    public function testAuthorizedUserCanCreateStorageLocation(): void
    {
        $user = User::factory()->manageComponentStorageLocations()->create();
        $siteLocation = Location::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.component_storage_locations.store'), [
                'name' => 'Bench Stock',
                'code' => 'bench-stock',
                'site_location_id' => $siteLocation->id,
                'type' => ComponentStorageLocation::TYPE_STOCK,
                'is_active' => '1',
            ]);

        $storageLocation = ComponentStorageLocation::query()->where('code', 'bench-stock')->first();

        $response->assertRedirect(route('settings.component_storage_locations.edit', $storageLocation))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('component_storage_locations', [
            'name' => 'Bench Stock',
            'code' => 'bench-stock',
            'type' => ComponentStorageLocation::TYPE_STOCK,
        ]);
    }

    public function testUnauthorizedUserIsBlockedFromStorageSettings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.component_storage_locations.index'))
            ->assertForbidden();
    }
}
