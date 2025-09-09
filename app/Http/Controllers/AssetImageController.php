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

        foreach ($request->file('image') as $index => $file) {
            $path = $file->store('assets/'.$asset->id, 'public');

            $asset->images()->create([
                'file_path' => $path,
                'caption' => $request->input('caption')[$index],
            ]);
        }

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
