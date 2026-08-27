<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AttributeDefinition;
use App\Models\TestResult;
use App\Models\TestResultPhoto;
use App\Models\TestRun;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowEvidencePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TestResultController extends Controller
{
    public function __construct(private readonly WorkflowEvidencePhotoService $workflowEvidencePhotos)
    {
    }

    public function active(Asset $asset)
    {
        $this->authorize('view', $asset);

        $requestedRunId = request()->query('run');
        $runsQuery = $asset->tests()
            ->with([
                'results' => function ($query) {
                    $query
                        ->with(['type', 'attributeDefinition', 'photos'])
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'profile',
                'user',
            ]);
        $workflowProfiles = WorkflowProfile::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->withCount('items')
            ->ordered()
            ->get();

        if ($requestedRunId) {
            $run = $runsQuery->whereKey($requestedRunId)->first();
            abort_unless($run, 404);
        } else {
            $run = $runsQuery->first();
        }

        $canUpdateResults = Gate::allows('update', $asset);

        if ($run) {
            $canUpdateResults = $canUpdateResults || Gate::allows('update', $run);
        }

        $canStartRun = Gate::allows('tests.execute') && Gate::allows('view', $asset);

        if (!$run) {
            return view('tests.active', [
                'asset' => $asset,
                'run' => null,
                'results' => collect(),
                'progress' => [
                    'total' => 0,
                    'completed' => 0,
                    'remaining' => 0,
                    'failures' => 0,
                    'blocking_failures' => 0,
                ],
                'failingLabels' => collect(),
                'canUpdate' => $canUpdateResults,
                'canStartRun' => $canStartRun,
                'canViewAudit' => Gate::allows('audits.view'),
                'workflowProfiles' => $workflowProfiles,
            ]);
        }

        $results = $run->results->map(function (TestResult $result) use ($asset, $run) {
            $definition = $result->attributeDefinition;
            $type = $result->type;
            $isRequired = $result->is_required;
            $labelMode = $result->result_label_mode ?: WorkflowProfileItem::LABEL_MODE_PASS_FAIL;

            $label = $definition?->label ?? $type?->name ?? trans('general.unknown');
            $slug = $type?->slug ?? Str::slug($label ?? 'result');
            $instructions = trim((string)($type?->instructions ?: $definition?->instructions ?: $definition?->help_text ?: ''));
            $expected = $result->expected_value;

            if ($definition && $definition->datatype === AttributeDefinition::DATATYPE_BOOL && $expected !== null) {
                $expected = $expected === '1' ? trans('general.yes') : trans('general.no');
            }

            $photos = $result->photos->map(function (TestResultPhoto $photo) use ($asset, $run, $result) {
                return [
                    'id' => $photo->id,
                    'url' => route('test-results.photos.show', [$asset, $run, $result, $photo]),
                ];
            });

            return [
                'id' => $result->id,
                'status' => $result->status,
                'label' => $label,
                'slug' => $slug,
                'note' => $result->note,
                'instructions' => $instructions,
                'expected' => $expected,
                'attribute' => $definition?->label,
                'note_saved_at' => $result->updated_at?->timezone(config('app.timezone'))?->format('Y-m-d H:i'),
                'photos' => $photos,
                'is_required' => $isRequired,
                'result_label_mode' => $labelMode,
                'pass_label' => $labelMode === WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE
                    ? trans('tests.status_done')
                    : trans('tests.status_pass'),
                'fail_label' => $labelMode === WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE
                    ? trans('tests.status_not_done')
                    : trans('tests.status_fail'),
            ];
        })->values();

        $requiredResults = $results->where('is_required', true);
        $optionalResults = $results->where('is_required', false);

        $total = $requiredResults->count();
        $requiredFailCount = $requiredResults->where('status', TestResult::STATUS_FAIL)->count();
        $optionalFailCount = $optionalResults->where('status', TestResult::STATUS_FAIL)->count();
        $openCount = $requiredResults->where('status', TestResult::STATUS_NVT)->count();

        $progress = [
            'total' => $total,
            'completed' => $total - $openCount,
            'remaining' => $openCount,
            'failures' => $requiredFailCount + $optionalFailCount,
            'blocking_failures' => $requiredFailCount,
        ];

        $failingLabels = $results
            ->where('status', TestResult::STATUS_FAIL)
            ->pluck('label');

        return view('tests.active', [
            'asset' => $asset,
            'run' => $run,
            'results' => $results,
            'progress' => $progress,
            'failingLabels' => $failingLabels,
            'canUpdate' => $canUpdateResults,
            'canStartRun' => $canStartRun,
            'canViewAudit' => Gate::allows('audits.view'),
            'workflowProfiles' => $workflowProfiles,
        ]);
    }

    public function edit(Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);
        $testRun->load('results.type', 'results.attributeDefinition');
        return redirect()->route('test-results.active', ['asset' => $asset->id, 'run' => $testRun->id]);
    }

    public function update(Request $request, Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);
        $testRun->loadMissing('results.photos');

        $preparedPhotos = [];
        foreach ($testRun->results as $result) {
            if ($request->hasFile('photo.'.$result->id)) {
                $preparedPhotos[$result->id] = $this->workflowEvidencePhotos->prepare(
                    $request->file('photo.'.$result->id),
                    'photo.'.$result->id
                );
            }
        }

        $storedPaths = [];
        try {
            foreach ($preparedPhotos as $resultId => $preparedPhoto) {
                $storedPaths[$resultId] = $this->workflowEvidencePhotos->storePrepared(
                    $preparedPhoto,
                    $resultId
                );
            }
        } catch (Throwable $exception) {
            $this->deleteEvidencePaths($storedPaths);
            throw $exception;
        }

        $obsoletePaths = [];
        try {
            DB::transaction(function () use (
                $request,
                $testRun,
                $storedPaths,
                &$obsoletePaths
            ): void {
                foreach ($testRun->results as $result) {
                    $status = $request->input('status.'.$result->id);
                    if (in_array($status, TestResult::STATUSES, true)) {
                        $result->status = $status;
                    }
                    $result->note = $request->input('note.'.$result->id);

                    if (isset($storedPaths[$result->id])) {
                        $obsoletePaths = array_merge(
                            $obsoletePaths,
                            $result->photos->pluck('path')->all()
                        );

                        if ($result->photo_path) {
                            $obsoletePaths[] = $result->photo_path;
                        }

                        $result->photos()->delete();
                        $result->photos()->create(['path' => $storedPaths[$result->id]]);
                        $result->photo_path = $storedPaths[$result->id];
                    }

                    $result->save();
                }

                $testRun->finished_at = now();
                $testRun->save();
            });
        } catch (Throwable $exception) {
            $this->deleteEvidencePaths($storedPaths);
            throw $exception;
        }

        $this->deleteEvidencePaths($obsoletePaths);

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('tests.run_saved'));
    }

    public function showPhoto(
        Asset $asset,
        TestRun $testRun,
        TestResult $result,
        TestResultPhoto $photo
    ) {
        abort_unless(
            $testRun->asset_id === $asset->id &&
            $result->workflow_run_id === $testRun->id &&
            $photo->workflow_result_id === $result->id,
            404
        );
        abort_unless(
            Gate::allows('view', $asset) || Gate::allows('update', $testRun),
            403
        );

        $safePhoto = $this->workflowEvidencePhotos->readSafe($photo);

        return response($safePhoto['contents'], 200, [
            'Content-Type' => $safePhoto['mime'],
            'Content-Disposition' => 'inline; filename="workflow-evidence.'.$safePhoto['extension'].'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function promotePhoto(
        Request $request,
        Asset $asset,
        TestRun $testRun,
        TestResult $result,
        TestResultPhoto $photo
    ): JsonResponse {
        $this->authorize('update', $testRun);
        $this->authorize('update', $asset);
        $this->authorize('uploadImages', $asset);
        abort_unless(
            $testRun->asset_id === $asset->id &&
            $result->workflow_run_id === $testRun->id &&
            $photo->workflow_result_id === $result->id,
            404
        );

        $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'enable_override' => ['nullable', 'boolean'],
            'make_cover' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $safePhoto = $this->workflowEvidencePhotos->readSafe($photo);
        $filename = $asset->id.'_'.Str::uuid().'.'.$safePhoto['extension'];
        $targetPath = 'assets/'.$asset->id.'/'.$filename;

        if (!Storage::disk('public')->put($targetPath, $safePhoto['contents'])) {
            throw new \RuntimeException('Unable to store the promoted workflow evidence photo.');
        }

        $caption = $request->input('caption');
        if ($caption === null || trim($caption) === '') {
            $label = $result->attributeDefinition?->label ?: $result->type?->name ?: trans('tests.photo_thumbnail_alt');
            $caption = trim($label.' '.now()->format('Y-m-d H:i'));
        }

        $assetImage = null;

        try {
            DB::transaction(function () use ($request, $asset, $photo, $targetPath, $caption, &$assetImage) {
                $makeCover = $request->boolean('make_cover', true);
                if ($makeCover) {
                    $asset->images()->increment('sort_order');
                    $sortOrder = 0;
                } else {
                    $sortOrder = $request->filled('sort_order')
                        ? (int) $request->input('sort_order')
                        : ((int) $asset->images()->max('sort_order') + 1);
                }

                $assetImage = $asset->images()->create([
                    'file_path' => $targetPath,
                    'caption' => $caption,
                    'sort_order' => $sortOrder,
                    'source' => 'test_photo',
                    'source_photo_id' => $photo->id,
                ]);

                if ($request->boolean('enable_override', true)) {
                    $asset->image = Str::after($targetPath, 'assets/');
                    $asset->image_override_enabled = true;
                    $asset->save();
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($targetPath);
            throw $exception;
        }

        return response()->json([
            'message' => trans('general.saved'),
            'image' => [
                'id' => $assetImage->id,
                'url' => Storage::disk('public')->url($assetImage->file_path),
                'sort_order' => (int) $assetImage->sort_order,
                'source' => $assetImage->source,
            ],
            'image_override_enabled' => (bool) $asset->fresh()->image_override_enabled,
        ]);
    }

    public function partialUpdate(Request $request, Asset $asset, TestRun $testRun, TestResult $result)
    {
        $this->authorize('update', $testRun);
        abort_unless(
            $testRun->asset_id === $asset->id && $result->workflow_run_id === $testRun->id,
            404
        );
        $result->loadMissing('photos');

        try {
            $preparedPhotos = [];
            if ($request->hasFile('photo')) {
                $files = $request->file('photo');
                $files = is_array($files) ? $files : [$files];

                if ($request->filled('remove_photo_id')) {
                    $removedPhotoCount = $result->photos->contains(
                        'id',
                        (int) $request->input('remove_photo_id')
                    ) ? 1 : 0;
                } else {
                    $removedPhotoCount = $request->boolean('remove_photo')
                        ? $result->photos->count()
                        : 0;
                }

                if (
                    $result->photos->count() - $removedPhotoCount + count($files)
                    > WorkflowEvidencePhotoService::MAX_PHOTOS_PER_RESULT
                ) {
                    throw ValidationException::withMessages([
                        'photo' => __(
                            'A workflow result may contain at most :count evidence photos.',
                            ['count' => WorkflowEvidencePhotoService::MAX_PHOTOS_PER_RESULT]
                        ),
                    ]);
                }

                foreach ($files as $index => $file) {
                    $preparedPhotos[] = $this->workflowEvidencePhotos->prepare(
                        $file,
                        is_array($request->file('photo')) ? 'photo.'.$index : 'photo'
                    );
                }
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => __('The uploaded workflow evidence photo is invalid.'),
                'errors' => $exception->errors(),
            ], 422);
        }

        $storedPaths = [];
        try {
            foreach ($preparedPhotos as $preparedPhoto) {
                $storedPaths[] = $this->workflowEvidencePhotos->storePrepared(
                    $preparedPhoto,
                    $result->id
                );
            }
        } catch (Throwable $exception) {
            $this->deleteEvidencePaths($storedPaths);
            throw $exception;
        }

        $updated = false;
        $response = [];
        $obsoletePaths = [];
        $newPhotoIds = [];

        try {
            DB::transaction(function () use (
                $request,
                $result,
                $testRun,
                $storedPaths,
                &$updated,
                &$response,
                &$obsoletePaths,
                &$newPhotoIds
            ): void {
                if ($request->has('status')) {
                    $status = $request->input('status');
                    if ($status === null || $status === '') {
                        $result->status = TestResult::STATUS_NVT;
                        $response['status'] = TestResult::STATUS_NVT;
                        $updated = true;
                    } elseif (in_array($status, TestResult::STATUSES, true)) {
                        $result->status = $status;
                        $response['status'] = $status;
                        $updated = true;
                    }
                }

                if ($request->exists('note')) {
                    $note = $request->input('note');
                    $result->note = $note;
                    $response['note'] = $note;
                    $updated = true;
                }

                $photosMutated = false;
                if ($request->filled('remove_photo_id')) {
                    $photoId = (int) $request->input('remove_photo_id');
                    $photo = $result->photos->firstWhere('id', $photoId);
                    if ($photo) {
                        $obsoletePaths[] = $photo->path;
                        $photo->delete();
                        $response['removed_photo_id'] = $photoId;
                        $updated = true;
                        $photosMutated = true;
                    }
                } elseif ($request->boolean('remove_photo')) {
                    $obsoletePaths = array_merge(
                        $obsoletePaths,
                        $result->photos->pluck('path')->all()
                    );
                    if ($result->photo_path) {
                        $obsoletePaths[] = $result->photo_path;
                    }
                    $result->photos()->delete();
                    $result->photo_path = null;
                    $updated = true;
                    $photosMutated = true;
                }

                foreach ($storedPaths as $relativePath) {
                    $photoModel = $result->photos()->create(['path' => $relativePath]);
                    $newPhotoIds[] = $photoModel->id;
                    $result->photo_path = $relativePath;
                    $updated = true;
                    $photosMutated = true;
                }

                if ($photosMutated) {
                    $latestPhoto = $result->photos()->orderByDesc('id')->first();
                    $result->photo_path = $latestPhoto?->path;
                }

                if ($updated) {
                    $result->save();
                    $testRun->finished_at = now();
                    $testRun->save();
                }
            });
        } catch (Throwable $exception) {
            $this->deleteEvidencePaths($storedPaths);
            throw $exception;
        }

        $this->deleteEvidencePaths($obsoletePaths);

        if ($updated) {
            $asset->refreshTestCompletionFlag();
        }

        $result->unsetRelation('photos');
        $result->load('photos');

        $response['photos'] = $result->photos->map(function (TestResultPhoto $photo) use ($asset, $testRun, $result) {
            return [
                'id' => $photo->id,
                'url' => route('test-results.photos.show', [$asset, $testRun, $result, $photo]),
            ];
        });

        $primaryPhoto = $newPhotoIds !== []
            ? $result->photos->firstWhere('id', $newPhotoIds[0])
            : $result->photos->last();
        $response['photo'] = $primaryPhoto
            ? [
                'id' => $primaryPhoto->id,
                'url' => route('test-results.photos.show', [$asset, $testRun, $result, $primaryPhoto]),
            ]
            : false;
        $response['photo_url'] = is_array($response['photo'])
            ? $response['photo']['url']
            : null;
        $response['message'] = trans('general.saved');

        return response()->json($response);
    }

    /**
     * @param iterable<int|string, string> $paths
     */
    private function deleteEvidencePaths(iterable $paths): void
    {
        foreach (array_unique([...$paths]) as $path) {
            $this->workflowEvidencePhotos->delete($path);
        }
    }
}
