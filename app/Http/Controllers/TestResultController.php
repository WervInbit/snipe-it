<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\AttributeDefinition;
use App\Models\TestResultPhoto;

class TestResultController extends Controller
{
    public function active(Asset $asset)
    {
        $this->authorize('view', $asset);

        $requestedRunId = request()->query('run');
        $runsQuery = $asset->tests()
            ->with([
                'results' => function ($query) {
                    $query->with(['type', 'attributeDefinition', 'photos'])->orderBy('id');
                },
                'user',
            ]);

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
                'canStartRun' => Gate::allows('tests.execute') && Gate::allows('update', $asset),
                'canViewAudit' => Gate::allows('audits.view'),
            ]);
        }

        $results = $run->results->map(function (TestResult $result) {
            $definition = $result->attributeDefinition;
            $type = $result->type;
            $isRequired = $type?->is_required ?? true;

            $label = $definition?->label ?? $type?->name ?? trans('general.unknown');
            $slug = $type?->slug ?? Str::slug($label ?? 'result');
            $instructions = trim((string)($type?->instructions ?: $definition?->instructions ?: $definition?->help_text ?: ''));
            $expected = $result->expected_value;

            if ($definition && $definition->datatype === AttributeDefinition::DATATYPE_BOOL && $expected !== null) {
                $expected = $expected === '1' ? trans('general.yes') : trans('general.no');
            }

            $photos = $result->photos->map(function (TestResultPhoto $photo) {
                return [
                    'id' => $photo->id,
                    'url' => url($photo->path),
                ];
            });

            if ($photos->isEmpty() && $result->photo_path) {
                $photos = collect([[
                    'id' => null,
                    'url' => url($result->photo_path),
                ]]);
            }

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
            'canStartRun' => Gate::allows('tests.execute') && Gate::allows('update', $asset),
            'canViewAudit' => Gate::allows('audits.view'),
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
        foreach ($testRun->results as $result) {
            $status = $request->input('status.' . $result->id);
            if (in_array($status, TestResult::STATUSES, true)) {
                $result->status = $status;
            }
            $result->note = $request->input('note.' . $result->id);

            if ($request->hasFile('photo.' . $result->id)) {
                $file = $request->file('photo.' . $result->id);
                $destination = public_path('uploads/test_images');
                File::ensureDirectoryExists($destination);
                if ($result->photo_path && File::exists(public_path($result->photo_path))) {
                    File::delete(public_path($result->photo_path));
                }
                $filename = uniqid('test_', true) . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);
                $result->photo_path = 'uploads/test_images/' . $filename;
            }

            $result->save();
        }

        $testRun->finished_at = now();
        $testRun->save();

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('tests.run_saved'));
    }

    public function partialUpdate(Request $request, Asset $asset, TestRun $testRun, TestResult $result)
    {
        $this->authorize('update', $testRun);
        abort_unless(
            $testRun->asset_id === $asset->id && $result->test_run_id === $testRun->id,
            404
        );

        $updated = false;
        $response = [];
        $photosMutated = false;

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

        $result->loadMissing('photos');

        if ($request->filled('remove_photo_id')) {
            $photoId = (int) $request->input('remove_photo_id');
            $photo = $result->photos->firstWhere('id', $photoId);
            if ($photo) {
                if ($photo->path && File::exists(public_path($photo->path))) {
                    File::delete(public_path($photo->path));
                }
                $photo->delete();
                $response['removed_photo_id'] = $photoId;
                $updated = true;
                $photosMutated = true;
            }
        } elseif ($request->boolean('remove_photo')) {
            foreach ($result->photos as $photo) {
                if ($photo->path && File::exists(public_path($photo->path))) {
                    File::delete(public_path($photo->path));
                }
                $photo->delete();
            }
            if ($result->photo_path && File::exists(public_path($result->photo_path))) {
                File::delete(public_path($result->photo_path));
            }
            $result->photo_path = null;
            $updated = true;
            $photosMutated = true;
        }

        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            if (!is_array($files)) {
                $files = [$files];
            }

            $destination = public_path('uploads/test_images');
            File::ensureDirectoryExists($destination);

            $newPhotos = [];
            foreach ($files as $file) {
                $filename = uniqid('test_', true) . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);
                $relativePath = 'uploads/test_images/' . $filename;

                $photoModel = $result->photos()->create(['path' => $relativePath]);
                $newPhotos[] = [
                    'id' => $photoModel->id,
                    'url' => url($relativePath),
                ];
                $result->photo_path = $relativePath;
            }

            $response['photo'] = $newPhotos[0] ?? null;
            $response['photos'] = $newPhotos;
            $updated = true;
            $photosMutated = true;
        } elseif (!array_key_exists('photos', $response)) {
            $response['photos'] = $result->photos->map(function (TestResultPhoto $photo) {
                return [
                    'id' => $photo->id,
                    'url' => url($photo->path),
                ];
            });
        }

        if ($photosMutated) {
            $result->unsetRelation('photos');
            $result->load('photos');
            $latestPhoto = $result->photos()->latest()->first();
            $result->photo_path = $latestPhoto?->path;
        }

        $result->loadMissing('photos');

        $response['photos'] = $result->photos->map(function (TestResultPhoto $photo) {
            return [
                'id' => $photo->id,
                'url' => url($photo->path),
            ];
        });

        if (!isset($response['photo'])) {
            $latest = $result->photos->last();
            $response['photo'] = $latest
                ? ['id' => $latest->id, 'url' => url($latest->path)]
                : false;
        }

        if (is_array($response['photo'] ?? null)) {
            $response['photo_url'] = $response['photo']['url'];
        } elseif (!isset($response['photo_url'])) {
            $response['photo_url'] = null;
        }

        if ($updated) {
            $result->save();
            $testRun->finished_at = now();
            $testRun->save();
            $asset->refreshTestCompletionFlag();
        }

        $response['message'] = trans('general.saved');

        return response()->json($response);
    }
}
