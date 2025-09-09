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
        $this->authorize('update', $asset);
        $testRun->load('results.type');
        return view('tests.edit', compact('asset', 'testRun'));
    }

    public function update(Request $request, Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', $asset);
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

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.updated'));
    }
}
