<?php

namespace Tests\Feature\Settings;

use App\Models\AssetModel;
use App\Models\ModelNumberImage;
use App\Models\User;
use App\Services\SafeRasterImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class ModelNumberImageManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesPolyglotRasterUploads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_model_number_edit_page_renders_integrated_image_manager(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('settings.model_numbers.edit', $modelNumber));

        $response
            ->assertOk()
            ->assertSee("document.addEventListener('pointermove', handlePointerMove, { passive: false });", false)
            ->assertSee('Image changes are saved together with the model number.')
            ->assertDontSee('Save Order')
            ->assertDontSee('Upload Image');
    }

    public function test_admin_can_upload_model_number_image_via_main_save(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);
        $trailingPayload = '<?php echo "model-number-form-payload";';

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'new_image' => [
                'caption' => 'Front view',
                'image' => $this->makeJpegWithTrailingPayload('front.pht', $trailingPayload),
            ],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $image = ModelNumberImage::query()->where('model_number_id', $modelNumber->id)->first();
        $this->assertNotNull($image);
        $this->assertSame('Front view', $image->caption);
        $this->assertSame(0, (int) $image->sort_order);
        $this->assertStringEndsWith('.jpg', $image->file_path);
        Storage::disk('public')->assertExists($image->file_path);

        $stored = Storage::disk('public')->get($image->file_path);
        $this->assertStringNotContainsString($trailingPayload, $stored);
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
    }

    public function test_admin_can_reorder_and_update_captions_via_main_save(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $firstImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $secondImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/second.jpg',
            'caption' => 'Second',
            'sort_order' => 1,
        ]);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $firstImage->id => [
                    'caption' => 'Front updated',
                    'delete' => 0,
                ],
                $secondImage->id => [
                    'caption' => 'Back updated',
                    'delete' => 0,
                ],
            ],
            'image_order' => [$secondImage->id, $firstImage->id],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $this->assertDatabaseHas('model_number_images', [
            'id' => $secondImage->id,
            'caption' => 'Back updated',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('model_number_images', [
            'id' => $firstImage->id,
            'caption' => 'Front updated',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_remove_existing_image_via_main_save(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $path = 'model_numbers/'.$modelNumber->id.'/delete-me.jpg';
        Storage::disk('public')->put($path, 'fake-image-data');

        $image = $modelNumber->images()->create([
            'file_path' => $path,
            'caption' => 'Delete me',
            'sort_order' => 0,
        ]);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $image->id => [
                    'caption' => 'Delete me',
                    'delete' => 1,
                ],
            ],
            'image_order' => [],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $this->assertDatabaseMissing('model_number_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_replace_existing_image_via_main_save(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'fake-image-data');

        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Original',
            'sort_order' => 0,
        ]);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $image->id => [
                    'caption' => 'Updated',
                    'delete' => 0,
                    'image' => UploadedFile::fake()->image('replacement.jpg'),
                ],
            ],
            'image_order' => [$image->id],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $image->refresh();
        $this->assertSame('Updated', $image->caption);
        $this->assertNotSame($oldPath, $image->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($image->file_path);
    }

    public function test_main_save_preserves_existing_file_when_replacement_database_write_fails(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Original',
            'sort_order' => 0,
        ]);
        $request = \Illuminate\Http\Request::create('/model-number-image-sync', 'PUT');
        $request->files->set('existing_images', [
            $image->id => [
                'image' => UploadedFile::fake()->image('replacement.jpg'),
            ],
        ]);
        $validated = [
            'existing_images' => [
                $image->id => [
                    'caption' => 'Updated',
                    'delete' => 0,
                ],
            ],
            'image_order' => [$image->id],
        ];
        $eventName = 'eloquent.saving: '.ModelNumberImage::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated model-number image persistence failure.');
        });

        $exception = null;

        try {
            app(\App\Services\ModelNumberImageSyncService::class)
                ->sync($modelNumber, $request, $validated);
        } catch (RuntimeException $caught) {
            $exception = $caught;
        } finally {
            Event::forget($eventName);
        }

        $this->assertNotNull($exception);
        $this->assertSame($oldPath, $image->fresh()->file_path);
        $this->assertSame('Original', $image->fresh()->caption);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles());
    }

    public function test_nested_image_sync_does_not_delete_old_file_before_outer_transaction_commits(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Original',
            'sort_order' => 0,
        ]);
        $request = \Illuminate\Http\Request::create('/model-number-image-sync', 'PUT');
        $validated = [
            'existing_images' => [
                $image->id => [
                    'caption' => 'Original',
                    'delete' => 1,
                ],
            ],
            'image_order' => [],
        ];

        try {
            DB::transaction(function () use ($modelNumber, $request, $validated, $oldPath): void {
                app(\App\Services\ModelNumberImageSyncService::class)
                    ->sync($modelNumber, $request, $validated);

                $this->assertDatabaseMissing('model_number_images', [
                    'model_number_id' => $modelNumber->id,
                ]);
                Storage::disk('public')->assertExists($oldPath);

                throw new RuntimeException('Simulated outer metadata rollback.');
            });
            $this->fail('Expected the outer metadata transaction to roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated outer metadata rollback.', $exception->getMessage());
        }

        $this->assertDatabaseHas('model_number_images', [
            'id' => $image->id,
            'file_path' => $oldPath,
        ]);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_settings_update_rolls_back_metadata_and_new_files_when_raster_sync_fails(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $primary = $model->ensurePrimaryModelNumber();
        $modelNumber = $model->modelNumbers()->create([
            'code' => 'ROLLBACK-OLD',
            'label' => 'Before',
        ]);
        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Original',
            'sort_order' => 0,
        ]);

        $failingRasterImages = new class extends SafeRasterImageService {
            private int $storeCalls = 0;

            public function storePublic(
                UploadedFile $file,
                string $directory,
                string $filenamePrefix,
                string $field = 'image'
            ): array {
                $this->storeCalls++;

                if ($this->storeCalls === 2) {
                    throw ValidationException::withMessages([
                        $field => 'Simulated raster normalization failure.',
                    ]);
                }

                return parent::storePublic($file, $directory, $filenamePrefix, $field);
            }
        };
        $this->app->instance(SafeRasterImageService::class, $failingRasterImages);

        $response = $this->actingAs($user)->put(
            route('settings.model_numbers.update', $modelNumber),
            [
                'code' => 'ROLLBACK-NEW',
                'label' => 'After',
                'status' => 'active',
                'make_primary' => 1,
                'existing_images' => [
                    $image->id => [
                        'caption' => 'Replacement',
                        'delete' => 0,
                        'image' => UploadedFile::fake()->image('replacement.jpg'),
                    ],
                ],
                'image_order' => [$image->id],
                'new_image' => [
                    'caption' => 'Second',
                    'image' => UploadedFile::fake()->image('second.jpg'),
                ],
            ]
        );

        $response->assertSessionHasErrors('new_image.image');

        $this->assertDatabaseHas('model_numbers', [
            'id' => $modelNumber->id,
            'code' => 'ROLLBACK-OLD',
            'label' => 'Before',
        ]);
        $this->assertSame($primary->id, $model->fresh()->primary_model_number_id);
        $this->assertSame($oldPath, $image->fresh()->file_path);
        $this->assertSame('Original', $image->fresh()->caption);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles());
    }

    public function test_admin_cannot_submit_partial_existing_image_payload_via_main_save(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $firstImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $secondImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/second.jpg',
            'caption' => 'Second',
            'sort_order' => 1,
        ]);

        $response = $this->from(route('settings.model_numbers.edit', $modelNumber))
            ->put(route('settings.model_numbers.update', $modelNumber), [
                'code' => $modelNumber->code,
                'label' => $modelNumber->label,
                'status' => 'active',
                'existing_images' => [
                    $firstImage->id => [
                        'caption' => 'Only one submitted',
                        'delete' => 0,
                    ],
                ],
                'image_order' => [$firstImage->id],
            ]);

        $response
            ->assertRedirect(route('settings.model_numbers.edit', $modelNumber))
            ->assertSessionHasErrors('existing_images');

        $this->assertDatabaseHas('model_number_images', [
            'id' => $firstImage->id,
            'sort_order' => 0,
            'caption' => 'First',
        ]);
        $this->assertDatabaseHas('model_number_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
            'caption' => 'Second',
        ]);
    }
}
