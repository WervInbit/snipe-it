<?php

namespace Tests\Feature\Account;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileStoredEulaTest extends TestCase
{
    public function testUserCanDownloadOnlyTheirOwnAcceptedStoredEula(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $owner = User::factory()->create();
        $filename = 'owner-eula.pdf';
        Actionlog::factory()->create([
            'action_type' => 'accepted',
            'item_type' => Asset::class,
            'item_id' => Asset::factory()->create()->id,
            'target_type' => User::class,
            'target_id' => $owner->id,
            'filename' => $filename,
        ]);
        Storage::disk('local')->put('private_uploads/eula-pdfs/' . $filename, '%PDF-1.4');

        $this->actingAs($owner)
            ->get(route('profile.storedeula.download', $filename))
            ->assertOk()
            ->assertDownload($filename)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs(User::factory()->create())
            ->get(route('profile.storedeula.download', $filename))
            ->assertRedirect(route('account'));
    }

    public function testS3BackedProfileEulaIsStreamedThroughTheAuthorizedRoute(): void
    {
        config(['filesystems.default' => 's3_private']);
        Storage::fake('s3_private');

        $owner = User::factory()->create();
        $filename = 'owner-s3-eula.pdf';
        Actionlog::factory()->create([
            'action_type' => 'accepted',
            'item_type' => Asset::class,
            'item_id' => Asset::factory()->create()->id,
            'target_type' => User::class,
            'target_id' => $owner->id,
            'filename' => $filename,
        ]);
        Storage::disk('s3_private')->put('private_uploads/eula-pdfs/' . $filename, '%PDF-1.4');

        $this->actingAs($owner)
            ->get(route('profile.storedeula.download', $filename))
            ->assertOk()
            ->assertDownload($filename);
    }
}
