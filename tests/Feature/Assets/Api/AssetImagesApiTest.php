<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\ModelNumberImage;
use App\Models\User;
use Tests\TestCase;

class AssetImagesApiTest extends TestCase
{
    public function test_asset_images_api_returns_model_number_defaults_when_override_disabled(): void
    {
        $asset = Asset::factory()->create(['image_override_enabled' => false]);
        $modelNumberId = $asset->model_number_id;

        ModelNumberImage::create([
            'model_number_id' => $modelNumberId,
            'file_path' => 'model_numbers/'.$modelNumberId.'/back.jpg',
            'caption' => 'Back',
            'sort_order' => 2,
        ]);

        ModelNumberImage::create([
            'model_number_id' => $modelNumberId,
            'file_path' => 'model_numbers/'.$modelNumberId.'/front.jpg',
            'caption' => 'Front',
            'sort_order' => 1,
        ]);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.images', $asset));

        $response
            ->assertOk()
            ->assertJsonPath('payload.source', 'model_number_default')
            ->assertJsonPath('payload.images.0.caption', 'Front')
            ->assertJsonPath('payload.images.1.caption', 'Back');
    }

    public function test_asset_images_api_returns_asset_overrides_when_enabled(): void
    {
        $asset = Asset::factory()->create([
            'image_override_enabled' => true,
            'image' => null,
        ]);

        $asset->images()->create([
            'file_path' => 'assets/'.$asset->id.'/second.jpg',
            'caption' => 'Second',
            'sort_order' => 5,
            'source' => 'asset_upload',
        ]);

        $asset->images()->create([
            'file_path' => 'assets/'.$asset->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
            'source' => 'asset_upload',
        ]);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.images', $asset));

        $response
            ->assertOk()
            ->assertJsonPath('payload.source', 'asset_override')
            ->assertJsonPath('payload.images.0.caption', 'First')
            ->assertJsonPath('payload.images.1.caption', 'Second');
    }
}
