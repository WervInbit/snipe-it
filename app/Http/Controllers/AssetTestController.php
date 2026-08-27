<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssetTestController extends Controller
{
    public function index(Request $request, Asset $asset)
    {
        Gate::authorize('tests.execute');
        $this->authorize('view', $asset);
        $tests = $asset->assetTests()->get();
        if ($request->wantsJson()) {
            return response()->json($tests);
        }
        return view('tests.index', compact('asset', 'tests'));
    }

    public function create(Asset $asset): View
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        return view('tests.create', ['asset' => $asset, 'test' => new AssetTest]);
    }

    public function store(Request $request, Asset $asset): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $data = $request->validate([
            'performed_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:191'],
            'needs_cleaning' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()->id;
        $test = DB::transaction(function () use ($asset, $data): AssetTest {
            $test = $asset->assetTests()->create($data);
            $test->logCreate('asset test created');

            return $test;
        });

        if ($request->wantsJson()) {
            return response()->json($test, 201);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.created'));
    }

    public function edit(Asset $asset, AssetTest $assetTest): View
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $this->ensureAssetTestBelongsToAsset($asset, $assetTest);

        $test = $assetTest;

        return view('tests.create', compact('asset', 'test'));
    }

    public function update(Request $request, Asset $asset, AssetTest $assetTest): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $this->ensureAssetTestBelongsToAsset($asset, $assetTest);

        $data = $request->validate([
            'performed_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:191'],
            'needs_cleaning' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['updated_by'] = $request->user()->id;
        DB::transaction(function () use ($assetTest, $data, $request): void {
            $assetTest->update($data);
            $log = $assetTest->log()->make([
                'created_by' => $request->user()->id,
                'note' => 'asset test updated',
            ]);
            $log->logaction('update');
        });

        if ($request->wantsJson()) {
            return response()->json($assetTest->fresh());
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.updated'));
    }

    public function destroy(Request $request, Asset $asset, AssetTest $assetTest): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.delete');
        $this->authorize('update', $asset);
        $this->ensureAssetTestBelongsToAsset($asset, $assetTest);

        DB::transaction(function () use ($assetTest, $request): void {
            $log = $assetTest->log()->make([
                'created_by' => $request->user()->id,
                'note' => 'asset test deleted',
            ]);
            $log->logaction('delete');
            $assetTest->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([], 204);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }

    public function repeatForm(Asset $asset, AssetTest $assetTest): View
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $this->ensureAssetTestBelongsToAsset($asset, $assetTest);

        $test = $assetTest;

        return view('tests.repeat', compact('asset', 'test'));
    }

    public function repeat(Request $request, Asset $asset, AssetTest $assetTest): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $this->ensureAssetTestBelongsToAsset($asset, $assetTest);

        $new = DB::transaction(function () use ($asset, $assetTest, $request): AssetTest {
            $new = $asset->assetTests()->create([
                'performed_at' => now(),
                'status' => $assetTest->status,
                'needs_cleaning' => $assetTest->needs_cleaning,
                'notes' => $assetTest->notes,
                'created_by' => $request->user()->id,
            ]);
            $new->logCreate('asset test repeated');

            return $new;
        });

        if ($request->wantsJson()) {
            return response()->json($new, 201);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.created'));
    }

    private function ensureAssetTestBelongsToAsset(Asset $asset, AssetTest $assetTest): void
    {
        abort_unless((int) $assetTest->asset_id === (int) $asset->id, 404);
    }
}
