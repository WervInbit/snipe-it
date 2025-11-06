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

class TestResultController extends Controller
{
    public function active(Asset $asset)
    {
        $this->authorize('view', $asset);

        $run = $asset->tests()
            ->with([
                'results' => function ($query) {
                    $query->with(['type', 'attributeDefinition'])->orderBy('id');
                },
                'user',
            ])
            ->first();

        if (!$run) {
            return view('tests.active', [
                'asset' => $asset,
                'run' => null,
                'groups' => [
                    'fail' => collect(),
                    'open' => collect(),
                    'pass' => collect(),
                ],
                'progress' => [
                    'total' => 0,
                    'completed' => 0,
                    'remaining' => 0,
                    'failures' => 0,
                ],
                'failingLabels' => collect(),
                'canUpdate' => Gate::allows('update', $asset),
                'canStartRun' => Gate::allows('tests.execute') && Gate::allows('update', $asset),
                'canViewAudit' => Gate::allows('audits.view'),
            ]);
        }

        $results = $run->results->map(function (TestResult $result) {
            $definition = $result->attributeDefinition;
            $type = $result->type;

            $label = $definition?->label ?? $type?->name ?? trans('general.unknown');
            $slug = $type?->slug ?? Str::slug($label ?? 'result');
            $instructions = trim((string)($type?->instructions ?: $definition?->instructions ?: $definition?->help_text ?: ''));
            $expected = $result->expected_value;

            if ($definition && $definition->datatype === AttributeDefinition::DATATYPE_BOOL && $expected !== null) {
                $expected = $expected === '1' ? trans('general.yes') : trans('general.no');
            }

            return [
                'id' => $result->id,
                'status' => $result->status,
                'label' => $label,
                'slug' => $slug,
                'note' => $result->note,
                'instructions' => $instructions,
                'photo' => $result->photo_path ? url($result->photo_path) : null,
                'expected' => $expected,
                'attribute' => $definition?->label,
            ];
        });

        $grouped = [
            'fail' => $results->where('status', TestResult::STATUS_FAIL)->values(),
            'open' => $results->where('status', TestResult::STATUS_NVT)->values(),
            'pass' => $results->where('status', TestResult::STATUS_PASS)->values(),
        ];

        $total = $results->count();
        $openCount = $grouped['open']->count();
        $failCount = $grouped['fail']->count();

        $progress = [
            'total' => $total,
            'completed' => $total - $openCount,
            'remaining' => $openCount,
            'failures' => $failCount,
        ];

        $failingLabels = $grouped['fail']->pluck('label');

        return view('tests.active', [
            'asset' => $asset,
            'run' => $run,
            'groups' => $grouped,
            'progress' => $progress,
            'failingLabels' => $failingLabels,
            'canUpdate' => Gate::allows('update', $run),
            'canStartRun' => Gate::allows('tests.execute') && Gate::allows('update', $asset),
            'canViewAudit' => Gate::allows('audits.view'),
        ]);
    }

    public function edit(Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);
        $testRun->load('results.type', 'results.attributeDefinition');
        return redirect()->route('test-results.active', $asset->id);
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

        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, TestResult::STATUSES, true)) {
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

        if ($request->boolean('remove_photo')) {
            if ($result->photo_path && File::exists(public_path($result->photo_path))) {
                File::delete(public_path($result->photo_path));
            }
            $result->photo_path = null;
            $response['photo'] = false;
            $updated = true;
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $destination = public_path('uploads/test_images');
            File::ensureDirectoryExists($destination);

            if ($result->photo_path && File::exists(public_path($result->photo_path))) {
                File::delete(public_path($result->photo_path));
            }

            $filename = uniqid('test_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($destination, $filename);
            $relativePath = 'uploads/test_images/' . $filename;

            $result->photo_path = $relativePath;
            $response['photo'] = true;
            $response['photo_url'] = url($relativePath);
            $updated = true;
        } elseif ($result->photo_path) {
            $response['photo'] = true;
            $response['photo_url'] = url($result->photo_path);
        } else {
            $response['photo'] = false;
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
