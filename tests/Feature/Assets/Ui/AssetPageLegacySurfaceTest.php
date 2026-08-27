<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class AssetPageLegacySurfaceTest extends TestCase
{
    public function testDeprecatedNestedAssetTabIsAbsentAndFileUploadLivesInFilesTab(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertDontSee('href="#assets"', false)
            ->assertDontSee('id="assets"', false)
            ->assertDontSee('assetsListingTable', false)
            ->assertDontSee('bulkAssetEditButton', false)
            ->assertDontSee("document.getElementById('upload-form')", false)
            ->assertSee('href="#files"', false)
            ->assertSee('id="files"', false)
            ->assertSee('id="upload-form"', false)
            ->assertSee(trans('general.private_attachment_notice'))
            ->assertSee(trans('general.public_gallery_notice'))
            ->assertSee(
                route('ui.files.store', ['object_type' => 'assets', 'id' => $asset->id]),
                false,
            );

        $html = $response->getContent();
        $filesTabPosition = strpos($html, 'id="files"');
        $uploadFormPosition = strpos($html, 'id="upload-form"');

        $this->assertNotFalse($filesTabPosition);
        $this->assertNotFalse($uploadFormPosition);
        $this->assertGreaterThan($filesTabPosition, $uploadFormPosition);
    }

    public function testAssetFileTabAndUploadUseSeparateDedicatedPermissions(): void
    {
        $asset = Asset::factory()->create();
        $ordinaryAssetEditor = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.edit' => '1',
            ]),
        ]);
        $fileViewer = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.files.view' => '1',
            ]),
        ]);
        $fileUploader = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.files.view' => '1',
                'assets.files.upload' => '1',
            ]),
        ]);

        $this->actingAs($ordinaryAssetEditor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertDontSee('href="#files"', false)
            ->assertDontSee('id="files"', false);

        $this->actingAs($fileViewer)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('href="#files"', false)
            ->assertSee('id="files"', false)
            ->assertDontSee('id="upload-form"', false);

        $this->actingAs($fileUploader)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('href="#files"', false)
            ->assertSee('id="upload-form"', false);
    }

    public function testLicenseCheckinActionIsHiddenWithoutTheCheckinPermission(): void
    {
        $asset = Asset::factory()->create();
        $seat = LicenseSeat::factory()->assignedToAsset($asset)->create();
        $viewer = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'licenses.view' => '1',
            ]),
        ]);
        $checkinManager = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'licenses.view' => '1',
                'licenses.checkin' => '1',
            ]),
        ]);
        $checkinUrl = route('licenses.checkin', $seat->id);

        $this->actingAs($viewer)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertDontSee($checkinUrl, false);

        $this->actingAs($checkinManager)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee($checkinUrl, false);
    }

    public function testExtraModelFilesTabUsesTheDedicatedModelFilePermission(): void
    {
        $model = AssetModel::factory()->create();
        $asset = Asset::factory()->create(['model_id' => $model->id]);
        $ordinaryModelEditor = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'models.view' => '1',
                'models.edit' => '1',
            ]),
        ]);
        $modelFileViewer = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'models.files.view' => '1',
            ]),
        ]);

        $this->actingAs($ordinaryModelEditor)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertDontSee('href="#modelfiles"', false)
            ->assertDontSee('id="modelfiles"', false);

        $this->actingAs($modelFileViewer)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('href="#modelfiles"', false)
            ->assertSee('id="modelfiles"', false)
            ->assertSee(trans('general.model_resources'))
            ->assertSee(trans('general.model_resources_help', ['model' => $model->name]));
    }
}
