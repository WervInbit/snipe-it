<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestResultPhoto;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromoteTestResultPhotoToAssetImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_test_result_photo_can_be_promoted_to_asset_override_image(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create([
            'image_override_enabled' => false,
            'image' => null,
        ]);
        $user = User::factory()->refurbisher()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create(['name' => 'Camera']);
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();

        $destination = public_path('uploads/test_images');
        File::ensureDirectoryExists($destination);
        $uploaded = UploadedFile::fake()->image('camera.jpg', 640, 480);
        $filename = uniqid('test_', true).'.jpg';
        $uploaded->move($destination, $filename);

        $photo = TestResultPhoto::create([
            'test_result_id' => $result->id,
            'path' => 'uploads/test_images/'.$filename,
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

        File::delete(public_path($photo->path));
    }
}
