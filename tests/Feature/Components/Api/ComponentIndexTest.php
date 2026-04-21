<?php

namespace Tests\Feature\Components\Api;

use App\Models\Company;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class ComponentIndexTest extends TestCase
{
    public function testComponentIndexAdheresToCompanyScoping(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $componentA = ComponentInstance::factory()->create(['company_id' => $companyA->id]);
        $componentB = ComponentInstance::factory()->create(['company_id' => $companyB->id]);

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->viewComponents()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->viewComponents()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA, 'component_tag')
            ->assertResponseContainsInRows($componentB, 'component_tag');

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA, 'component_tag')
            ->assertResponseContainsInRows($componentB, 'component_tag');

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA, 'component_tag')
            ->assertResponseContainsInRows($componentB, 'component_tag');

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA, 'component_tag')
            ->assertResponseContainsInRows($componentB, 'component_tag');

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA, 'component_tag')
            ->assertResponseDoesNotContainInRows($componentB, 'component_tag');

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.components.index'))
            ->assertResponseDoesNotContainInRows($componentA, 'component_tag')
            ->assertResponseContainsInRows($componentB, 'component_tag');
    }

    public function testStoreRejectsComponentTagThatOverlapsAnAssetTag(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = \App\Models\Asset::factory()->create(['asset_tag' => 'INBIT-AA0001']);

        $this->actingAsForApi($user)
            ->postJson(route('api.components.store'), [
                'component_tag' => $asset->asset_tag,
                'display_name' => 'Replacement SSD',
                'status' => ComponentInstance::STATUS_IN_STOCK,
            ])
            ->assertStatus(422)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('component_tag');
    }

    public function testIndexCanFilterByStorageLocationHierarchySupplierAndManufacturer(): void
    {
        $user = User::factory()->viewComponents()->create();
        $site = Location::factory()->create();
        $bin = Location::factory()->create(['parent_id' => $site->id]);
        $supplier = Supplier::factory()->create();
        $manufacturer = Manufacturer::factory()->create();
        $storageLocation = ComponentStorageLocation::factory()->stock()->create(['site_location_id' => $bin->id]);
        $matching = ComponentInstance::factory()->create([
            'supplier_id' => $supplier->id,
            'storage_location_id' => $storageLocation->id,
            'component_definition_id' => \App\Models\ComponentDefinition::factory()->create([
                'manufacturer_id' => $manufacturer->id,
            ])->id,
        ]);
        $other = ComponentInstance::factory()->create();

        $this->actingAsForApi($user)
            ->getJson(route('api.components.index', [
                'location_id' => $site->id,
                'supplier_id' => $supplier->id,
                'manufacturer_id' => $manufacturer->id,
            ]))
            ->assertResponseContainsInRows($matching, 'component_tag')
            ->assertResponseDoesNotContainInRows($other, 'component_tag');
    }
}
