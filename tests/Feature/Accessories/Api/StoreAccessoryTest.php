<?php

namespace Tests\Feature\Accessories\Api;

use App\Models\Accessory;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;
use Tests\Concerns\TestsFullMultipleCompaniesSupport;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\TestCase;

class StoreAccessoryTest extends TestCase implements TestsFullMultipleCompaniesSupport, TestsPermissionsRequirement
{
    public function testRequiresPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.accessories.store'))
            ->assertForbidden();
    }

    public function testAdheresToFullMultipleCompaniesSupportScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $userInCompanyA = User::factory()->for($companyA)->createAccessories()->create();

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($userInCompanyA)
            ->postJson(route('api.accessories.store'), [
                'category_id' => Category::factory()->forAccessories()->create()->id,
                'name' => 'My Awesome Accessory',
                'qty' => 1,
                'company_id' => $companyB->id,
            ])->assertStatusMessageIs('success');

        $accessory = Accessory::withoutGlobalScopes()
            ->where('name', 'My Awesome Accessory')
            ->sole();

        $this->assertSame($companyA->id, $accessory->company_id);
        $this->assertSame($userInCompanyA->id, $accessory->created_by);
    }

    public function testFullCompanySupportClearsCompanyForCompanylessUser()
    {
        $company = Company::factory()->create();
        $user = User::factory()->createAccessories()->create(['company_id' => null]);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($user)
            ->postJson(route('api.accessories.store'), [
                'category_id' => Category::factory()->forAccessories()->create()->id,
                'name' => 'Companyless Accessory',
                'qty' => 1,
                'company_id' => $company->id,
            ])->assertStatusMessageIs('success');

        $accessory = Accessory::withoutGlobalScopes()
            ->where('name', 'Companyless Accessory')
            ->sole();

        $this->assertNull($accessory->company_id);
        $this->assertSame($user->id, $accessory->created_by);
    }

    public function testFullCompanySupportAllowsSuperuserToSelectCompany()
    {
        $company = Company::factory()->create();
        $superuser = User::factory()->superuser()->create(['company_id' => null]);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superuser)
            ->postJson(route('api.accessories.store'), [
                'category_id' => Category::factory()->forAccessories()->create()->id,
                'name' => 'Superuser Accessory',
                'qty' => 1,
                'company_id' => (string) $company->id,
            ])->assertStatusMessageIs('success');

        $accessory = Accessory::withoutGlobalScopes()
            ->where('name', 'Superuser Accessory')
            ->sole();

        $this->assertSame($company->id, $accessory->company_id);
        $this->assertSame($superuser->id, $accessory->created_by);
    }

    public function testValidation()
    {
        $this->actingAsForApi(User::factory()->createAccessories()->create())
            ->postJson(route('api.accessories.store'), [
                //
            ])
            ->assertStatusMessageIs('error')
            ->assertMessagesContains([
                'category_id',
                'name',
                'qty',
            ]);
    }

    public function testCanStoreAccessory()
    {
        $category = Category::factory()->forAccessories()->create();
        $company = Company::factory()->create();
        $location = Location::factory()->create();
        $manufacturer = Manufacturer::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAsForApi(User::factory()->createAccessories()->create())
            ->postJson(route('api.accessories.store'), [
                'name' => 'My Awesome Accessory',
                'qty' => 2,
                'order_number' => '12345',
                'purchase_cost' => 100.00,
                'purchase_date' => '2024-09-18',
                'model_number' => '98765',
                'category_id' => $category->id,
                'company_id' => $company->id,
                'location_id' => $location->id,
                'manufacturer_id' => $manufacturer->id,
                'supplier_id' => $supplier->id,
            ])->assertStatusMessageIs('success');

        $this->assertDatabaseHas('accessories', [
            'name' => 'My Awesome Accessory',
            'qty' => 2,
            'order_number' => '12345',
            'purchase_cost' => 100.00,
            'purchase_date' => '2024-09-18',
            'model_number' => '98765',
            'category_id' => $category->id,
            'company_id' => $company->id,
            'location_id' => $location->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
        ]);
    }
}
