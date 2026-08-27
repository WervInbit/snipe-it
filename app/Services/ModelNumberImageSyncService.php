<?php

namespace App\Services;

use App\Models\ModelNumber;
use App\Models\ModelNumberImage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ModelNumberImageSyncService
{
    public function __construct(
        private SafeRasterImageService $rasterImages,
    ) {
    }

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

    /**
     * @return array{new_paths: list<string>, old_paths: list<string>}
     */
    public function sync(ModelNumber $modelNumber, Request $request, array $validated): array
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

        $newPaths = [];
        $oldPaths = [];

        try {
            DB::transaction(function () use (
                $existingImages,
                $submittedImages,
                $submittedOrder,
                $modelNumber,
                $request,
                $validated,
                &$newPaths,
                &$oldPaths
            ) {
                foreach ($existingImages as $id => $image) {
                    $row = $submittedImages->get($id, []);

                    if ($this->isMarkedForRemoval($row)) {
                        $oldPaths[] = $image->file_path;
                        $image->delete();
                        continue;
                    }

                    $image->caption = $row['caption'] ?? null;

                    if ($request->hasFile("existing_images.$id.image")) {
                        $file = $request->file("existing_images.$id.image");
                        $storedImage = $this->rasterImages->storePublic(
                            $file,
                            'model_numbers/'.$modelNumber->id,
                            $modelNumber->id.'_',
                            "existing_images.$id.image"
                        );
                        $newPaths[] = $storedImage['path'];
                        $oldPaths[] = $image->file_path;
                        $image->file_path = $storedImage['path'];
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
                    $storedImage = $this->rasterImages->storePublic(
                        $file,
                        'model_numbers/'.$modelNumber->id,
                        $modelNumber->id.'_',
                        'new_image.image'
                    );
                    $newPaths[] = $storedImage['path'];

                    $modelNumber->images()->create([
                        'file_path' => $storedImage['path'],
                        'caption' => data_get($validated, 'new_image.caption'),
                        'sort_order' => count($submittedOrder),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPaths);

            throw $e;
        }

        return [
            'new_paths' => array_values(array_unique($newPaths)),
            'old_paths' => array_values(array_unique($oldPaths)),
        ];
    }

    /**
     * Remove newly staged files when the outer metadata transaction rolls back.
     *
     * @param array{new_paths: list<string>, old_paths: list<string>} $changes
     */
    public function cleanupAfterRollback(array $changes): void
    {
        Storage::disk('public')->delete($changes['new_paths']);
    }

    /**
     * Remove superseded files only after the outer metadata transaction commits.
     *
     * @param array{new_paths: list<string>, old_paths: list<string>} $changes
     */
    public function cleanupAfterCommit(array $changes): void
    {
        Storage::disk('public')->delete($changes['old_paths']);
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
