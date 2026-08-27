<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

trait CreatesPolyglotRasterUploads
{
    /**
     * Keep fake-image temp streams alive for the duration of each test.
     *
     * @var array<int, UploadedFile>
     */
    private array $polyglotRasterSources = [];

    protected function makeJpegWithTrailingPayload(
        string $clientFilename,
        string $trailingPayload
    ): UploadedFile {
        $source = UploadedFile::fake()->image('source.jpg', 32, 32);
        File::append($source->getRealPath(), $trailingPayload);
        $this->polyglotRasterSources[] = $source;

        return new UploadedFile(
            $source->getRealPath(),
            $clientFilename,
            'image/jpeg',
            null,
            true
        );
    }

    protected function makeJpegWithExifOrientation(
        string $clientFilename,
        int $orientation,
        int $width = 40,
        int $height = 20
    ): UploadedFile {
        $source = UploadedFile::fake()->image('source.jpg', $width, $height);
        $jpeg = File::get($source->getRealPath());
        $tiff = 'II'
            .pack('v', 42)
            .pack('V', 8)
            .pack('v', 1)
            .pack('v', 0x0112)
            .pack('v', 3)
            .pack('V', 1)
            .pack('v', $orientation)
            .pack('v', 0)
            .pack('V', 0);
        $exif = "Exif\x00\x00".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        File::put(
            $source->getRealPath(),
            substr($jpeg, 0, 2).$app1.substr($jpeg, 2)
        );
        $this->polyglotRasterSources[] = $source;

        return new UploadedFile(
            $source->getRealPath(),
            $clientFilename,
            'image/jpeg',
            null,
            true
        );
    }

    protected function makePngHeaderWithDimensions(
        string $clientFilename,
        int $width,
        int $height
    ): UploadedFile {
        $ihdr = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1A\n"
            .$this->pngChunk('IHDR', $ihdr)
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()
            ->createWithContent($clientFilename, $png)
            ->mimeType('image/png');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }
}
