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
                'image' => UploadedFile::fake()->image("photo{$i}.jpg"),
            ]);
            $response->assertStatus(302);
        }

        $this->assertEquals(30, $asset->images()->count());

        $response = $this->actingAs($user)->post(route('asset-images.store', $asset), [
            'image' => UploadedFile::fake()->image('too_many.jpg'),
        ]);
        $response->assertSessionHasErrors('image');
    }
}
