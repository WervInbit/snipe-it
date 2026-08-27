<?php

namespace Tests\Feature\Reporting;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActionlogStoredEulaSecurityTest extends TestCase
{
    public function testStoredEulaMustBeBoundToAVisibleAcceptedActionLogItem(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $asset = Asset::factory()->create();
        $filename = 'accepted-eula.pdf';
        Actionlog::factory()->create([
            'action_type' => 'accepted',
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'filename' => $filename,
        ]);
        Storage::disk('local')->put('private_uploads/eula-pdfs/' . $filename, '%PDF-1.4');

        $this->actingAs(User::factory()->create())
            ->get(route('log.storedeula.download', $filename))
            ->assertForbidden();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('log.storedeula.download', $filename))
            ->assertOk()
            ->assertDownload($filename)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Storage::disk('local')->put('private_uploads/eula-pdfs/unbound.pdf', '%PDF-1.4');

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('log.storedeula.download', 'unbound.pdf'))
            ->assertNotFound();
    }

    public function testS3BackedStoredEulaUsesTheSameAuthorizationAndDownloadPath(): void
    {
        config(['filesystems.default' => 's3_private']);
        Storage::fake('s3_private');

        $asset = Asset::factory()->create();
        $filename = 's3-accepted-eula.pdf';
        Actionlog::factory()->create([
            'action_type' => 'accepted',
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'filename' => $filename,
        ]);
        Storage::disk('s3_private')->put(
            'private_uploads/eula-pdfs/' . $filename,
            '%PDF-1.4'
        );

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('log.storedeula.download', $filename))
            ->assertOk()
            ->assertDownload($filename);
    }
}
