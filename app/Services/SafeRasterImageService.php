<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SafeRasterImageService
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const ABSOLUTE_MAX_PIXELS = 40_000_000;

    private const RESERVED_RUNTIME_MEMORY_BYTES = 64 * 1024 * 1024;

    /**
     * Allow for the decoded raster, a same-sized orientation copy, and GD
     * bookkeeping. The encoded input/output buffers are covered by the
     * separate runtime reserve and strict five-megabyte size limits.
     */
    private const PEAK_BYTES_PER_PIXEL = 12;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    public static function maxPixels(
        ?int $memoryLimitBytes = null,
        ?int $currentUsageBytes = null
    ): int
    {
        $memoryLimitBytes ??= self::memoryLimitBytes();
        $currentUsageBytes ??= memory_get_usage(true);

        if ($memoryLimitBytes < 0) {
            return self::ABSOLUTE_MAX_PIXELS;
        }

        $availableBytes = max(
            0,
            $memoryLimitBytes - max(0, $currentUsageBytes) - self::RESERVED_RUNTIME_MEMORY_BYTES
        );

        return min(
            self::ABSOLUTE_MAX_PIXELS,
            intdiv($availableBytes, self::PEAK_BYTES_PER_PIXEL)
        );
    }

    /**
     * Decode and re-encode a supported raster image before storing it.
     *
     * The returned extension and MIME type are derived from the decoded image,
     * never from client-controlled filename or Content-Type metadata.
     *
     * @return array{contents: string, extension: string, mime: string}
     */
    public function prepare(UploadedFile $file, string $field = 'image'): array
    {
        if (! $file->isValid()) {
            $this->fail($field, __('The image could not be uploaded.'));
        }

        $size = $file->getSize();
        if ($size === false || $size > self::MAX_BYTES) {
            $this->fail($field, __('The image may not be greater than 5 MB.'));
        }

        $path = $file->getRealPath();
        $contents = $path ? @file_get_contents($path) : false;
        if (! is_string($contents)) {
            $this->fail($field, __('The image could not be uploaded.'));
        }

        return $this->prepareContents($contents, $field);
    }

    /**
     * Decode and re-encode raster bytes read from trusted or legacy storage.
     *
     * @return array{contents: string, extension: string, mime: string}
     */
    public function prepareContents(string $contents, string $field = 'image'): array
    {
        if (strlen($contents) > self::MAX_BYTES) {
            $this->fail($field, __('The image may not be greater than 5 MB.'));
        }

        $imageInfo = @getimagesizefromstring($contents);

        if ($imageInfo === false || ! isset($imageInfo['mime'], self::MIME_EXTENSIONS[$imageInfo['mime']])) {
            $this->fail($field, __('The image must be a JPEG, PNG, or GIF raster image.'));
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width < 1 || $height < 1 || ($width * $height) > self::maxPixels()) {
            $this->fail($field, __('The image dimensions are invalid or too large.'));
        }

        $mime = $imageInfo['mime'];
        $orientation = $mime === 'image/jpeg'
            ? $this->jpegExifOrientation($contents)
            : 1;
        $safeContents = $this->reencode($contents, $mime, $field, $orientation);

        if (strlen($safeContents) > self::MAX_BYTES) {
            $this->fail($field, __('The encoded image may not be greater than 5 MB.'));
        }

        $safeImageInfo = @getimagesizefromstring($safeContents);

        if ($safeImageInfo === false || ($safeImageInfo['mime'] ?? null) !== $mime) {
            $this->fail($field, __('The image could not be encoded safely.'));
        }

        return [
            'contents' => $safeContents,
            'extension' => self::MIME_EXTENSIONS[$mime],
            'mime' => $mime,
        ];
    }

    /**
     * @return array{path: string, extension: string, mime: string}
     */
    public function storePublic(
        UploadedFile $file,
        string $directory,
        string $filenamePrefix,
        string $field = 'image'
    ): array {
        $prepared = $this->prepare($file, $field);
        $filename = $filenamePrefix.Str::uuid().'.'.$prepared['extension'];
        $path = trim($directory, '/\\').'/'.$filename;

        if (! Storage::disk('public')->put($path, $prepared['contents'])) {
            throw new RuntimeException('Unable to store normalized raster image.');
        }

        return [
            'path' => $path,
            'extension' => $prepared['extension'],
            'mime' => $prepared['mime'],
        ];
    }

    protected function reencode(
        string $contents,
        string $mime,
        string $field,
        int $orientation = 1
    ): string
    {
        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            $this->fail($field, __('The image could not be decoded safely.'));
        }

        $image = $this->applyExifOrientation($image, $orientation, $field);

        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $output = tmpfile();
        if ($output === false) {
            imagedestroy($image);
            $this->fail($field, __('The image could not be encoded safely.'));
        }

        try {
            $encoded = match ($mime) {
                'image/jpeg' => imagejpeg($image, $output, 90),
                'image/png' => imagepng($image, $output, 6),
                'image/gif' => imagegif($image, $output),
                default => false,
            };
            $outputStats = fstat($output);

            if (! $encoded || ! is_array($outputStats) || ($outputStats['size'] ?? 0) < 1) {
                $this->fail($field, __('The image could not be encoded safely.'));
            }

            if ($outputStats['size'] > self::MAX_BYTES) {
                $this->fail($field, __('The encoded image may not be greater than 5 MB.'));
            }

            rewind($output);
            $safeContents = stream_get_contents($output, self::MAX_BYTES + 1);
        } finally {
            fclose($output);
            imagedestroy($image);
        }

        if (! is_string($safeContents)
            || $safeContents === ''
            || strlen($safeContents) > self::MAX_BYTES
        ) {
            $this->fail($field, __('The image could not be encoded safely.'));
        }

        return $safeContents;
    }

    private function applyExifOrientation(\GdImage $image, int $orientation, string $field): \GdImage
    {
        if ($orientation === 2 && ! imageflip($image, IMG_FLIP_HORIZONTAL)) {
            imagedestroy($image);
            $this->fail($field, __('The image orientation could not be normalized safely.'));
        }

        if ($orientation === 4 && ! imageflip($image, IMG_FLIP_VERTICAL)) {
            imagedestroy($image);
            $this->fail($field, __('The image orientation could not be normalized safely.'));
        }

        if (in_array($orientation, [5, 7], true)
            && ! imageflip($image, IMG_FLIP_HORIZONTAL)
        ) {
            imagedestroy($image);
            $this->fail($field, __('The image orientation could not be normalized safely.'));
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        if ($rotated === false) {
            $this->fail($field, __('The image orientation could not be normalized safely.'));
        }

        return $rotated;
    }

    private function jpegExifOrientation(string $contents): int
    {
        if (! str_starts_with($contents, "\xFF\xD8")) {
            return 1;
        }

        $length = strlen($contents);
        $offset = 2;

        while ($offset + 4 <= $length) {
            if (ord($contents[$offset]) !== 0xFF) {
                return 1;
            }

            while ($offset < $length && ord($contents[$offset]) === 0xFF) {
                $offset++;
            }

            if ($offset >= $length) {
                return 1;
            }

            $marker = ord($contents[$offset]);
            $offset++;

            if ($marker === 0xD9 || $marker === 0xDA) {
                return 1;
            }

            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                continue;
            }

            $segmentLength = $this->readUnsignedShort($contents, $offset, false);
            if ($segmentLength === null
                || $segmentLength < 2
                || $offset + $segmentLength > $length
            ) {
                return 1;
            }

            if ($marker === 0xE1) {
                $segment = substr($contents, $offset + 2, $segmentLength - 2);
                if (str_starts_with($segment, "Exif\x00\x00")) {
                    return $this->tiffOrientation(substr($segment, 6));
                }
            }

            $offset += $segmentLength;
        }

        return 1;
    }

    private function tiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $byteOrder = substr($tiff, 0, 2);
        if (! in_array($byteOrder, ['II', 'MM'], true)) {
            return 1;
        }

        $littleEndian = $byteOrder === 'II';
        if ($this->readUnsignedShort($tiff, 2, $littleEndian) !== 42) {
            return 1;
        }

        $ifdOffset = $this->readUnsignedLong($tiff, 4, $littleEndian);
        if ($ifdOffset === null) {
            return 1;
        }

        $entryCount = $this->readUnsignedShort($tiff, $ifdOffset, $littleEndian);
        if ($entryCount === null) {
            return 1;
        }

        for ($index = 0; $index < $entryCount; $index++) {
            $entryOffset = $ifdOffset + 2 + ($index * 12);
            if ($entryOffset + 12 > strlen($tiff)) {
                return 1;
            }

            $tag = $this->readUnsignedShort($tiff, $entryOffset, $littleEndian);
            if ($tag !== 0x0112) {
                continue;
            }

            $type = $this->readUnsignedShort($tiff, $entryOffset + 2, $littleEndian);
            $count = $this->readUnsignedLong($tiff, $entryOffset + 4, $littleEndian);
            if ($type !== 3 || $count === null || $count < 1) {
                return 1;
            }

            $valueOffset = $count === 1
                ? $entryOffset + 8
                : $this->readUnsignedLong($tiff, $entryOffset + 8, $littleEndian);
            if ($valueOffset === null) {
                return 1;
            }

            $orientation = $this->readUnsignedShort($tiff, $valueOffset, $littleEndian);

            return in_array($orientation, range(1, 8), true) ? $orientation : 1;
        }

        return 1;
    }

    private function readUnsignedShort(string $contents, int $offset, bool $littleEndian): ?int
    {
        if ($offset < 0 || $offset + 2 > strlen($contents)) {
            return null;
        }

        $value = unpack($littleEndian ? 'vvalue' : 'nvalue', substr($contents, $offset, 2));

        return isset($value['value']) ? (int) $value['value'] : null;
    }

    private function readUnsignedLong(string $contents, int $offset, bool $littleEndian): ?int
    {
        if ($offset < 0 || $offset + 4 > strlen($contents)) {
            return null;
        }

        $value = unpack($littleEndian ? 'Vvalue' : 'Nvalue', substr($contents, $offset, 4));

        return isset($value['value']) ? (int) $value['value'] : null;
    }

    private static function memoryLimitBytes(): int
    {
        $configuredLimit = trim((string) ini_get('memory_limit'));
        if ($configuredLimit === '' || $configuredLimit === '-1') {
            return -1;
        }

        if (! preg_match('/^(\d+)\s*([KMG]?)$/i', $configuredLimit, $matches)) {
            return 128 * 1024 * 1024;
        }

        $bytes = (int) $matches[1];
        $multiplier = match (strtoupper($matches[2])) {
            'G' => 1024 * 1024 * 1024,
            'M' => 1024 * 1024,
            'K' => 1024,
            default => 1,
        };

        return $bytes * $multiplier;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
