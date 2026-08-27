<?php

namespace Tests\Feature\Assets\Api;

use App\Models\AssetModel;
use App\Models\ModelNumberImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class ModelNumberImagesApiTest extends TestCase
{
    use CreatesPolyglotRasterUploads;

    public function test_api_store_defaults_first_model_number_image_to_sort_order_zero(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->post(route('api.model-numbers.images.store', $modelNumber), [
                'caption' => 'Front',
                'image' => UploadedFile::fake()->image('front.jpg'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payload.sort_order', 0)
            ->assertJsonPath('payload.caption', 'Front');

        $path = $response->json('payload.file_path');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_api_store_reencodes_model_number_image_and_uses_server_derived_extension(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $trailingPayload = '<?php echo "model-number-api-payload";';

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->post(route('api.model-numbers.images.store', $modelNumber), [
                'caption' => 'Front',
                'image' => $this->makeJpegWithTrailingPayload('front.pht', $trailingPayload),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertCreated();

        $path = $response->json('payload.file_path');
        $this->assertMatchesRegularExpression(
            '#^model_numbers/'.$modelNumber->id.'/'.$modelNumber->id.'_[0-9a-f-]{36}\.jpg$#',
            $path
        );

        $stored = Storage::disk('public')->get($path);
        $this->assertStringNotContainsString($trailingPayload, $stored);
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
    }

    public function test_api_update_reencodes_replacement_before_removing_existing_image(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Old',
            'sort_order' => 0,
        ]);
        $trailingPayload = '<?php echo "model-number-update-payload";';

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->put(route('api.model-numbers.images.update', [$modelNumber, $image]), [
                'caption' => 'Updated',
                'image' => $this->makeJpegWithTrailingPayload('replacement.pht', $trailingPayload),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();

        $newPath = $image->fresh()->file_path;
        $this->assertStringEndsWith('.jpg', $newPath);
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $stored = Storage::disk('public')->get($newPath);
        $this->assertStringNotContainsString($trailingPayload, $stored);
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
    }

    public function test_api_update_preserves_existing_file_when_database_write_fails(): void
    {
        Storage::fake('public');

        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Old',
            'sort_order' => 0,
        ]);
        $eventName = 'eloquent.saving: '.ModelNumberImage::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated model-number image persistence failure.');
        });

        $exception = null;
        $this->withoutExceptionHandling();

        try {
            $this->actingAsForApi(User::factory()->superuser()->create())
                ->put(route('api.model-numbers.images.update', [$modelNumber, $image]), [
                    'caption' => 'Updated',
                    'image' => UploadedFile::fake()->image('replacement.jpg'),
                ], [
                    'Accept' => 'application/json',
                ]);
        } catch (RuntimeException $caught) {
            $exception = $caught;
        } finally {
            Event::forget($eventName);
        }

        $this->assertNotNull($exception);
        $this->assertSame($oldPath, $image->fresh()->file_path);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles());
    }
}
