<?php

namespace Tests\Feature\Assets;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\TestResult;
use App\Models\TestResultPhoto;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Services\SafeRasterImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class PromoteTestResultPhotoToAssetImageTest extends TestCase
{
    use CreatesPolyglotRasterUploads;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_test_result_photo_can_be_promoted_to_asset_override_image(): void
    {
        Storage::fake('public');
        Storage::fake(config('filesystems.default'));

        $asset = Asset::factory()->create([
            'image_override_enabled' => false,
            'image' => null,
        ]);
        $user = User::factory()->refurbisher()->editAssets()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create(['name' => 'Camera']);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();

        $uploaded = UploadedFile::fake()->image('camera.jpg', 640, 480);
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/camera.jpg';
        Storage::put($path, file_get_contents($uploaded->getRealPath()));

        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('test-results.photos.promote', [$asset, $run, $result, $photo]),
            [
                'caption' => 'Promoted camera image',
                'enable_override' => true,
                'make_cover' => true,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('image.source', 'test_photo')
            ->assertJsonPath('image_override_enabled', true);

        $asset->refresh();

        $this->assertTrue($asset->image_override_enabled);
        $this->assertNotNull($asset->image);
        $this->assertDatabaseHas('asset_images', [
            'asset_id' => $asset->id,
            'source' => 'test_photo',
            'source_photo_id' => $photo->id,
            'caption' => 'Promoted camera image',
        ]);

        $storedPath = $asset->images()->first()->file_path;
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_promoted_photo_is_orientation_normalized_and_output_bounded(): void
    {
        Storage::fake('public');
        Storage::fake(config('filesystems.default'));

        $asset = Asset::factory()->create();
        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.edit' => '1',
                'assets.images.upload' => '1',
                'tests.execute' => '1',
            ]),
        ]);
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create(['name' => 'Camera']);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();
        $uploaded = $this->makeJpegWithExifOrientation('portrait.jpg', 6, 40, 20);
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/portrait.jpg';
        Storage::put($path, file_get_contents($uploaded->getRealPath()));
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $this->actingAs($user)->postJson(
            route('test-results.photos.promote', [$asset, $run, $result, $photo]),
            [
                'enable_override' => true,
                'make_cover' => true,
            ]
        )->assertOk();

        $storedPath = $asset->images()->sole()->file_path;
        $stored = Storage::disk('public')->get($storedPath);
        $imageInfo = getimagesizefromstring($stored);

        $this->assertIsArray($imageInfo);
        $this->assertSame(20, $imageInfo[0]);
        $this->assertSame(40, $imageInfo[1]);
        $this->assertStringNotContainsString("Exif\x00\x00", $stored);
        $this->assertLessThanOrEqual(SafeRasterImageService::MAX_BYTES, strlen($stored));
    }

    public function test_failed_promotion_database_write_cleans_the_new_public_file(): void
    {
        Storage::fake('public');
        Storage::fake(config('filesystems.default'));

        $asset = Asset::factory()->create([
            'image_override_enabled' => false,
            'image' => null,
        ]);
        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.edit' => '1',
                'assets.images.upload' => '1',
                'tests.execute' => '1',
            ]),
        ]);
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create(['name' => 'Camera']);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();
        $uploaded = UploadedFile::fake()->image('camera.jpg', 640, 480);
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/camera.jpg';
        Storage::put($path, file_get_contents($uploaded->getRealPath()));
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        AssetImage::creating(function (): void {
            throw new \RuntimeException('Simulated asset-image failure.');
        });

        $this->actingAs($user)->postJson(
            route('test-results.photos.promote', [$asset, $run, $result, $photo]),
            [
                'enable_override' => true,
                'make_cover' => true,
            ]
        )
            ->assertServerError();

        $this->assertSame(0, $asset->images()->count());
        $this->assertFalse($asset->fresh()->image_override_enabled);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_workflow_editor_without_gallery_permission_cannot_publish_private_evidence(): void
    {
        Storage::fake('public');
        Storage::fake(config('filesystems.default'));

        $asset = Asset::factory()->create();
        $user = User::factory()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.edit' => '1',
                'tests.execute' => '1',
            ]),
        ]);
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create(['name' => 'Camera']);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();
        $uploaded = UploadedFile::fake()->image('private-evidence.jpg');
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/private-evidence.jpg';
        Storage::put($path, file_get_contents($uploaded->getRealPath()));
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $this->actingAs($user)->postJson(
            route('test-results.photos.promote', [$asset, $run, $result, $photo])
        )->assertForbidden();

        $this->assertSame(0, $asset->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Storage::disk(config('filesystems.default'))->assertExists($path);
    }
}
