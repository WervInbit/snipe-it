<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Models\ModelNumberImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModelNumberImagesController extends Controller
{
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
        $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');

        $maxSortOrder = $modelNumber->images()->max('sort_order');
        $sortOrder = $request->filled('sort_order')
            ? (int) $request->input('sort_order')
            : ($maxSortOrder === null ? 0 : ((int) $maxSortOrder + 1));

        $image = $modelNumber->images()->create([
            'file_path' => $path,
            'caption' => $request->input('caption'),
            'sort_order' => $sortOrder,
        ]);

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

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($modelNumberImage->file_path);

            $file = $request->file('image');
            $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
            $modelNumberImage->file_path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');
        }

        if ($request->exists('caption')) {
            $modelNumberImage->caption = $request->input('caption');
        }

        if ($request->filled('sort_order')) {
            $modelNumberImage->sort_order = (int) $request->input('sort_order');
        }

        $modelNumberImage->save();

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

        Storage::disk('public')->delete($modelNumberImage->file_path);
        $modelNumberImage->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('general.saved')));
    }
}
