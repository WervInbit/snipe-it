<?php

namespace Tests\Feature\UploadedFiles;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedFileResponseHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
    }

    public function test_ui_allows_uppercase_jpeg_inline(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $log = $this->storeAttachment($user, $asset, 'PHOTO.JPEG', 'jpeg bytes');

        $response = $this->actingAs($user)->get(
            route('ui.files.show', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]).'?inline=true'
        );

        $response->assertOk();
        $this->assertStringStartsWith(
            'inline',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_api_forces_non_media_inline_request_to_download(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $log = $this->storeAttachment($user, $asset, 'payload.XML', '<root>value</root>');

        $response = $this->actingAsForApi($user)->get(
            route('api.files.show', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]).'?inline=true'
        );

        $response->assertOk();
        $this->assertStringStartsWith(
            'attachment',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_ui_renders_legacy_svg_as_plain_text_instead_of_image(): void
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->superuser()->create();
        $log = $this->storeAttachment(
            $user,
            $asset,
            'diagram.SVG',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $response = $this->actingAs($user)->get(
            route('ui.files.show', [
                'object_type' => 'assets',
                'id' => $asset->id,
                'file_id' => $log->id,
            ]).'?inline=true'
        );

        $response->assertOk();
        $this->assertStringStartsWith(
            'inline',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith(
            'text/plain',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    private function storeAttachment(
        User $user,
        Asset $asset,
        string $filename,
        string $contents
    ): Actionlog {
        $this->actingAs($user);
        $log = $asset->logUpload($filename, 'Header regression');
        Storage::put($log->uploads_file_path(), $contents);

        return $log;
    }
}
