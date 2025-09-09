<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\TestResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestRunController extends Controller
{
    public function index(Asset $asset)
    {
        $this->authorize('view', $asset);
        $runs = $asset->tests()
            ->with(['results.type', 'user'])
            ->orderByDesc('created_at')
            ->get();

        return view('tests.index', compact('asset', 'runs'));
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->user()->associate($request->user());
        $run->save();

        foreach (TestType::pluck('id') as $typeId) {
            $run->results()->create([
                'test_type_id' => $typeId,
                'status' => TestResult::STATUS_NVT,
                'note' => null,
            ]);
        }

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-runs.index', ['asset' => $asset->id])
            ->with('success', trans('general.test_run_created'));
    }

    public function destroy(Asset $asset, TestRun $testRun)
    {
        $this->authorize('view', $asset);
        $testRun->delete();
        $asset->refreshTestCompletionFlag();
        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }
}
