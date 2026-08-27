<?php

namespace Tests\Feature\Licenses\Api;

use App\Models\Company;
use App\Models\License;
use App\Models\User;
use Tests\TestCase;

class LicenseIndexTest extends TestCase
{
    public function testProductKeysCannotBeDiscoveredWithoutKeyPermission(): void
    {
        $license = License::factory()->create([
            'name' => 'Visible license metadata',
            'serial' => 'SECRET-PRODUCT-KEY-123',
        ]);
        $viewer = User::factory()->viewLicenses()->create();

        $this->actingAsForApi($viewer)
            ->getJson(route('api.licenses.index'))
            ->assertOk()
            ->assertJsonPath('rows.0.id', $license->id)
            ->assertJsonPath('rows.0.product_key', '------------');

        $this->actingAsForApi($viewer)
            ->getJson(route('api.licenses.index', ['search' => 'SECRET-PRODUCT-KEY-123']))
            ->assertOk()
            ->assertJsonPath('total', 0);

        $this->actingAsForApi($viewer)
            ->getJson(route('api.licenses.index', ['product_key' => 'SECRET-PRODUCT-KEY-123']))
            ->assertForbidden();

        $this->actingAsForApi($viewer)
            ->getJson(route('api.licenses.index', ['sort' => 'serial']))
            ->assertForbidden();
    }

    public function testKeyPermissionAllowsProductKeyOutputSearchAndSort(): void
    {
        $license = License::factory()->create([
            'name' => 'Licensed application',
            'serial' => 'AUTHORIZED-PRODUCT-KEY-456',
        ]);
        $keyViewer = User::factory()->viewLicenses()->viewKeysLicenses()->create();

        $this->actingAsForApi($keyViewer)
            ->getJson(route('api.licenses.index', [
                'search' => 'AUTHORIZED-PRODUCT-KEY-456',
                'sort' => 'serial',
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.id', $license->id)
            ->assertJsonPath('rows.0.product_key', 'AUTHORIZED-PRODUCT-KEY-456');

        $this->actingAsForApi($keyViewer)
            ->getJson(route('api.licenses.index', ['product_key' => 'AUTHORIZED-PRODUCT-KEY-456']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.id', $license->id);
    }

    public function testLicensesIndexAdheresToCompanyScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $licenseA = License::factory()->for($companyA)->create();
        $licenseB = License::factory()->for($companyB)->create();

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->viewLicenses()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->viewLicenses()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.licenses.index'))
            ->assertResponseContainsInRows($licenseA)
            ->assertResponseContainsInRows($licenseB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.licenses.index'))
            ->assertResponseContainsInRows($licenseA)
            ->assertResponseContainsInRows($licenseB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.licenses.index'))
            ->assertResponseContainsInRows($licenseA)
            ->assertResponseContainsInRows($licenseB);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.licenses.index'))
            ->assertResponseContainsInRows($licenseA)
            ->assertResponseContainsInRows($licenseB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.licenses.index'))
            ->assertResponseContainsInRows($licenseA)
            ->assertResponseDoesNotContainInRows($licenseB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.licenses.index'))
            ->assertResponseDoesNotContainInRows($licenseA)
            ->assertResponseContainsInRows($licenseB);
    }
}
