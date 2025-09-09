<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetImageUploadTest extends TestCase
{
    public function test_asset_image_upload_limit_enforced(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
                'image' => [UploadedFile::fake()->image("photo{$i}.jpg")],
                'caption' => ["caption {$i}"],
            ]);
            $response->assertStatus(302);
        }

        $this->assertEquals(30, $asset->images()->count());

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('too_many.jpg')],
            'caption' => ['overflow'],
        ]);
        $response->assertSessionHasErrors('image');
    }

    public function test_asset_image_upload_requires_caption_and_allows_multiple(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('photo1.jpg')],
        ]);
        $response->assertSessionHasErrors('caption');

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [
                UploadedFile::fake()->image('photo1.jpg'),
                UploadedFile::fake()->image('photo2.jpg'),
            ],
            'caption' => ['front', 'back'],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals(2, $asset->images()->count());
    }

    public function test_asset_image_upload_rejects_non_images(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->create('doc1.pdf', 10, 'application/pdf')],
            'caption' => ['bad'],
        ]);

        $response->assertSessionHasErrors('image.0');
        $this->assertEquals(0, $asset->images()->count());
    }

    public function test_asset_image_upload_rejects_large_images(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('big.jpg')->size(6000)],
            'caption' => ['big'],
        ]);

        $response->assertSessionHasErrors('image.0');
        $this->assertEquals(0, $asset->images()->count());
    }
}
