<?php

namespace Tests\Feature\Assets\Api;

use App\Events\CheckoutableCheckedIn;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\TestsFullMultipleCompaniesSupport;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\TestCase;

class DeleteAssetsTest extends TestCase implements TestsFullMultipleCompaniesSupport, TestsPermissionsRequirement
{
    public function testRequiresPermission()
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->create())
            ->deleteJson(route('api.assets.destroy', $asset))
            ->assertForbidden();

        $this->assertNotSoftDeleted($asset);
    }

    public function testAdheresToFullMultipleCompaniesSupportScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $assetA = Asset::factory()->for($companyA)->create();
        $assetB = Asset::factory()->for($companyB)->create();
        $assetC = Asset::factory()->for($companyB)->create();

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->deleteAssets()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->deleteAssets()->make());

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($userInCompanyA)
            ->deleteJson(route('api.assets.destroy', $assetB))
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($userInCompanyB)
            ->deleteJson(route('api.assets.destroy', $assetA))
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($superUser)
            ->deleteJson(route('api.assets.destroy', $assetC))
            ->assertStatusMessageIs('success');

        $this->assertNotSoftDeleted($assetA);
        $this->assertNotSoftDeleted($assetB);
        $this->assertSoftDeleted($assetC);
    }

    public function testDeletingLegacyAssignedAssetClearsStaleStateWithoutCreatingCheckinHistory()
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $assignedUser = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($assignedUser)->create([
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $pendingAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $assignedUser->id,
        ]);

        $this->actingAsForApi(User::factory()->deleteAssets()->create())
            ->deleteJson(route('api.assets.destroy', $asset))
            ->assertStatusMessageIs('success');

        $asset->refresh();
        $this->assertSoftDeleted($asset);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSoftDeleted($pendingAcceptance);
        $this->assertDatabaseHas('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'delete',
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'checkin from',
        ]);
        Event::assertNotDispatched(CheckoutableCheckedIn::class);
    }

    public function testCanDeleteAsset()
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->deleteAssets()->create())
            ->deleteJson(route('api.assets.destroy', $asset))
            ->assertStatusMessageIs('success');

        $this->assertSoftDeleted($asset);
        $this->assertHasTheseActionLogs($asset, ['create', 'delete']);
    }
}
