<?php

namespace Tests\Unit\Services;

use App\Services\SafeRasterImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesPolyglotRasterUploads;
use Tests\TestCase;

class SafeRasterImageServiceTest extends TestCase
{
    use CreatesPolyglotRasterUploads;

    public function test_orientation_six_is_rotated_before_reencoding_without_exif_dependency(): void
    {
        $prepared = app(SafeRasterImageService::class)->prepare(
            $this->makeJpegWithExifOrientation('portrait.jpg', 6, 40, 20)
        );

        $imageInfo = getimagesizefromstring($prepared['contents']);

        $this->assertIsArray($imageInfo);
        $this->assertSame(20, $imageInfo[0]);
        $this->assertSame(40, $imageInfo[1]);
        $this->assertSame('image/jpeg', $imageInfo['mime']);
        $this->assertSame('jpg', $prepared['extension']);
        $this->assertStringNotContainsString("Exif\x00\x00", $prepared['contents']);
        $this->assertLessThanOrEqual(SafeRasterImageService::MAX_BYTES, strlen($prepared['contents']));
    }

    public function test_dimensions_over_the_current_memory_budget_are_rejected_before_decode(): void
    {
        $width = 3000;
        $height = intdiv(SafeRasterImageService::maxPixels(), $width) + 1;
        $file = $this->makePngHeaderWithDimensions('oversized-dimensions.png', $width, $height);

        $this->assertValidationFailure(
            fn () => app(SafeRasterImageService::class)->prepare($file),
            'image'
        );
    }

    public function test_pixel_budget_accounts_for_runtime_usage_and_reserve(): void
    {
        $memoryLimit = 128 * 1024 * 1024;
        $runtimeUsage = 16 * 1024 * 1024;

        $this->assertSame(
            intdiv(48 * 1024 * 1024, 12),
            SafeRasterImageService::maxPixels($memoryLimit, $runtimeUsage)
        );
    }

    public function test_512mb_budget_accepts_normal_phone_photo_dimensions(): void
    {
        $pixelBudget = SafeRasterImageService::maxPixels(
            512 * 1024 * 1024,
            64 * 1024 * 1024
        );

        $this->assertGreaterThanOrEqual(12_000_000, $pixelBudget);
        $this->assertLessThanOrEqual(40_000_000, $pixelBudget);
    }

    public function test_pixel_budget_is_capped_at_40_megapixels(): void
    {
        $this->assertSame(
            40_000_000,
            SafeRasterImageService::maxPixels(2 * 1024 * 1024 * 1024, 0)
        );
        $this->assertSame(40_000_000, SafeRasterImageService::maxPixels(-1, 0));
    }

    public function test_encoded_output_larger_than_upload_limit_is_rejected(): void
    {
        $service = new class extends SafeRasterImageService {
            protected function reencode(
                string $contents,
                string $mime,
                string $field,
                int $orientation = 1
            ): string {
                return str_repeat('x', self::MAX_BYTES + 1);
            }
        };

        $this->assertValidationFailure(
            fn () => $service->prepare(UploadedFile::fake()->image('image.jpg')),
            'image'
        );
    }

    private function assertValidationFailure(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('Expected raster validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
