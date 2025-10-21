<?php

namespace Tests\Feature\Manufacturers\Api;

use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UpdateManufacturersTest extends TestCase
{
    public function testPermissionRequiredToStoreManufacturer(): void
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.manufacturers.store'), [
                'name' => 'Test Manufacturer',
            ])
            ->assertForbidden();
    }

    public function testPageRenders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('manufacturers.edit', Manufacturer::factory()->create()->id))
            ->assertOk();
    }

    public function testUserCanCreateManufacturersViaApi(): void
    {
        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.manufacturers.store'), [
                'name' => 'Test Manufacturer',
                'notes' => 'Test Note',
            ])
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->where('messages', trans('admin/manufacturers/message.create.success'))
                ->where('payload.name', 'Test Manufacturer')
                ->where('payload.notes', 'Test Note')
                ->etc()
            );

        $manufacturerId = $response->json('payload.id');
        $this->assertNotNull($manufacturerId);
        $this->assertDatabaseHas('manufacturers', [
            'id' => $manufacturerId,
            'name' => 'Test Manufacturer',
            'notes' => 'Test Note',
        ]);
    }

    public function testUserCanEditManufacturersViaApi(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => 'Test Manufacturer',
            'notes' => 'Original Note',
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.manufacturers.update', $manufacturer), [
                'name' => 'Test Manufacturer Edited',
                'notes' => 'Test Note Edited',
            ])
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->where('messages', trans('admin/manufacturers/message.update.success'))
                ->where('payload.name', 'Test Manufacturer Edited')
                ->where('payload.notes', 'Test Note Edited')
                ->etc()
            );

        $this->assertDatabaseHas('manufacturers', [
            'id' => $manufacturer->id,
            'name' => 'Test Manufacturer Edited',
            'notes' => 'Test Note Edited',
        ]);
    }
}
