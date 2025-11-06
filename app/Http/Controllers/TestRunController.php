<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TestRunController extends Controller
{
    public function index(Asset $asset)
    {
        $this->authorize('view', $asset);
        $runs = $asset->tests()
            ->with(['results.type', 'results.attributeDefinition', 'user'])
            ->orderByDesc('created_at')
            ->get();

        return view('tests.index', compact('asset', 'runs'));
    }

    public function store(Request $request, Asset $asset, EffectiveAttributeResolver $resolver): RedirectResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('update', $asset);

        $resolved = $resolver->resolveForAsset($asset);

        $missing = $resolved->filter(function ($attribute) {
            return $attribute->definition->required_for_category && $attribute->value === null;
        });

        if ($missing->isNotEmpty()) {
            return redirect()
                ->route('test-runs.index', $asset->id)
                ->withErrors([
                    'attributes' => __('Complete the model specification before starting a test run. Missing: :list', [
                        'list' => $missing->map(fn ($attribute) => $attribute->definition->label)->implode(', '),
                    ]),
                ]);
        }

        $run = new TestRun();
        $run->asset()->associate($asset);
        $run->user()->associate($request->user());
        $run->model_number_id = $asset->model_number_id;
        $run->started_at = now();
        $run->save();

        $resolvedAttributes = $resolved->filter(fn ($attribute) => $attribute->requiresTest);

        foreach ($resolvedAttributes as $attribute) {
            $definition = $attribute->definition->loadMissing('tests');

            foreach ($definition->tests as $testType) {
                $run->results()->create([
                    'test_type_id' => $testType->id,
                    'attribute_definition_id' => $definition->id,
                    'status' => TestResult::STATUS_NVT,
                    'note' => null,
                    'expected_value' => $attribute->value,
                    'expected_raw_value' => $attribute->rawValue,
                ]);
            }
        }

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-results.active', $asset->id);
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
