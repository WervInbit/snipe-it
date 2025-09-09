<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AssetImageController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image'],
            'caption' => ['nullable', 'string'],
        ]);

        if ($asset->images()->count() >= 30) {
            throw ValidationException::withMessages([
                'image' => trans('general.too_many_asset_images'),
            ]);
        }

        $path = $request->file('image')->store('assets/'.$asset->id, 'public');

        $asset->images()->create([
            'file_path' => $path,
            'caption' => $request->input('caption'),
        ]);

        return back()->with('success', trans('general.image_upload'));
    }

    public function destroy(Asset $asset, AssetImage $assetImage): RedirectResponse
    {
        if ($assetImage->asset_id !== $asset->id) {
            abort(404);
        }

        Storage::disk('public')->delete($assetImage->file_path);
        $assetImage->delete();

        return back()->with('success', trans('general.image_delete')); // reuse existing string
    }
}
