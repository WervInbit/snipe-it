<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
            $result->status = $request->input('status.' . $result->id);
            $result->note = $request->input('note.' . $result->id);
            $result->save();
        }

        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.updated'));
    }
}
