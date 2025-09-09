<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetImageController extends Controller
{
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        $user = $request->user();
        abort_unless(
            $user->hasAccess('superuser') ||
            $user->hasAccess('admin') ||
            $user->hasAccess('supervisor') ||
            $user->hasAccess('senior-refurbisher') ||
            $user->hasAccess('refurbisher'),
            403
        );

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

        DB::beginTransaction();

        try {
            foreach ($request->file('image') as $index => $file) {
                $filename = $asset->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('assets/'.$asset->id, $filename, 'public');

                $image = $asset->images()->create([
                    'file_path' => $path,
                    'caption' => $request->input('caption')[$index],
                ]);

                // If this is the first image for the asset, mark it as the cover image
                if (! $asset->image) {
                    $asset->image = Str::after($path, 'assets/');
                    $asset->save();
                }

                $paths[] = $path;

                $stored[] = [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($path),
                ];
            }

            DB::commit();

            return response()->json(['images' => $stored], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }

            return response()->json(['message' => trans('general.image_upload_failed')], 500);
        }
    }

    public function update(Request $request, Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        $this->authorize('update', $asset);

        $user = $request->user();
        abort_unless(
            $user->hasAccess('superuser') ||
            $user->hasAccess('admin') ||
            $user->hasAccess('supervisor') ||
            $user->hasAccess('senior-refurbisher') ||
            $user->hasAccess('refurbisher'),
            403
        );

        $request->validate([
            'caption' => ['required', 'string'],
        ]);

        $assetImage->update(['caption' => $request->input('caption')]);

        return back()->with('success', trans('general.image_caption_updated'));
    }


    public function destroy(Request $request, Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        $this->authorize('update', $asset);


        $user = $request->user();
        abort_unless(
            $user->hasAccess('superuser') ||
            $user->hasAccess('admin') ||
            $user->hasAccess('supervisor') ||
            $user->hasAccess('senior-refurbisher'),
            403
        );

        $relative = Str::after($assetImage->file_path, 'assets/');

        Storage::disk('public')->delete($assetImage->file_path);
        $assetImage->delete();

        if ($asset->image === $relative) {
            $next = $asset->images()->first();
            $asset->image = $next ? Str::after($next->file_path, 'assets/') : null;
            $asset->save();
        }

        return back()->with('success', trans('general.image_deleted'));
    }
}
