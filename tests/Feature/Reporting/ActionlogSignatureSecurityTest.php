<?php

namespace Tests\Feature\Reporting;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActionlogSignatureSecurityTest extends TestCase
{
    public function testLocalSignatureMustBeBoundToAVisibleActionLogItem(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $asset = Asset::factory()->create();
        $viewer = User::factory()->viewAssets()->create();
        $filename = 'bound-signature.png';
        Actionlog::factory()->acceptedSignature()->create([
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'accept_signature' => $filename,
        ]);
        Storage::disk('local')->put('private_uploads/signatures/'.$filename, $this->png());

        $this->actingAs($viewer)
            ->get(route('log.signature.view', $filename))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Storage::disk('local')->put('private_uploads/signatures/unbound.png', $this->png());
        $this->actingAs($viewer)
            ->get(route('log.signature.view', 'unbound.png'))
            ->assertNotFound();
    }

    public function testS3BackedSignatureStillRequiresAuthorizationForTheBoundItem(): void
    {
        config(['filesystems.default' => 's3_private']);
        Storage::fake('s3_private');

        $asset = Asset::factory()->create();
        $filename = 's3-signature.png';
        Actionlog::factory()->acceptedSignature()->create([
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'accept_signature' => $filename,
        ]);
        Storage::disk('s3_private')->put('private_uploads/signatures/'.$filename, $this->png());

        $this->actingAs(User::factory()->create())
            ->get(route('log.signature.view', $filename))
            ->assertForbidden();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('log.signature.view', $filename))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(2, 2);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 220));

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
