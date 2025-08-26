<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestType;
use Illuminate\Http\Request;

class TestRunController extends Controller
{
    public function index(Asset $asset)
    {
        $this->authorize('view', Asset::class);
        $runs = $asset->testRuns;
        return view('tests.index', compact('asset', 'runs'));
    }

    public function store(Request $request, Asset $asset)
    {
        $this->authorize('update', Asset::class);
        $run = TestRun::create([
            'asset_id' => $asset->id,
            'user_id' => $request->user()->id,
        ]);

        foreach (TestType::all() as $type) {
            $run->results()->create([
                'test_type_id' => $type->id,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('test-results.edit', [$asset->id, $run->id])
            ->with('success', trans('general.created'));
    }

    public function destroy(Asset $asset, TestRun $testRun)
    {
        $this->authorize('update', Asset::class);
        $testRun->delete();
        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }
}
