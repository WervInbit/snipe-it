<?php
namespace Tests\Feature\Checkouts\Api;

use App\Models\Company;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseCheckOutTest extends TestCase {
    public function testLicenseCheckout()
    {
        $authUser = User::factory()->superuser()->create();
        $this->actingAsForApi($authUser);

        $license = License::factory()->create();
        $licenseSeat = LicenseSeat::factory()->for($license)->create([
            'assigned_to' => null,
        ]);

        $targetUser = User::factory()->create();

        $payload = [
            'assigned_to' => $targetUser->id,
            'notes' => 'Checking out the seat to a user',
        ];

        $response = $this->patchJson(
            route('api.licenses.seats.update', [$license->id, $licenseSeat->id]),
            $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'success',
            ]);

        $licenseSeat->refresh();

        $this->assertEquals($targetUser->id, $licenseSeat->assigned_to);
        $this->assertEquals('Checking out the seat to a user', $licenseSeat->notes);
        $this->assertHasTheseActionLogs($license, ['add seats', 'create', 'checkout']); //FIXME - backwards
    }

    public function testInactiveLicenseCannotBeCheckedOut(): void
    {
        $license = License::factory()->create([
            'seats' => 1,
            'expiration_date' => now()->subDay(),
            'termination_date' => null,
        ]);
        $seat = $license->licenseseats()->first();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson(route('api.licenses.seats.update', [$license->id, $seat->id]), [
                'assigned_to' => User::factory()->create()->id,
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath('messages', trans('admin/licenses/message.checkout.license_is_inactive'));

        $this->assertNull($seat->fresh()->assigned_to);
    }

    public function testOccupiedSeatCannotBeReassignedThroughApi(): void
    {
        $license = License::factory()->create(['seats' => 2]);
        $seat = $license->licenseseats()->first();
        $currentHolder = User::factory()->create();
        $seat->update(['assigned_to' => $currentHolder->id]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson(route('api.licenses.seats.update', [$license->id, $seat->id]), [
                'assigned_to' => User::factory()->create()->id,
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath('messages', trans('admin/licenses/message.checkout.unavailable'));

        $this->assertSame($currentHolder->id, $seat->fresh()->assigned_to);
    }

    public function testApiCheckinUsesCheckinPermission(): void
    {
        $license = License::factory()->create(['seats' => 1, 'reassignable' => true]);
        $seat = $license->licenseseats()->first();
        $seat->update(['assigned_to' => User::factory()->create()->id]);

        $this->actingAsForApi(User::factory()->checkinLicenses()->create())
            ->patchJson(route('api.licenses.seats.update', [$license->id, $seat->id]), [
                'assigned_to' => null,
                'asset_id' => null,
            ])
            ->assertStatusMessageIs('success');

        $this->assertNull($seat->fresh()->assigned_to);
    }

    public function testApiCannotCheckInANonReassignableLicense(): void
    {
        $license = License::factory()->create(['seats' => 1, 'reassignable' => false]);
        $seat = $license->licenseseats()->first();
        $holder = User::factory()->create();
        $seat->update(['assigned_to' => $holder->id]);

        $this->actingAsForApi(User::factory()->checkinLicenses()->create())
            ->patchJson(route('api.licenses.seats.update', [$license->id, $seat->id]), [
                'assigned_to' => null,
                'asset_id' => null,
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath(
                'messages',
                trans('admin/licenses/message.checkin.not_reassignable').'.'
            );

        $this->assertSame($holder->id, $seat->fresh()->assigned_to);
    }

    public function testLicenseCannotBeCheckedOutAcrossCompanyBoundary(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $licenseCompany = Company::factory()->create();
        $targetCompany = Company::factory()->create();
        $license = License::factory()->for($licenseCompany)->create(['seats' => 1]);
        $seat = $license->licenseseats()->first()
            ?? LicenseSeat::factory()->for($license)->create([
                'assigned_to' => null,
                'asset_id' => null,
            ]);
        $target = User::factory()->for($targetCompany)->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.licenses.seats.update', [$license->id, $seat->id]), [
                'assigned_to' => $target->id,
            ])
            ->assertStatusMessageIs('error')
            ->assertJsonPath('messages', trans('general.error_user_company'));

        $this->assertNull($seat->fresh()->assigned_to);
    }
}
