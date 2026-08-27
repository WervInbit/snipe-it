<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\TestResult;
use App\Models\TestResultPhoto;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Services\SafeRasterImageService;
use App\Services\WorkflowEvidencePhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class WorkflowEvidencePhotoSecurityTest extends TestCase
{
    use CreatesPolyglotRasterUploads;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake(config('filesystems.default'));
    }

    private function makeRun(): array
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $type = TestType::factory()->create();
        $result = TestResult::factory()->for($run)->for($type, 'type')->create();

        return [$asset, $run, $result, $user];
    }

    private function upload(array $run, UploadedFile $file)
    {
        [$asset, $testRun, $result, $user] = $run;

        return $this->actingAs($user, 'web')->post(
            route('test-results.partial-update', [$asset, $testRun, $result]),
            ['photo' => $file],
            ['HTTP_ACCEPT' => 'application/json']
        );
    }

    public function test_valid_raster_is_reencoded_with_a_server_generated_name_in_private_storage(): void
    {
        $run = $this->makeRun();
        $original = UploadedFile::fake()->image('operator-name.jpg', 320, 240);
        File::append($original->getRealPath(), '<?php echo "must be removed";');
        $polyglot = new UploadedFile(
            $original->getRealPath(),
            'operator-name.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->upload($run, $polyglot);

        $response->assertOk();
        $photo = TestResultPhoto::query()->sole();
        $this->assertMatchesRegularExpression(
            '#^private_uploads/workflow_evidence/results/'.$run[2]->id.'/[0-9a-f-]{36}\.jpg$#',
            $photo->path
        );
        $this->assertStringNotContainsString('operator-name', $photo->path);
        Storage::disk(config('filesystems.default'))->assertExists($photo->path);
        $stored = Storage::get($photo->path);
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
    }

    public function test_deleting_a_workflow_run_removes_its_private_evidence_files(): void
    {
        [$asset, $testRun, $result, $user] = $this->makeRun();
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/delete-with-run.jpg';
        Storage::put($path, UploadedFile::fake()->image('evidence.jpg')->getContent());
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $this->actingAs($user)
            ->delete(route('test-runs.destroy', [$asset, $testRun]))
            ->assertRedirect(route('test-runs.index', $asset->id));

        Storage::assertMissing($path);
        $this->assertDatabaseMissing('workflow_result_photos', ['id' => $photo->id]);
        $this->assertDatabaseMissing('workflow_results', ['id' => $result->id]);
        $this->assertDatabaseMissing('workflow_runs', ['id' => $testRun->id]);
    }

    public function test_deleting_private_evidence_does_not_delete_its_promoted_public_copy(): void
    {
        Storage::fake('public');
        [$asset, $testRun, $result, $user] = $this->makeRun();
        $privatePath = 'private_uploads/workflow_evidence/results/'.$result->id.'/source.jpg';
        $publicPath = 'assets/'.$asset->id.'/promoted.jpg';
        Storage::put($privatePath, UploadedFile::fake()->image('source.jpg')->getContent());
        Storage::disk('public')->put($publicPath, UploadedFile::fake()->image('promoted.jpg')->getContent());
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $privatePath,
        ]);
        $assetImage = $asset->images()->create([
            'file_path' => $publicPath,
            'caption' => 'Published copy',
            'sort_order' => 0,
            'source' => 'test_photo',
            'source_photo_id' => $photo->id,
        ]);

        $this->actingAs($user)
            ->delete(route('test-runs.destroy', [$asset, $testRun]))
            ->assertRedirect();

        Storage::assertMissing($privatePath);
        Storage::disk('public')->assertExists($publicPath);
        $this->assertNull(AssetImage::query()->findOrFail($assetImage->id)->source_photo_id);
    }

    public function test_asset_soft_delete_and_restore_preserve_public_gallery_and_private_evidence(): void
    {
        Storage::fake('public');
        [$asset, $testRun, $result, $user] = $this->makeRun();
        $privatePath = 'private_uploads/workflow_evidence/results/'.$result->id.'/retained.jpg';
        $publicPath = 'assets/'.$asset->id.'/retained.jpg';
        Storage::put($privatePath, UploadedFile::fake()->image('private.jpg')->getContent());
        Storage::disk('public')->put($publicPath, UploadedFile::fake()->image('public.jpg')->getContent());
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $privatePath,
        ]);
        $assetImage = $asset->images()->create([
            'file_path' => $publicPath,
            'caption' => 'Retained gallery image',
            'sort_order' => 0,
            'source' => 'test_photo',
            'source_photo_id' => $photo->id,
        ]);
        $asset->forceFill([
            'image' => $asset->id.'/retained.jpg',
            'image_override_enabled' => true,
        ])->save();

        $this->actingAs($user)
            ->delete(route('hardware.destroy', $asset))
            ->assertRedirect(route('hardware.index'));

        Storage::assertExists($privatePath);
        Storage::disk('public')->assertExists($publicPath);
        $this->assertDatabaseHas('asset_images', ['id' => $assetImage->id]);
        $this->assertDatabaseHas('workflow_result_photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('workflow_runs', ['id' => $testRun->id]);

        $this->actingAs($user)
            ->post(route('restore/hardware', $asset))
            ->assertRedirect(route('hardware.index'));

        Storage::assertExists($privatePath);
        Storage::disk('public')->assertExists($publicPath);
        $this->assertDatabaseHas('asset_images', ['id' => $assetImage->id]);
        $this->assertDatabaseHas('workflow_result_photos', ['id' => $photo->id]);
    }

    public function test_uploaded_jpeg_orientation_is_normalized_before_private_storage(): void
    {
        $run = $this->makeRun();

        $this->upload(
            $run,
            $this->makeJpegWithExifOrientation('portrait.jpg', 6, 40, 20)
        )->assertOk();

        $photo = TestResultPhoto::query()->sole();
        $stored = Storage::get($photo->path);
        $imageInfo = getimagesizefromstring($stored);

        $this->assertIsArray($imageInfo);
        $this->assertSame(20, $imageInfo[0]);
        $this->assertSame(40, $imageInfo[1]);
        $this->assertStringNotContainsString("Exif\x00\x00", $stored);
        $this->assertLessThanOrEqual(SafeRasterImageService::MAX_BYTES, strlen($stored));
    }

    public function test_php_html_svg_and_non_image_payloads_are_rejected(): void
    {
        $run = $this->makeRun();
        foreach ([
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo "owned";'),
            UploadedFile::fake()->createWithContent('page.html', '<script>alert(1)</script>'),
            UploadedFile::fake()->createWithContent(
                'vector.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
            ),
            UploadedFile::fake()->createWithContent('fake.jpg', 'this is not an image'),
        ] as $file) {
            $response = $this->upload($run, $file);
            $response->assertUnprocessable()->assertJsonValidationErrors('photo');
        }

        $this->assertSame(0, TestResultPhoto::query()->count());
        $this->assertSame([], Storage::allFiles());
    }

    public function test_oversized_photo_is_rejected_before_decoding(): void
    {
        $response = $this->upload(
            $this->makeRun(),
            UploadedFile::fake()->create('oversized.jpg', 5121, 'image/jpeg')
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertSame(0, TestResultPhoto::query()->count());
    }

    public function test_mime_mismatch_is_rejected(): void
    {
        $png = UploadedFile::fake()->image('photo.png', 32, 32);
        $mismatched = new UploadedFile(
            $png->getRealPath(),
            'photo.png',
            'image/jpeg',
            null,
            true
        );

        $response = $this->upload($this->makeRun(), $mismatched);

        $response->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertSame(0, TestResultPhoto::query()->count());
    }

    public function test_extension_mismatch_is_rejected(): void
    {
        $png = UploadedFile::fake()->image('photo.png', 32, 32);
        $mismatched = new UploadedFile(
            $png->getRealPath(),
            'photo.jpg',
            'image/png',
            null,
            true
        );

        $response = $this->upload($this->makeRun(), $mismatched);

        $response->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertSame(0, TestResultPhoto::query()->count());
    }

    public function test_private_photo_is_served_only_through_the_authorized_controlled_route(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $this->upload([$asset, $run, $result, $user], UploadedFile::fake()->image('photo.jpg'))
            ->assertOk();
        $photo = TestResultPhoto::query()->sole();
        $url = route('test-results.photos.show', [$asset, $run, $result, $photo]);

        auth('web')->logout();
        $this->get($url)->assertRedirect();

        $unauthorized = User::factory()->create(['permissions' => '{}']);
        $this->actingAs($unauthorized, 'web')->get($url)->assertForbidden();

        $response = $this->actingAs($user, 'web')
            ->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_stored_photo_over_the_current_decode_budget_is_not_served(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $width = 7000;
        $height = intdiv(SafeRasterImageService::maxPixels(), $width) + 1;
        $oversized = $this->makePngHeaderWithDimensions(
            'oversized-dimensions.png',
            $width,
            $height
        );
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/oversized.png';
        Storage::put($path, file_get_contents($oversized->getRealPath()));
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('test-results.photos.show', [$asset, $run, $result, $photo]))
            ->assertNotFound();
    }

    public function test_stored_photo_with_oversized_reencoded_output_is_not_served(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $uploaded = UploadedFile::fake()->image('photo.jpg');
        $path = 'private_uploads/workflow_evidence/results/'.$result->id.'/photo.jpg';
        Storage::put($path, file_get_contents($uploaded->getRealPath()));
        $photo = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $path,
        ]);

        $this->app->instance(SafeRasterImageService::class, new class extends SafeRasterImageService {
            protected function reencode(
                string $contents,
                string $mime,
                string $field,
                int $orientation = 1
            ): string {
                return str_repeat('x', self::MAX_BYTES + 1);
            }
        });

        $this->actingAs($user, 'web')
            ->get(route('test-results.photos.show', [$asset, $run, $result, $photo]))
            ->assertNotFound();
    }

    public function test_workflow_pages_render_the_controlled_photo_url(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $this->upload([$asset, $run, $result, $user], UploadedFile::fake()->image('photo.jpg'))
            ->assertOk();
        $photo = TestResultPhoto::query()->sole();
        $url = route('test-results.photos.show', [$asset, $run, $result, $photo]);

        $this->actingAs($user, 'web')
            ->get(route('test-results.active', ['asset' => $asset, 'run' => $run]))
            ->assertOk()
            ->assertSee($url, false);

        $this->actingAs($user, 'web')
            ->get(route('test-runs.index', $asset))
            ->assertOk()
            ->assertSee($url, false);
    }

    public function test_cross_asset_and_cross_result_photo_paths_return_not_found(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $this->upload([$asset, $run, $result, $user], UploadedFile::fake()->image('photo.jpg'))
            ->assertOk();
        $photo = TestResultPhoto::query()->sole();

        auth('web')->logout();
        $otherAsset = Asset::factory()->create();
        $this->actingAs($user, 'web')
            ->get(route('test-results.photos.show', [$otherAsset, $run, $result, $photo]))
            ->assertNotFound();

        $otherType = TestType::factory()->create();
        $otherResult = TestResult::factory()->for($run)->for($otherType, 'type')->create();
        $this->actingAs($user, 'web')
            ->get(route('test-results.photos.show', [$asset, $run, $otherResult, $photo]))
            ->assertNotFound();
    }

    public function test_legacy_public_photo_is_served_through_the_controlled_route(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $legacyDirectory = public_path('uploads/test_images');
        File::ensureDirectoryExists($legacyDirectory);
        $legacyFile = UploadedFile::fake()->image('legacy.jpg');
        $legacyName = 'legacy-'.uniqid().'.jpg';
        File::copy($legacyFile->getRealPath(), $legacyDirectory.'/'.$legacyName);

        try {
            $photo = TestResultPhoto::create([
                'workflow_result_id' => $result->id,
                'path' => 'uploads/test_images/'.$legacyName,
            ]);

            $this->actingAs($user, 'web')
                ->get(route('test-results.photos.show', [$asset, $run, $result, $photo]))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/jpeg')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        } finally {
            File::delete($legacyDirectory.'/'.$legacyName);
        }
    }

    public function test_photo_limit_is_enforced_before_any_new_evidence_is_stored(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();

        foreach (range(1, WorkflowEvidencePhotoService::MAX_PHOTOS_PER_RESULT) as $index) {
            TestResultPhoto::create([
                'workflow_result_id' => $result->id,
                'path' => 'private_uploads/workflow_evidence/results/'.$result->id.'/existing-'.$index.'.jpg',
            ]);
        }

        $this->actingAs($user, 'web')
            ->post(
                route('test-results.partial-update', [$asset, $run, $result]),
                ['photo' => UploadedFile::fake()->image('one-too-many.jpg')],
                ['HTTP_ACCEPT' => 'application/json']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->assertSame(
            WorkflowEvidencePhotoService::MAX_PHOTOS_PER_RESULT,
            $result->photos()->count()
        );
        $this->assertSame([], Storage::allFiles());
    }

    public function test_replacement_keeps_existing_evidence_and_cleans_new_file_when_database_write_fails(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $existingUpload = UploadedFile::fake()->image('existing.jpg');
        $existingPath = 'private_uploads/workflow_evidence/results/'.$result->id.'/existing.jpg';
        Storage::put($existingPath, file_get_contents($existingUpload->getRealPath()));
        $existingPhoto = TestResultPhoto::create([
            'workflow_result_id' => $result->id,
            'path' => $existingPath,
        ]);
        $result->update([
            'status' => TestResult::STATUS_NVT,
            'photo_path' => $existingPath,
        ]);

        TestResultPhoto::creating(function (): void {
            throw new \RuntimeException('Simulated photo-row failure.');
        });

        $this->actingAs($user, 'web')
            ->put(
                route('test-results.update', [$asset, $run]),
                [
                    'status' => [$result->id => TestResult::STATUS_FAIL],
                    'photo' => [$result->id => UploadedFile::fake()->image('replacement.jpg')],
                ]
            )
            ->assertServerError();

        $result->refresh();
        $this->assertSame(TestResult::STATUS_NVT, $result->status);
        $this->assertSame($existingPath, $result->photo_path);
        $this->assertDatabaseHas('workflow_result_photos', [
            'id' => $existingPhoto->id,
            'path' => $existingPath,
        ]);
        $this->assertSame([$existingPath], Storage::allFiles());
    }
}
