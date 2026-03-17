<?php

namespace Tests\Feature\Assets\Api;

use App\Models\AssetModel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelNumberImagesApiTest extends TestCase
{
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
}
