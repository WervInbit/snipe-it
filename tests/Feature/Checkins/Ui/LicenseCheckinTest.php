<?php

namespace Tests\Feature\Checkins\Ui;

use App\Events\CheckoutableCheckedIn;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LicenseCheckinTest extends TestCase
{
    public function testCheckingInLicenseRequiresCorrectPermission()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('licenses.checkin.save', [
                'licenseId' => LicenseSeat::factory()->assignedToUser()->create()->id,
            ]))
            ->assertForbidden();
    }

    public function testCannotCheckinNonReassignableLicense()
    {
        $licenseSeat = LicenseSeat::factory()
            ->notReassignable()
            ->assignedToUser()
            ->create();

        $this->actingAs(User::factory()->checkinLicenses()->create())
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'notes' => 'my note',
                'redirect_option' => 'index',
            ])
            ->assertSessionHas('error', trans('admin/licenses/message.checkin.not_reassignable') . '.');

        $this->assertNotNull($licenseSeat->fresh()->assigned_to);
    }

    public function testCannotCheckinLicenseThatIsNotAssigned()
    {
        $licenseSeat = LicenseSeat::factory()
            ->reassignable()
            ->create();

        $this->assertNull($licenseSeat->assigned_to);
        $this->assertNull($licenseSeat->asset_id);

        $this->actingAs(User::factory()->checkinLicenses()->create())
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'notes' => 'my note',
                'redirect_option' => 'index',
            ])
            ->assertSessionHas('error', trans('admin/licenses/message.checkin.error'));
    }

    public function testCanCheckInLicenseAssignedToAsset()
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $asset = Asset::factory()->create();

        $licenseSeat = LicenseSeat::factory()
            ->reassignable()
            ->assignedToAsset($asset)
            ->create();

        $actor = User::factory()->checkinLicenses()->create();

        $this->actingAs($actor)
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'notes' => 'my note',
                'redirect_option' => 'index',
            ])
            ->assertRedirect(route('licenses.index'));

        $this->assertNull($licenseSeat->fresh()->asset_id);
        $this->assertNull($licenseSeat->fresh()->assigned_to);
        $this->assertEquals('my note', $licenseSeat->fresh()->notes);

        Event::assertDispatchedTimes(CheckoutableCheckedIn::class, 1);
        Event::assertDispatched(CheckoutableCheckedIn::class, function (CheckoutableCheckedIn $event) use ($actor, $asset, $licenseSeat) {
            return $event->checkoutable->is($licenseSeat)
                && $event->checkedOutTo->is($asset)
                && $event->checkedInBy->is($actor)
                && $event->note === 'my note';
        });
    }

    public function testCanCheckInLicenseAssignedToUser()
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $user = User::factory()->create();

        $licenseSeat = LicenseSeat::factory()
            ->reassignable()
            ->assignedToUser($user)
            ->create();

        $actor = User::factory()->checkinLicenses()->create();

        $this->actingAs($actor)
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'notes' => 'my note',
                'redirect_option' => 'index',
            ])
            ->assertRedirect(route('licenses.index'));

        $this->assertNull($licenseSeat->fresh()->asset_id);
        $this->assertNull($licenseSeat->fresh()->assigned_to);
        $this->assertEquals('my note', $licenseSeat->fresh()->notes);

        Event::assertDispatchedTimes(CheckoutableCheckedIn::class, 1);
        Event::assertDispatched(CheckoutableCheckedIn::class, function (CheckoutableCheckedIn $event) use ($actor, $licenseSeat, $user) {
            return $event->checkoutable->is($licenseSeat)
                && $event->checkedOutTo->is($user)
                && $event->checkedInBy->is($actor)
                && $event->note === 'my note';
        });

    }
  
    public function testPageRenders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.checkin', LicenseSeat::factory()->assignedToUser()->create()->id))
            ->assertOk();

    }

    public function testAssetAssignmentRemainsTheCheckinTargetWhenTheSeatAlsoTracksTheAssetUser(): void
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $asset = Asset::factory()->create();
        $assetUser = User::factory()->create();
        $licenseSeat = LicenseSeat::factory()
            ->reassignable()
            ->assignedToAsset($asset)
            ->create(['assigned_to' => $assetUser->id]);

        $this->actingAs(User::factory()->checkinLicenses()->create())
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'redirect_option' => 'index',
            ])
            ->assertRedirect(route('licenses.index'));

        Event::assertDispatched(CheckoutableCheckedIn::class, function (CheckoutableCheckedIn $event) use ($asset) {
            return $event->checkedOutTo->is($asset);
        });
    }

    public function testBulkCheckinProcessesEachSeatOnceIncludingAssetSeatsWithAnOwner(): void
    {
        $license = License::factory()->create([
            'seats' => 3,
            'reassignable' => true,
        ]);
        $seats = $license->licenseseats()->orderBy('id')->get();
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $otherAsset = Asset::factory()->create();

        $seats[0]->update(['assigned_to' => $user->id]);
        $seats[1]->update(['asset_id' => $asset->id, 'assigned_to' => $user->id]);
        $seats[2]->update(['asset_id' => $otherAsset->id]);

        $this->actingAs(User::factory()->checkinLicenses()->create())
            ->from(route('licenses.show', $license))
            ->post(route('licenses.bulkcheckin', $license))
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('success');

        foreach ($seats as $seat) {
            $this->assertNull($seat->fresh()->assigned_to);
            $this->assertNull($seat->fresh()->asset_id);
        }

        $checkins = Actionlog::query()
            ->where('item_type', License::class)
            ->where('item_id', $license->id)
            ->where('action_type', 'checkin from')
            ->get();

        $this->assertCount(3, $checkins);
        $this->assertTrue($checkins->contains(fn (Actionlog $log) =>
            $log->target_type === Asset::class && $log->target_id === $asset->id
        ));
    }

    public function testCheckoutPermissionDoesNotAuthorizeLicenseCheckin(): void
    {
        $licenseSeat = LicenseSeat::factory()
            ->reassignable()
            ->assignedToUser()
            ->create();

        $this->actingAs(User::factory()->checkoutLicenses()->create())
            ->post(route('licenses.checkin.save', $licenseSeat), [
                'redirect_option' => 'index',
            ])
            ->assertForbidden();

        $this->assertNotNull($licenseSeat->fresh()->assigned_to);
    }
}
