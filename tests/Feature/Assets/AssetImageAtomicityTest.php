<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AssetImageAtomicityTest extends TestCase
{
    public function testDeleteRestoresTheFileRecordAndCoverPointerWhenDatabaseDeleteFails(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $image = $asset->images()->create([
            'file_path' => 'assets/'.$asset->id.'/front.jpg',
            'caption' => 'Front',
            'sort_order' => 0,
            'source' => 'asset_upload',
        ]);
        $asset->forceFill([
            'image' => Str::after($image->file_path, 'assets/'),
            'image_override_enabled' => true,
        ])->save();
        Storage::disk('public')->put($image->file_path, 'original image bytes');

        $user = User::factory()->superuser()->create();
        $eventName = 'eloquent.deleting: '.AssetImage::class;
        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated asset-image delete failure.');
        });

        try {
            $this->actingAs($user)
                ->from(route('hardware.show', $asset))
                ->delete(route('asset-images.destroy', [$asset, $image]))
                ->assertRedirect()
                ->assertSessionHas('error', trans('general.image_delete_failed'))
                ->assertSessionMissing('success');
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseHas('asset_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->file_path);
        $this->assertSame('original image bytes', Storage::disk('public')->get($image->file_path));
        $this->assertSame(Str::after($image->file_path, 'assets/'), $asset->fresh()->image);
    }
}
