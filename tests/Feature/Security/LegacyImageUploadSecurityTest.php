<?php

namespace Tests\Feature\Security;

use App\Http\Requests\ImageUploadRequest;
use App\Models\Accessory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class LegacyImageUploadSecurityTest extends TestCase
{
    use CreatesPolyglotRasterUploads;

    public function testLegacyImageFormsAdvertiseOnlyFormatsAcceptedByTheUploadRequest(): void
    {
        $basePath = realpath(__DIR__.'/../../..');
        $imageForm = file_get_contents(
            $basePath.'/resources/views/partials/forms/edit/image-upload.blade.php'
        );
        $logoForm = file_get_contents(
            $basePath.'/resources/views/partials/forms/edit/uploadLogo.blade.php'
        );

        foreach ([$imageForm, $logoForm] as $form) {
            $this->assertStringContainsString('image/gif,image/jpeg,image/png,image/svg,image/svg+xml', $form);
            $this->assertStringNotContainsString('image/webp', $form);
            $this->assertStringNotContainsString('image/avif', $form);
        }

        foreach (['en-US', 'nl-NL'] as $locale) {
            $this->app->setLocale($locale);

            $help = trans('general.image_filetypes_help', ['size' => '5 MB']);
            $this->assertStringNotContainsString('webp', strtolower($help));
            $this->assertStringNotContainsString('avif', strtolower($help));
        }
    }

    public function testLegacyImageHelperReencodesRasterAndDerivesItsExtensionServerSide(): void
    {
        Storage::fake('public');

        $payload = '<?php echo "legacy-image-payload";';
        $request = $this->requestWithImage(
            $this->makeJpegWithTrailingPayload('accessory.pht', $payload)
        );
        $accessory = Accessory::factory()->create();

        $request->handleImages($accessory);

        $this->assertMatchesRegularExpression('/\.jpg$/', $accessory->image);
        Storage::disk('public')->assertExists('accessories/'.$accessory->image);
        $stored = Storage::disk('public')->get('accessories/'.$accessory->image);
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
        $this->assertStringNotContainsString($payload, $stored);
    }

    public function testLegacyImageHelperRejectsUndecodableRasterBytes(): void
    {
        Storage::fake('public');

        $request = $this->requestWithImage(
            UploadedFile::fake()->createWithContent('broken.jpg', 'not an image')
        );
        $accessory = Accessory::factory()->create();

        try {
            $request->handleImages($accessory);
            $this->fail('The invalid raster should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNull($accessory->image);
    }

    private function requestWithImage(UploadedFile $file): ImageUploadRequest
    {
        $request = ImageUploadRequest::create('/', 'POST', [], [], ['image' => $file]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);

        return $request;
    }
}
