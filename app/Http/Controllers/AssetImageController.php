<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetImage;
use App\Services\SafeRasterImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AssetImageController extends Controller
{
    public function __construct(
        private SafeRasterImageService $rasterImages,
    ) {
    }

    public function store(Request $request, Asset $asset): Response|JsonResponse
    {
        $this->authorize('update', $asset);
        $this->authorize('uploadImages', $asset);

        $request->validate([
            'image' => ['required', 'array'],
            'image.*' => ['image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['required', 'array'],
            'caption.*' => ['required', 'string', 'max:255'],
        ]);

        if (count($request->file('image')) !== count($request->input('caption'))) {
            throw ValidationException::withMessages([
                'caption' => trans('general.caption_required'),
            ]);
        }

        $stored = [];
        $paths = [];
        $firstRelativePath = null;

        DB::beginTransaction();

        try {
            Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if ($asset->images()->count() + count($request->file('image')) > 30) {
                throw ValidationException::withMessages([
                    'image' => trans('general.too_many_asset_images'),
                ]);
            }

            $nextSortOrder = (int) $asset->images()->max('sort_order');

            foreach ($request->file('image') as $index => $file) {
                $storedImage = $this->rasterImages->storePublic(
                    $file,
                    'assets/'.$asset->id,
                    $asset->id.'_',
                    'image.'.$index
                );
                $path = $storedImage['path'];
                $paths[] = $path;

                $image = $asset->images()->create([
                    'file_path' => $path,
                    'caption' => $request->input('caption')[$index],
                    'sort_order' => $nextSortOrder + $index + 1,
                    'source' => 'asset_upload',
                ]);

                if ($firstRelativePath === null) {
                    $firstRelativePath = Str::after($path, 'assets/');
                }

                $stored[] = [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($path),
                ];
            }

            if (!empty($stored)) {
                if (!$asset->image) {
                    $asset->image = $firstRelativePath;
                }
                $asset->image_override_enabled = true;
                if (! $asset->save()) {
                    throw new \RuntimeException('Unable to persist the asset image pointer.');
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['images' => $stored], 201);
            }

            return redirect()
                ->route('hardware.show', $asset)
                ->with('success', trans('general.file_upload_success'));
        } catch (ValidationException $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                if (! Storage::disk('public')->delete($path)) {
                    Log::critical('Unable to remove a rejected normalized asset image.', [
                        'asset_id' => $asset->getKey(),
                        'path' => $path,
                    ]);
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                if (! Storage::disk('public')->delete($path)) {
                    Log::critical('Unable to remove an asset image after persistence failed.', [
                        'asset_id' => $asset->getKey(),
                        'path' => $path,
                    ]);
                }
            }

            Log::error('Asset image upload failed and was rolled back.', [
                'asset_id' => $asset->getKey(),
                'exception' => $e,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => trans('general.image_upload_failed')], 500);
            }

            return redirect()
                ->route('hardware.show', $asset)
                ->with('error', trans('general.image_upload_failed'));
        }
    }

    public function update(Request $request, Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        $this->authorize('update', $asset);
        $this->authorize('uploadImages', $asset);

        $request->validate([
            'caption' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'make_cover' => ['nullable', 'boolean'],
            'image_override_enabled' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($asset, $assetImage, $request): void {
            $assetImage->caption = $request->input('caption');

            if ($request->filled('sort_order')) {
                $assetImage->sort_order = (int) $request->input('sort_order');
            }

            if ($request->boolean('make_cover')) {
                $asset->images()->where('id', '!=', $assetImage->id)->increment('sort_order');
                $assetImage->sort_order = 0;

                $asset->image = Str::after($assetImage->file_path, 'assets/');
                $asset->image_override_enabled = true;

            }

            if (! $assetImage->save()) {
                throw new \RuntimeException('Unable to persist the asset image metadata.');
            }

            if ($request->has('image_override_enabled')) {
                $asset->image_override_enabled = $request->boolean('image_override_enabled');
            }

            if ($asset->isDirty() && ! $asset->save()) {
                throw new \RuntimeException('Unable to persist the asset image settings.');
            }
        });

        return back()->with('success', trans('general.image_caption_updated'));
    }


    public function destroy(Request $request, Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        $this->authorize('update', $asset);
        $this->authorize('manageImages', $asset);

        $relative = Str::after($assetImage->file_path, 'assets/');
        $disk = Storage::disk('public');
        $contents = null;

        try {
            if ($disk->exists($assetImage->file_path)) {
                $contents = $disk->get($assetImage->file_path);

                if (! $disk->delete($assetImage->file_path)) {
                    throw new \RuntimeException('Unable to delete the asset image from storage.');
                }
            }

            DB::transaction(function () use ($asset, $assetImage, $relative) {
                if (! $assetImage->delete()) {
                    throw new \RuntimeException('Unable to delete the asset image record.');
                }

                if ($asset->image === $relative || $asset->images()->count() === 0) {
                    $asset->syncImageOverridePointers();
                }
            });

            return back()->with('success', trans('general.image_deleted'));
        } catch (\Throwable $exception) {
            if (is_string($contents) && ! $disk->exists($assetImage->file_path)) {
                if (! $disk->put($assetImage->file_path, $contents)) {
                    Log::critical('Unable to restore an asset image after database rollback.', [
                        'asset_id' => $asset->getKey(),
                        'asset_image_id' => $assetImage->getKey(),
                        'path' => $assetImage->file_path,
                    ]);
                }
            }

            Log::error('Asset image deletion failed and was rolled back.', [
                'asset_id' => $asset->getKey(),
                'asset_image_id' => $assetImage->getKey(),
                'exception' => $exception,
            ]);

            return back()->with('error', trans('general.image_delete_failed'));
        }
    }
}
