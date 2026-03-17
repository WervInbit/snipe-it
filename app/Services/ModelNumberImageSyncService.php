<?php

namespace App\Services;

use App\Models\ModelNumber;
use App\Models\ModelNumberImage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModelNumberImageSyncService
{
    public function validationRules(): array
    {
        return [
            'existing_images' => ['nullable', 'array'],
            'existing_images.*.caption' => ['nullable', 'string', 'max:255'],
            'existing_images.*.delete' => ['nullable', 'boolean'],
            'existing_images.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'image_order' => ['nullable', 'array'],
            'image_order.*' => ['required', 'integer', 'distinct'],
            'new_image.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'new_image.caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function sync(ModelNumber $modelNumber, Request $request, array $validated): void
    {
        $existingImages = $modelNumber->images()->get()->keyBy(fn (ModelNumberImage $image) => (string) $image->id);
        $submittedImages = collect($validated['existing_images'] ?? [])
            ->mapWithKeys(fn ($row, $id) => [(string) $id => $row]);

        $this->assertSubmittedImagesMatchModel($existingImages, $submittedImages);

        $keptIds = $existingImages
            ->keys()
            ->reject(fn (string $id) => $this->isMarkedForRemoval($submittedImages->get($id, [])))
            ->map(fn (string $id) => (int) $id)
            ->values()
            ->all();

        $submittedOrder = array_values(array_map('intval', $validated['image_order'] ?? []));
        $this->assertSubmittedOrderMatchesKeptImages($submittedOrder, $keptIds);

        DB::transaction(function () use ($existingImages, $submittedImages, $submittedOrder, $modelNumber, $request, $validated) {
            foreach ($existingImages as $id => $image) {
                $row = $submittedImages->get($id, []);

                if ($this->isMarkedForRemoval($row)) {
                    Storage::disk('public')->delete($image->file_path);
                    $image->delete();
                    continue;
                }

                $image->caption = $row['caption'] ?? null;

                if ($request->hasFile("existing_images.$id.image")) {
                    Storage::disk('public')->delete($image->file_path);

                    $file = $request->file("existing_images.$id.image");
                    $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
                    $image->file_path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');
                }

                $image->save();
            }

            foreach ($submittedOrder as $index => $id) {
                ModelNumberImage::query()
                    ->where('model_number_id', $modelNumber->id)
                    ->whereKey($id)
                    ->update(['sort_order' => $index]);
            }

            if ($request->hasFile('new_image.image')) {
                $file = $request->file('new_image.image');
                $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');

                $modelNumber->images()->create([
                    'file_path' => $path,
                    'caption' => data_get($validated, 'new_image.caption'),
                    'sort_order' => count($submittedOrder),
                ]);
            }
        });
    }

    private function assertSubmittedImagesMatchModel(Collection $existingImages, Collection $submittedImages): void
    {
        $existingIds = $existingImages->keys()->sort()->values()->all();
        $submittedIds = $submittedImages->keys()->sort()->values()->all();

        if ($existingIds !== $submittedIds) {
            throw ValidationException::withMessages([
                'existing_images' => __('Invalid image update payload.'),
            ]);
        }
    }

    private function assertSubmittedOrderMatchesKeptImages(array $submittedOrder, array $keptIds): void
    {
        $sortedSubmitted = $submittedOrder;
        sort($sortedSubmitted);

        $sortedKept = $keptIds;
        sort($sortedKept);

        if ($sortedSubmitted !== $sortedKept) {
            throw ValidationException::withMessages([
                'image_order' => __('Invalid image order payload.'),
            ]);
        }
    }

    private function isMarkedForRemoval(array $row): bool
    {
        return (bool) ($row['delete'] ?? false);
    }
}
