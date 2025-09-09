<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
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
        $test = $asset->assetTests()->create($data);
        $test->logCreate('asset test created');
        if ($request->wantsJson()) {
            return response()->json($test, 201);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.created'));
    }

    public function edit(Asset $asset, AssetTest $test): View
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        return view('tests.create', compact('asset', 'test'));
    }

    public function update(Request $request, Asset $asset, AssetTest $test): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $data = $request->validate([
            'performed_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:191'],
            'needs_cleaning' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['updated_by'] = $request->user()->id;
        $test->update($data);
        $test->log()->create([
            'created_by' => $request->user()->id,
            'note' => 'asset test updated'
        ])->logaction('update');
        if ($request->wantsJson()) {
            return response()->json($test);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.updated'));
    }

    public function destroy(Request $request, Asset $asset, AssetTest $test): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.delete');
        $this->authorize('update', $asset);
        $test->log()->create([
            'created_by' => $request->user()->id,
            'note' => 'asset test deleted'
        ])->logaction('delete');
        $test->delete();
        if ($request->wantsJson()) {
            return response()->json([], 204);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }

    public function repeatForm(Asset $asset, AssetTest $test): View
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        return view('tests.repeat', compact('asset', 'test'));
    }

    public function repeat(Request $request, Asset $asset, AssetTest $test): RedirectResponse|JsonResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);
        $new = $asset->assetTests()->create([
            'performed_at' => now(),
            'status' => $test->status,
            'needs_cleaning' => $test->needs_cleaning,
            'notes' => $test->notes,
            'created_by' => $request->user()->id,
        ]);
        $new->logCreate('asset test repeated');
        if ($request->wantsJson()) {
            return response()->json($new, 201);
        }
        return redirect()->route('asset-tests.index', $asset->id)
            ->with('success', trans('general.created'));
    }
}
