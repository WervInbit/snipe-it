<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Models\ModelNumberImage;
use App\Services\SafeRasterImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModelNumberImagesController extends Controller
{
    public function __construct(
        private SafeRasterImageService $rasterImages,
    ) {
    }

    public function index(ModelNumber $modelNumber): JsonResponse
    {
        $this->authorize('view', AssetModel::class);

        $images = $modelNumber->images()->get()->map(function (ModelNumberImage $image) {
            return [
                'id' => (int) $image->id,
                'sort_order' => (int) $image->sort_order,
                'caption' => $image->caption,
                'url' => Storage::disk('public')->url($image->file_path),
                'file_path' => $image->file_path,
                'created_at' => optional($image->created_at)->toIso8601String(),
                'updated_at' => optional($image->updated_at)->toIso8601String(),
            ];
        })->values()->all();

        return response()->json(Helper::formatStandardApiResponse('success', [
            'model_number_id' => (int) $modelNumber->id,
            'images' => $images,
        ], trans('general.saved')));
    }

    public function store(Request $request, ModelNumber $modelNumber): JsonResponse
    {
        $this->authorize('update', AssetModel::class);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $file = $request->file('image');
        $storedImage = $this->rasterImages->storePublic(
            $file,
            'model_numbers/'.$modelNumber->id,
            $modelNumber->id.'_'
        );

        $maxSortOrder = $modelNumber->images()->max('sort_order');
        $sortOrder = $request->filled('sort_order')
            ? (int) $request->input('sort_order')
            : ($maxSortOrder === null ? 0 : ((int) $maxSortOrder + 1));

        try {
            $image = $modelNumber->images()->create([
                'file_path' => $storedImage['path'],
                'caption' => $request->input('caption'),
                'sort_order' => $sortOrder,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($storedImage['path']);

            throw $e;
        }

        return response()->json(Helper::formatStandardApiResponse('success', [
            'id' => (int) $image->id,
            'sort_order' => (int) $image->sort_order,
            'caption' => $image->caption,
            'url' => Storage::disk('public')->url($image->file_path),
            'file_path' => $image->file_path,
        ], trans('general.saved')), 201);
    }

    public function update(Request $request, ModelNumber $modelNumber, ModelNumberImage $modelNumberImage): JsonResponse
    {
        if ($modelNumberImage->model_number_id !== $modelNumber->id) {
            abort(404);
        }

        $this->authorize('update', AssetModel::class);

        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $newPath = null;
        $oldPath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $storedImage = $this->rasterImages->storePublic(
                $file,
                'model_numbers/'.$modelNumber->id,
                $modelNumber->id.'_'
            );
            $oldPath = $modelNumberImage->file_path;
            $newPath = $storedImage['path'];
            $modelNumberImage->file_path = $newPath;
        }

        if ($request->exists('caption')) {
            $modelNumberImage->caption = $request->input('caption');
        }

        if ($request->filled('sort_order')) {
            $modelNumberImage->sort_order = (int) $request->input('sort_order');
        }

        try {
            $modelNumberImage->save();
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $e;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(Helper::formatStandardApiResponse('success', [
            'id' => (int) $modelNumberImage->id,
            'sort_order' => (int) $modelNumberImage->sort_order,
            'caption' => $modelNumberImage->caption,
            'url' => Storage::disk('public')->url($modelNumberImage->file_path),
            'file_path' => $modelNumberImage->file_path,
        ], trans('general.saved')));
    }

    public function destroy(ModelNumber $modelNumber, ModelNumberImage $modelNumberImage): JsonResponse
    {
        if ($modelNumberImage->model_number_id !== $modelNumber->id) {
            abort(404);
        }

        $this->authorize('update', AssetModel::class);

        $path = $modelNumberImage->file_path;
        $modelNumberImage->delete();
        Storage::disk('public')->delete($path);

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('general.saved')));
    }
}
