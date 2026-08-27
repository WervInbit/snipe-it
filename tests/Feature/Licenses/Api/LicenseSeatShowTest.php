<?php

namespace Tests\Feature\Licenses\Api;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseSeatShowTest extends TestCase
{
    public function testSeatCanBeShownForItsLicense(): void
    {
        $license = License::factory()->create();
        $seat = LicenseSeat::factory()->for($license)->create();

        $this->actingAsForApi(User::factory()->viewLicenses()->create())
            ->getJson(route('api.licenses.seats.show', [$license->id, $seat->id]))
            ->assertOk()
            ->assertJsonPath('id', $seat->id)
            ->assertJsonPath('license_id', $license->id);
    }

    public function testSeatCannotBeShownThroughAnotherLicense(): void
    {
        $license = License::factory()->create();
        $otherLicense = License::factory()->create();
        $seat = LicenseSeat::factory()->for($license)->create();

        $this->actingAsForApi(User::factory()->viewLicenses()->create())
            ->getJson(route('api.licenses.seats.show', [$otherLicense->id, $seat->id]))
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonPath('messages', 'Seat does not belong to the specified license');
    }
}
