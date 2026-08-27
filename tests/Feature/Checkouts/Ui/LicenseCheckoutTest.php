<?php

namespace Tests\Feature\Checkouts\Ui;

use App\Models\Asset;
use App\Models\Company;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseCheckoutTest extends TestCase
{
    public function testPageRenders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.checkout', License::factory()->create()->id))
            ->assertOk();
    }

    public function testNotesAreStoredInActionLogOnCheckoutToAsset()
    {
        $admin = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $licenseSeat = LicenseSeat::factory()->create();

        $this->actingAs($admin)
            ->post(route('licenses.checkout', $licenseSeat->license), [
                'checkout_to_type' => 'asset',
                'assigned_to' => null,
                'asset_id' => $asset->id,
                'notes' => 'oh hi there',
            ]);

        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkout',
            'target_id' => $asset->id,
            'target_type' => Asset::class,
            'item_id' => $licenseSeat->license->id,
            'item_type' => License::class,
            'note' => 'oh hi there',
        ]);
        $this->assertHasTheseActionLogs($licenseSeat->license, ['add seats', 'create', 'checkout']); // TODO - TOTALLY out-of-order
    }

    public function testNotesAreStoredInActionLogOnCheckoutToUser()
    {
        $admin = User::factory()->superuser()->create();
        $licenseSeat = LicenseSeat::factory()->create();

        $this->actingAs($admin)
            ->post(route('licenses.checkout', $licenseSeat->license), [
                'checkout_to_type' => 'user',
                'assigned_to' => $admin->id,
                'asset_id' => null,
                'notes' => 'oh hi there',
            ]);

        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkout',
            'target_id' => $admin->id,
            'target_type' => User::class,
            'item_id' => $licenseSeat->license->id,
            'item_type' => License::class,
            'note' => 'oh hi there',
        ]);
        $this->assertHasTheseActionLogs($licenseSeat->license, ['add seats', 'create', 'checkout']); //FIXME - out-of-order
    }

    public function testLicenseCheckoutPagePostIsRedirectedIfRedirectSelectionIsIndex()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('licenses.checkout', $license))
            ->post(route('licenses.checkout', $license), [
                'assigned_to' =>  User::factory()->create()->id,
                'redirect_option' => 'index',
                'assigned_qty' => 1,
            ])
            ->assertStatus(302)
            ->assertRedirect(route('licenses.index'));
    }

    public function testLicenseCheckoutPagePostIsRedirectedIfRedirectSelectionIsItem()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('licenses.checkout', $license))
            ->post(route('licenses.checkout', $license), [
                'assigned_to' =>  User::factory()->create()->id,
                'redirect_option' => 'item',
            ])
            ->assertStatus(302)
            ->assertRedirect(route('licenses.show', $license));
    }

    public function testLicenseCheckoutPagePostIsRedirectedIfRedirectSelectionIsUserTarget()
    {
        $user = User::factory()->create();
        $license = License::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('licenses.checkout', $license))
            ->post(route('licenses.checkout' , $license), [
                'assigned_to' =>  $user->id,
                'redirect_option' => 'target',
            ])
            ->assertStatus(302)
            ->assertRedirect(route('users.show', $user));
    }
    public function testLicenseCheckoutPagePostIsRedirectedIfRedirectSelectionIsAssetTarget()
    {
        $asset = Asset::factory()->create();
        $license = License::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('licenses.checkout', $license))
            ->post(route('licenses.checkout' , $license), [
                'asset_id' =>  $asset->id,
                'redirect_option' => 'target',
            ])
            ->assertStatus(302)
            ->assertRedirect(route('hardware.show', $asset));
    }

    public function testExpiredLicenseCannotOpenCheckoutPage(): void
    {
        $license = License::factory()->create([
            'expiration_date' => now()->subDay(),
            'termination_date' => null,
        ]);

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->get(route('licenses.checkout', $license))
            ->assertRedirect(route('licenses.index'))
            ->assertSessionHas('error', trans('admin/licenses/message.checkout.license_is_inactive'));
    }

    public function testTerminatedLicenseCannotBeCheckedOut(): void
    {
        $license = License::factory()->create([
            'seats' => 1,
            'expiration_date' => now()->addYear(),
            'termination_date' => now(),
        ]);

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->post(route('licenses.checkout', $license), [
                'assigned_to' => User::factory()->create()->id,
            ])
            ->assertRedirect(route('licenses.index'))
            ->assertSessionHas('error', trans('admin/licenses/message.checkout.license_is_inactive'));

        $this->assertNull($license->licenseseats()->first()->assigned_to);
    }

    public function testSequentialCheckoutsUseDistinctSeats(): void
    {
        $license = License::factory()->create(['seats' => 2]);
        $actor = User::factory()->checkoutLicenses()->create();
        $targets = User::factory()->count(2)->create();

        foreach ($targets as $target) {
            $this->actingAs($actor)
                ->post(route('licenses.checkout', $license), ['assigned_to' => $target->id])
                ->assertSessionHas('success');
        }

        $assignedTo = $license->licenseseats()->orderBy('id')->pluck('assigned_to');
        $this->assertSame($targets->pluck('id')->sort()->values()->all(), $assignedTo->sort()->values()->all());
    }

    public function testOccupiedSpecificSeatCannotBeOverwrittenWhileAnotherSeatIsFree(): void
    {
        $license = License::factory()->create(['seats' => 2]);
        $seats = $license->licenseseats()->orderBy('id')->get();
        $currentHolder = User::factory()->create();
        $seats->first()->update(['assigned_to' => $currentHolder->id]);

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->post(route('licenses.checkout', [
                'license' => $license,
                'seatId' => $seats->first()->id,
            ]), [
                'assigned_to' => User::factory()->create()->id,
            ])
            ->assertRedirect(route('licenses.index'))
            ->assertSessionHas('error', trans('admin/licenses/message.checkout.unavailable'));

        $this->assertSame($currentHolder->id, $seats->first()->fresh()->assigned_to);
        $this->assertNull($seats->last()->fresh()->assigned_to);
    }

    public function testLicenseCannotBeCheckedOutAcrossCompanyBoundary(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $licenseCompany = Company::factory()->create();
        $targetCompany = Company::factory()->create();
        $license = License::factory()->for($licenseCompany)->create(['seats' => 1]);
        $target = User::factory()->for($targetCompany)->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.checkout', $license), [
                'assigned_to' => $target->id,
            ])
            ->assertRedirect(route('licenses.index'))
            ->assertSessionHas('error', trans('general.error_user_company'));

        $this->assertNull($license->licenseseats()->first()->assigned_to);
    }
}
