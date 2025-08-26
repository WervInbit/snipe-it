<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pass', 'fail', 'pending'])],
            'notes' => 'nullable|string',
        ]);

        $result = new TestResult();
        $result->status = $validated['status'];
        $result->note = $validated['notes'] ?? null;
        $result->save();

        return redirect()->back()->with('success', trans('general.test_result_saved'));
    }
}
