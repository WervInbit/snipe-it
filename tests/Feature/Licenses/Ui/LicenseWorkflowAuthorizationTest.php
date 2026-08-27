<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\Company;
use App\Models\License;
use App\Models\User;
use Tests\TestCase;

class LicenseWorkflowAuthorizationTest extends TestCase
{
    public function testBulkCheckoutRequiresCheckoutPermission(): void
    {
        $license = License::factory()->create(['seats' => 1]);

        $this->actingAs(User::factory()->checkinLicenses()->create())
            ->post(route('licenses.bulkcheckout', $license))
            ->assertForbidden();

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->post(route('licenses.bulkcheckout', $license))
            ->assertStatus(302);
    }

    public function testBulkCheckoutOnlyAutoassignsUsersFromLicenseCompanyWhenFmcsIsEnabled(): void
    {
        $this->settings->enableMultipleFullCompanySupport();

        $licenseCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $license = License::factory()->for($licenseCompany)->create([
            'seats' => 2,
            'termination_date' => null,
            'expiration_date' => now()->addYear(),
        ]);
        $sameCompanyUser = User::factory()->for($licenseCompany)->create([
            'autoassign_licenses' => 1,
        ]);
        $otherCompanyUser = User::factory()->for($otherCompany)->create([
            'autoassign_licenses' => 1,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.bulkcheckout', $license))
            ->assertStatus(302);

        $this->assertDatabaseHas('license_seats', [
            'license_id' => $license->id,
            'assigned_to' => $sameCompanyUser->id,
        ]);
        $this->assertDatabaseMissing('license_seats', [
            'license_id' => $license->id,
            'assigned_to' => $otherCompanyUser->id,
        ]);
    }

    public function testBulkCheckoutRejectsInactiveLicense(): void
    {
        $license = License::factory()->create([
            'seats' => 1,
            'expiration_date' => now()->subDay(),
            'termination_date' => null,
        ]);
        User::factory()->create(['autoassign_licenses' => 1]);

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->post(route('licenses.bulkcheckout', $license))
            ->assertSessionHas('error', trans('admin/licenses/message.checkout.license_is_inactive'));

        $this->assertNull($license->licenseseats()->first()->assigned_to);
    }
}
