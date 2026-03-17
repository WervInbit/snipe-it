<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AssetImageController extends Controller
{
    /**
     * Determine if the given user is allowed to upload images.
     */
    private function canUpload(User $user): bool
    {
        return $user->hasAccess('superuser') ||
            $user->hasAccess('admin') ||
            $user->hasAccess('supervisor') ||
            $user->hasAccess('senior-refurbisher') ||
            $user->hasAccess('refurbisher');
    }

    /**
     * Determine if the given user is allowed to delete images.
     */
    private function canDelete(User $user): bool
    {
        return $user->hasAccess('superuser') ||
            $user->hasAccess('admin') ||
            $user->hasAccess('supervisor') ||
            $user->hasAccess('senior-refurbisher');
    }

    public function store(Request $request, Asset $asset): Response|JsonResponse
    {
        $this->authorize('update', $asset);

        $user = $request->user();
        abort_unless($this->canUpload($user), 403);

        $request->validate([
            'image' => ['required', 'array'],
            'image.*' => ['image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['required', 'array'],
            'caption.*' => ['required', 'string'],
        ]);

        if (count($request->file('image')) !== count($request->input('caption'))) {
            throw ValidationException::withMessages([
                'caption' => trans('general.caption_required'),
            ]);
        }

        if ($asset->images()->count() + count($request->file('image')) > 30) {
            throw ValidationException::withMessages([
                'image' => trans('general.too_many_asset_images'),
            ]);
        }

        $stored = [];
        $paths = [];
        $firstRelativePath = null;
        $nextSortOrder = (int) $asset->images()->max('sort_order');

        DB::beginTransaction();

        try {
            foreach ($request->file('image') as $index => $file) {
                $filename = $asset->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('assets/'.$asset->id, $filename, 'public');

                $image = $asset->images()->create([
                    'file_path' => $path,
                    'caption' => $request->input('caption')[$index],
                    'sort_order' => $nextSortOrder + $index + 1,
                    'source' => 'asset_upload',
                ]);

                if ($firstRelativePath === null) {
                    $firstRelativePath = Str::after($path, 'assets/');
                }

                $paths[] = $path;

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
                $asset->save();
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['images' => $stored], 201);
            }

            return redirect()
                ->route('hardware.show', $asset)
                ->with('success', trans('general.file_upload_success'));
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }

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

        $user = $request->user();
        abort_unless($this->canUpload($user), 403);

        $request->validate([
            'caption' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'make_cover' => ['nullable', 'boolean'],
            'image_override_enabled' => ['nullable', 'boolean'],
        ]);

        $assetImage->caption = $request->input('caption');

        if ($request->filled('sort_order')) {
            $assetImage->sort_order = (int) $request->input('sort_order');
        }

        if ($request->boolean('make_cover')) {
            DB::transaction(function () use ($asset, $assetImage) {
                $asset->images()->where('id', '!=', $assetImage->id)->increment('sort_order');
                $assetImage->sort_order = 0;
                $assetImage->save();

                $asset->image = Str::after($assetImage->file_path, 'assets/');
                $asset->image_override_enabled = true;
                $asset->save();
            });
        } else {
            $assetImage->save();
        }

        if ($request->has('image_override_enabled')) {
            $asset->image_override_enabled = $request->boolean('image_override_enabled');
            $asset->save();
        }

        return back()->with('success', trans('general.image_caption_updated'));
    }


    public function destroy(Request $request, Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        $this->authorize('update', $asset);


        $user = $request->user();
        abort_unless($this->canDelete($user), 403);

        $relative = Str::after($assetImage->file_path, 'assets/');

        Storage::disk('public')->delete($assetImage->file_path);
        $assetImage->delete();

        if ($asset->image === $relative || $asset->images()->count() === 0) {
            $asset->syncImageOverridePointers();
        }

        return back()->with('success', trans('general.image_deleted'));
    }
}
