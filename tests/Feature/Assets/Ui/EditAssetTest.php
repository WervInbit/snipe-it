<?php

namespace Tests\Feature\Assets\Ui;

use App\Events\CheckoutableCheckedIn;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CheckoutAcceptance;
use App\Models\Location;
use App\Models\StatusLabel;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class EditAssetTest extends TestCase
{

    public function testPermissionRequiredToViewAsset()
    {
        $asset = Asset::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('hardware.edit', $asset))
            ->assertForbidden();
    }

    public function testPageCanBeAccessed(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->editAssets()->create();
        $response = $this->actingAs($user)->get(route('hardware.edit', $asset));
        $response->assertStatus(200);
    }

    public function testEditPageHidesOptionalInformationBlockAndPlacesNotesBelowStatus(): void
    {
        $asset = Asset::factory()->create();
        $response = $this->actingAs(User::factory()->editAssets()->create())
            ->get(route('hardware.edit', $asset));

        $response->assertOk();
        $response->assertDontSee(trans('admin/hardware/form.optional_infos'));

        $content = $response->getContent();
        $statusPos = strpos($content, 'name="status_id"');
        $notesPos = strpos($content, 'name="notes"');
        $modelSpecPos = strpos($content, 'id="model_spec_content"');

        $this->assertNotFalse($statusPos);
        $this->assertNotFalse($notesPos);
        $this->assertNotFalse($modelSpecPos);
        $this->assertFalse(str_contains($content, 'name="status_change_note"'));
        $this->assertTrue($statusPos < $notesPos);
        $this->assertTrue($notesPos < $modelSpecPos);
    }

    public function testCreateAndEditPagesRenderMobileFloatingSaveButton(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('hardware.create'))
            ->assertOk()
            ->assertSee('data-testid="hardware-mobile-save-fab-wrapper"', false)
            ->assertSee('data-testid="hardware-mobile-save-fab"', false)
            ->assertDontSee('<select class="redirect-options form-control select2"', false)
            ->assertDontSee('name="name"', false);

        $this->actingAs($user)
            ->get(route('hardware.edit', $asset))
            ->assertOk()
            ->assertSee('data-testid="hardware-mobile-save-fab-wrapper"', false)
            ->assertSee('data-testid="hardware-mobile-save-fab"', false)
            ->assertSee('<select class="redirect-options form-control select2"', false)
            ->assertSee('name="name"', false);
    }

    public function testAssetEditPostIsRedirectedIfRedirectSelectionIsIndex()
    {
        $asset = Asset::factory()->assignedToUser()->create();
        $originalTag = $asset->asset_tag;

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset),
                [
                    'redirect_option' => 'index',
                    'name' => 'New name',
                    'asset_tags' => $asset->asset_tag,
                    'status_id' => StatusLabel::factory()->create()->id,
                    'model_id' => AssetModel::factory()->create()->id,
                ])
            ->assertStatus(302)
            ->assertRedirect(route('hardware.index'));
        $asset->refresh();
        $this->assertEquals($originalTag, $asset->asset_tag);
    }
    public function testAssetEditPostIsRedirectedIfRedirectSelectionIsItem()
    {
        $asset = Asset::factory()->create();
        $originalTag = $asset->asset_tag;

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'redirect_option' => 'item',
                'name' => 'New name',
                'asset_tags' => $asset->asset_tag,
                'status_id' => StatusLabel::factory()->create()->id,
                'model_id' => AssetModel::factory()->create()->id,
            ])
            ->assertStatus(302)
            ->assertRedirect(route('hardware.show', $asset));

        $asset->refresh();
        $this->assertEquals($originalTag, $asset->asset_tag);
    }

    public function testNonAdminCannotChangeAssetTag(): void
    {
        $asset = Asset::factory()->create();
        $originalTag = $asset->asset_tag;

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => 'New Asset Tag',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('asset_tag');

        $asset->refresh();
        $this->assertEquals($originalTag, $asset->asset_tag);
    }

    public function testAdminCanChangeAssetTagWithDefaultUppercaseNormalization(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => 'New Asset Tag',
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('NEW ASSET TAG', $asset->asset_tag);
    }

    public function testAdminCanPreserveAssetTagCaseWithOverride(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => [1 => 'New Asset Tag'],
                'asset_tag_case_override' => [1 => '1'],
            ])
            ->assertRedirect();

        $this->assertSame('New Asset Tag', $asset->fresh()->asset_tag);
    }

    public function testUndeployableEditClearsLegacyAssignmentWithoutCreatingCheckinHistory()
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $user = User::factory()->create();
        $deployable_status = Statuslabel::factory()->rtd()->create();
        $achived_status = Statuslabel::factory()->archived()->create();
        $asset = Asset::factory()->assignedToUser($user)->create([
            'status_id' => $deployable_status->id,
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $pendingAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
        $completedAcceptance = CheckoutAcceptance::factory()->accepted()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);
        $this->assertTrue($asset->assignedTo->is($user));

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                    'redirect_option' => 'item',
                    'status_id' => $achived_status->id,
                    'model_id' => $asset->model_id,
                    'model_number_id' => $asset->model_number_id ?? $asset->model?->primaryModelNumber?->id,
                    'asset_tags' => $asset->asset_tag,
                ],
            )
            ->assertRedirect(route('hardware.show', $asset));

        $asset = Asset::find($asset->id);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertEquals($achived_status->id, $asset->status_id);
        $this->assertSoftDeleted($pendingAcceptance);
        $this->assertNotSoftDeleted($completedAcceptance);
        $this->assertDatabaseHas('asset_status_events', [
            'asset_id' => $asset->id,
            'from_status_id' => $deployable_status->id,
            'to_status_id' => $achived_status->id,
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'checkin from',
        ]);
        Event::assertNotDispatched(CheckoutableCheckedIn::class);
    }

    public function testUndeployableStatusPatchClearsLegacyAssignmentWithoutCreatingCheckinHistory()
    {
        Event::fake([CheckoutableCheckedIn::class]);

        $user = User::factory()->create();
        $deployableStatus = Statuslabel::factory()->rtd()->create();
        $archivedStatus = Statuslabel::factory()->archived()->create();
        $asset = Asset::factory()->assignedToUser($user)->create([
            'status_id' => $deployableStatus->id,
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $pendingAcceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $user->id,
        ]);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $archivedStatus->id,
                'status_change_note' => 'Retired without legacy check-in.',
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $asset->refresh();
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSame($archivedStatus->id, $asset->status_id);
        $this->assertSoftDeleted($pendingAcceptance);
        $this->assertDatabaseHas('asset_status_events', [
            'asset_id' => $asset->id,
            'from_status_id' => $deployableStatus->id,
            'to_status_id' => $archivedStatus->id,
            'note' => 'Retired without legacy check-in.',
        ]);
        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'checkin from',
        ]);
        Event::assertNotDispatched(CheckoutableCheckedIn::class);
    }

    public function testCurrentLocationIsNotUpdatedOnEdit()
    {
        $defaultLocation = Location::factory()->create();
        $currentLocation = Location::factory()->create();
        $asset = Asset::factory()->create([
            'location_id' => $currentLocation->id,
            'rtd_location_id' => $defaultLocation->id
        ]);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->put(route('hardware.update', $asset), [
                'redirect_option' => 'item',
                'name' => 'New name',
                'asset_tags' => $asset->asset_tag,
                'status_id' => $asset->status_id,
                'model_id' => $asset->model_id,
                'model_number_id' => $asset->model_number_id,
            ]);

        $asset->refresh();
        $this->assertEquals('New name', $asset->name);
        $this->assertEquals($currentLocation->id, $asset->location_id);
    }

    public function testImageDeleteConfinesLegacyImagePathToAssetsDirectory(): void
    {
        $originalPublicPath = $this->app->publicPath();
        $temporaryPublicPath = storage_path('framework/testing/public-'.Str::uuid());
        $asset = Asset::factory()->create([
            'model_id' => null,
            'model_number_id' => null,
            'image' => '../keep.jpg',
        ]);

        File::ensureDirectoryExists($temporaryPublicPath.'/uploads/assets');
        File::put($temporaryPublicPath.'/uploads/keep.jpg', 'must remain');
        File::put($temporaryPublicPath.'/uploads/assets/keep.jpg', 'asset image');
        $this->app->usePublicPath($temporaryPublicPath);

        try {
            $this->actingAs(User::factory()->superuser()->create())
                ->put(route('hardware.update', $asset), [
                    'redirect_option' => 'item',
                    'asset_tags' => $asset->asset_tag,
                    'status_id' => $asset->status_id,
                    'image_delete' => '1',
                ])
                ->assertRedirect(route('hardware.show', $asset));

            $this->assertFileExists($temporaryPublicPath.'/uploads/keep.jpg');
            $this->assertFileDoesNotExist($temporaryPublicPath.'/uploads/assets/keep.jpg');
            $this->assertNull($asset->fresh()->image);
        } finally {
            $this->app->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryPublicPath);
        }
    }

    public function testEditAcceptsSerialArrayInputFromFormShape(): void
    {
        $asset = Asset::factory()->create();
        $status = StatusLabel::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->put(route('hardware.update', $asset), [
                'redirect_option' => 'item',
                'name' => $asset->name,
                'asset_tags' => [1 => $asset->asset_tag],
                'serials' => [1 => 'SERIAL-ARRAY-1'],
                'status_id' => $status->id,
                'model_id' => $asset->model_id,
                'model_number_id' => $asset->model_number_id,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $asset->refresh();
        $this->assertSame('SERIAL-ARRAY-1', $asset->serial);
    }

    public function testSellableFlagCanBeToggled()
    {
        $asset = Asset::factory()->create(['is_sellable' => true]);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->patch(route('hardware.update', $asset), [
                'is_sellable' => 0,
            ])
            ->assertRedirect();

        $this->assertFalse($asset->fresh()->is_sellable);
    }
}
