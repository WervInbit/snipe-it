<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use Illuminate\Http\Request;

class TestResultController extends Controller
{
    public function edit(Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', Asset::class);
        $testRun->load('results.type');
        return view('tests.edit', compact('asset', 'testRun'));
    }

    public function update(Request $request, Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', Asset::class);
        foreach ($testRun->results as $result) {
            $result->status = $request->input('status.' . $result->id);
            $result->notes = $request->input('notes.' . $result->id);
            $result->save();
        }

        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.updated'));
    }
}
