<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisplayAssetImagesTest extends TestCase
{
    public function test_asset_images_display_with_captions(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('front.jpg')],
            'caption' => ['Front'],
        ])->assertStatus(201);

        $asset->refresh();
        $storedPath = $asset->images()->first()->file_path;

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertSee('Front')
            ->assertSee('/storage/' . $storedPath, false);
    }

    public function test_asset_image_placeholder_when_empty(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertSee(trans('general.no_asset_images'));
    }

    public function test_refurbisher_cannot_see_delete_button(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $supervisor = User::factory()->supervisor()->editAssets()->create();

        $this->actingAs($supervisor)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('front.jpg')],
            'caption' => ['Front'],
        ])->assertStatus(201);

        $image = $asset->images()->first();

        $refurbisher = User::factory()->refurbisher()->editAssets()->create();
        $this->actingAs($refurbisher)
            ->get(route('hardware.show', $asset))
            ->assertSee('name="caption"', false)
            ->assertDontSee(route('asset-images.destroy', [$asset, $image]));
    }
}
