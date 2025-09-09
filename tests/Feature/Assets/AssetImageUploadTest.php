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
            $response->assertStatus(201);
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
        $response->assertStatus(201)->assertJsonCount(2, 'images');
        $this->assertEquals(2, $asset->images()->count());
    }

    public function test_asset_image_upload_rejects_non_images(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('doc1.bmp')],
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

    public function test_asset_image_caption_can_be_updated(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('photo1.jpg')],
            'caption' => ['old'],
        ]);

        $image = $asset->images()->first();

        $response = $this->actingAs($user)->put(route('asset-images.update', [$asset, $image]), [
            'caption' => 'new',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asset_images', [
            'id' => $image->id,
            'caption' => 'new',
        ]);
    }

    public function test_asset_image_can_be_deleted_and_replaced(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('photo1.jpg')],
            'caption' => ['front'],
        ]);

        $image = $asset->images()->first();
        Storage::disk('public')->assertExists($image->file_path);

        $response = $this->actingAs($user)->delete(route('asset-images.destroy', [$asset, $image]));
        $response->assertRedirect();

        Storage::disk('public')->assertMissing($image->file_path);
        $this->assertEquals(0, $asset->images()->count());

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => [UploadedFile::fake()->image('photo2.jpg')],
            'caption' => ['back'],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, $asset->images()->count());
    }
}
