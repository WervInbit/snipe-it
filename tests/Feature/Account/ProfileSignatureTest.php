<?php

namespace Tests\Feature\Account;

use App\Models\Actionlog;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSignatureTest extends TestCase
{
    public function testUserCanViewOnlyTheirOwnStoredSignature(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $owner = User::factory()->create();
        $filename = 'siglog-owner.png';
        Actionlog::factory()->acceptedSignature()->create([
            'target_id' => $owner->id,
            'accept_signature' => $filename,
        ]);
        Storage::disk('local')->put('private_uploads/signatures/'.$filename, $this->png());

        $response = $this->actingAs($owner)->get(route('profile.signature.view', $filename));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertNotFalse(getimagesizefromstring($response->getContent()));

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('profile.signature.view', $filename))
            ->assertRedirect(route('account'));
    }

    public function testInvalidStoredSignatureIsNotServed(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $owner = User::factory()->create();
        $filename = 'siglog-invalid.png';
        Actionlog::factory()->acceptedSignature()->create([
            'target_id' => $owner->id,
            'accept_signature' => $filename,
        ]);
        Storage::disk('local')->put(
            'private_uploads/signatures/'.$filename,
            '<script>alert("not an image")</script>'
        );

        $this->actingAs($owner)
            ->get(route('profile.signature.view', $filename))
            ->assertNotFound();
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
