<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
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
            ->with(['profile', 'results.type', 'results.attributeDefinition', 'results.photos', 'user'])
            ->orderByDesc('created_at')
            ->get();
        $workflowProfiles = WorkflowProfile::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->withCount('items')
            ->ordered()
            ->get();
        $manualWorkflowItems = TestType::query()
            ->ordered()
            ->get();

        return view('tests.index', compact('asset', 'runs', 'workflowProfiles', 'manualWorkflowItems'));
    }

    public function store(Request $request, Asset $asset, EffectiveAttributeResolver $resolver): RedirectResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('view', $asset);

        $data = $request->validate([
            'workflow_profile_id' => ['required', 'integer', 'exists:workflow_profiles,id'],
            'extra_workflow_item_ids' => ['nullable', 'array'],
            'extra_workflow_item_ids.*' => ['integer', 'exists:workflow_items,id'],
        ]);

        $profile = WorkflowProfile::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->whereKey($data['workflow_profile_id'])
            ->first();

        if (!$profile) {
            return redirect()
                ->route('test-runs.index', $asset->id)
                ->withErrors([
                    'workflow_profile_id' => __('Choose an active workflow profile before starting a workflow.'),
                ]);
        }

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
        $run->workflow_profile_id = $profile->id;
        $run->profile_name_snapshot = $profile->name;
        $run->profile_slug_snapshot = $profile->slug;
        $run->started_at = now();
        $run->save();

        $resolvedByDefinition = $resolved->keyBy(fn ($attribute) => $attribute->definition->id);
        $applicableItemIds = TestType::forAsset($asset)->pluck('id')->all();
        $profile->load(['items.item.attributeDefinition']);
        $profileItems = $profile->items
            ->filter(fn ($profileItem) => $profileItem->item && in_array($profileItem->workflow_item_id, $applicableItemIds, true))
            ->values();
        $extraItems = TestType::query()
            ->whereIn('id', array_values($data['extra_workflow_item_ids'] ?? []))
            ->ordered()
            ->get()
            ->reject(fn (TestType $item) => $profileItems->contains('workflow_item_id', $item->id))
            ->values();

        if ($profileItems->isEmpty() && $extraItems->isEmpty()) {
            $run->delete();

            return redirect()
                ->route('test-runs.index', $asset->id)
                ->withErrors([
                    'workflow_profile_id' => __('This workflow profile has no applicable items for this asset.'),
                ]);
        }

        foreach ($profileItems as $profileItem) {
            $testType = $profileItem->item;
            $attribute = null;

            if ($testType->attribute_definition_id) {
                $attribute = $resolvedByDefinition->get($testType->attribute_definition_id);

                if (!$attribute) {
                    continue;
                }
            }

            $run->results()->create([
                'workflow_item_id' => $testType->id,
                'workflow_profile_item_id' => $profileItem->id,
                'attribute_definition_id' => $testType->attribute_definition_id,
                'status' => TestResult::STATUS_NVT,
                'note' => null,
                'expected_value' => $attribute?->value,
                'expected_raw_value' => $attribute?->rawValue,
                'is_required' => $testType->is_required,
                'result_label_mode' => $testType->result_label_mode
                    ?: $profileItem->result_label_mode
                    ?: WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                'sort_order' => $profileItem->sort_order,
            ]);
        }

        $extraSortOrder = (int) ($profileItems->max('sort_order') ?? -1) + 1;
        foreach ($extraItems as $index => $testType) {
            $attribute = $testType->attribute_definition_id
                ? $resolvedByDefinition->get($testType->attribute_definition_id)
                : null;

            $run->results()->create([
                'workflow_item_id' => $testType->id,
                'workflow_profile_item_id' => null,
                'attribute_definition_id' => $testType->attribute_definition_id,
                'status' => TestResult::STATUS_NVT,
                'note' => null,
                'expected_value' => $attribute?->value,
                'expected_raw_value' => $attribute?->rawValue,
                'is_required' => $testType->is_required,
                'result_label_mode' => $testType->result_label_mode ?: WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                'sort_order' => $extraSortOrder + $index,
            ]);
        }

        $asset->refreshTestCompletionFlag();

        return redirect()->route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]);
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
