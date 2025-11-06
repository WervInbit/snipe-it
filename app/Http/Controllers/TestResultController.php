<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;

class TestResultController extends Controller
{
    
    public function edit(Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);
        $testRun->load('results.type', 'results.attributeDefinition');
        return view('tests.edit', compact('asset', 'testRun'));
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
