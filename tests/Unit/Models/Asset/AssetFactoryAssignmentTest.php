<?php

namespace Tests\Unit\Models\Asset;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class AssetFactoryAssignmentTest extends TestCase
{
    public function test_user_assignment_state_bypasses_request_mass_assignment_guard(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($user)->create()->fresh();

        $this->assertTrue($asset->checkedOutToUser());
        $this->assertTrue($asset->assignedTo->is($user));
        $this->assertNotNull($asset->last_checkout);
    }

    public function test_location_assignment_state_bypasses_request_mass_assignment_guard(): void
    {
        $location = Location::factory()->create();
        $asset = Asset::factory()->assignedToLocation($location)->create()->fresh();

        $this->assertTrue($asset->checkedOutToLocation());
        $this->assertTrue($asset->assignedTo->is($location));
    }

    public function test_asset_assignment_state_bypasses_request_mass_assignment_guard(): void
    {
        $target = Asset::factory()->create();
        $asset = Asset::factory()->assignedToAsset($target)->create()->fresh();

        $this->assertTrue($asset->checkedOutToAsset());
        $this->assertTrue($asset->assignedTo->is($target));
    }
}
