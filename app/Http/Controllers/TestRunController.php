<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\TestResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->user()->associate($request->user());
        $run->sku()->associate($asset->sku);
        $run->started_at = now();
        $run->save();

        foreach (TestType::forAsset($asset)->pluck('id') as $typeId) {
            $run->results()->create([
                'test_type_id' => $typeId,
                'status' => TestResult::STATUS_NVT,
                'note' => null,
            ]);
        }

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-results.edit', [$asset->id, $run->id]);
    }

    public function destroy(Asset $asset, TestRun $testRun)
    {
        $this->authorize('delete', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);
        $testRun->delete();
        $asset->refreshTestCompletionFlag();
        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }
}
