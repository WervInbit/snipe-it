<?php

namespace App\Services;

use App\Models\TestResultPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkflowEvidencePhotoService
{
    public const MAX_BYTES = SafeRasterImageService::MAX_BYTES;

    public const MAX_PHOTOS_PER_RESULT = 20;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
    ];

    public function __construct(
        private SafeRasterImageService $rasterImages,
    ) {
    }

    /**
     * Validate and normalize an uploaded raster image before any state is changed.
     *
     * @return array{contents: string, extension: string, mime: string}
     */
    public function prepare(UploadedFile $file, string $field = 'photo'): array
    {
        $prepared = $this->rasterImages->prepare($file, $field);
        $actualMime = $prepared['mime'];
        $serverMime = $file->getMimeType();
        $clientMime = strtolower((string) $file->getClientMimeType());
        $clientExtension = strtolower($file->getClientOriginalExtension());

        if ($serverMime !== $actualMime || $clientMime !== $actualMime) {
            $this->fail($field, __('The photo MIME type does not match its decoded image content.'));
        }

        if (! in_array($clientExtension, self::MIME_EXTENSIONS[$actualMime], true)) {
            $this->fail($field, __('The photo filename extension does not match its decoded image content.'));
        }

        return $prepared;
    }

    /**
     * @param array{contents: string, extension: string, mime: string} $prepared
     */
    public function storePrepared(array $prepared, int $resultId): string
    {
        $path = 'private_uploads/workflow_evidence/results/'.$resultId.'/'.Str::uuid().'.'.$prepared['extension'];

        if (! Storage::put($path, $prepared['contents'])) {
            throw new \RuntimeException('Unable to store workflow evidence photo.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if ($this->isPrivatePath($path)) {
            Storage::delete($path);

            return;
        }

        if ($this->isLegacyPublicPath($path)) {
            File::delete(public_path($path));
        }
    }

    /**
     * Decode and re-encode stored evidence before serving or promoting it.
     *
     * This also makes legacy public evidence safe to consume without trusting its
     * old filename or any bytes appended after the raster payload.
     *
     * @return array{contents: string, extension: string, mime: string}
     */
    public function readSafe(TestResultPhoto $photo): array
    {
        $path = (string) $photo->path;

        if ($this->isPrivatePath($path)) {
            abort_unless(Storage::exists($path), 404);
            $contents = Storage::get($path);
        } elseif ($this->isLegacyPublicPath($path)) {
            $absolutePath = public_path($path);
            abort_unless(File::isFile($absolutePath), 404);
            $contents = File::get($absolutePath);
        } else {
            abort(404);
        }

        try {
            return $this->rasterImages->prepareContents($contents, 'photo');
        } catch (ValidationException) {
            abort(404);
        }
    }

    private function isPrivatePath(string $path): bool
    {
        return Str::startsWith($path, 'private_uploads/workflow_evidence/')
            && ! Str::contains($path, ['..', '\\']);
    }

    private function isLegacyPublicPath(string $path): bool
    {
        return Str::startsWith($path, 'uploads/test_images/')
            && ! Str::contains($path, ['..', '\\']);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
