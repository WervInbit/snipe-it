<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\WorkflowEvidencePhotoService;
use App\Services\WorkflowProgressionService;
use App\Services\WorkflowRunDefinitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TestRunController extends Controller
{
    public function index(Asset $asset, WorkflowProgressionService $progression)
    {
        $this->authorize('view', $asset);
        $runs = $asset->tests()
            ->with(['profile', 'results.type', 'results.attributeDefinition', 'results.photos', 'user', 'guardOverrideUser'])
            ->orderByDesc('created_at')
            ->get();
        $manualWorkflowItems = TestType::query()
            ->ordered()
            ->get();
        $workflowProgression = $progression->forAsset($asset);
        $canStartRun = Gate::allows('tests.execute') && Gate::allows('view', $asset);
        $canOverrideWorkflowGuards = Gate::allows('tests.override_dependencies');
        $canStartNewRun = Gate::allows('tests.start_new_run');

        return view('tests.index', compact(
            'asset',
            'runs',
            'manualWorkflowItems',
            'workflowProgression',
            'canStartRun',
            'canOverrideWorkflowGuards',
            'canStartNewRun'
        ));
    }

    public function store(
        Request $request,
        Asset $asset,
        WorkflowRunDefinitionService $definitions,
        WorkflowProgressionService $progression
    ): RedirectResponse
    {
        Gate::authorize('tests.execute');
        $this->authorize('view', $asset);

        $data = $request->validate([
            'workflow_profile_id' => ['required', 'integer', 'exists:workflow_profiles,id'],
            'extra_workflow_item_ids' => ['nullable', 'array'],
            'extra_workflow_item_ids.*' => ['integer', 'exists:workflow_items,id'],
            'confirm_workflow_override' => ['nullable', 'boolean'],
            'workflow_override_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $outcome = DB::transaction(function () use (
            $asset,
            $data,
            $definitions,
            $progression,
            $request
        ): array {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $profile = WorkflowProfile::query()
                ->active()
                ->forAsset($lockedAsset)
                ->whereHas('items')
                ->whereKey($data['workflow_profile_id'])
                ->first();

            if (!$profile) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('Choose an active workflow profile before starting a workflow.'),
                ]);
            }

            Gate::authorize($profile->executionAbility());

            $decision = $progression->forProfile($lockedAsset, $profile);

            if (!$decision) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('This workflow is not applicable to this asset.'),
                ]);
            }

            if ($decision['can_continue']) {
                return ['run' => $decision['run'], 'continued' => true];
            }

            if ($decision['repeat_forbidden']) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('This workflow has already been completed and its repeat policy does not allow another run.'),
                ]);
            }

            $overrideTypes = collect([
                $decision['dependency_override_required'] ? 'dependencies' : null,
                $decision['repeat_override_required'] ? 'repeat' : null,
            ])->filter()->values();

            if ($overrideTypes->contains('dependencies') && !Gate::allows('tests.override_dependencies')) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('This workflow is waiting for required work. A Supervisor or Administrator must confirm an override.'),
                ]);
            }

            if ($overrideTypes->contains('repeat') && !Gate::allows('tests.start_new_run')) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('You can edit the existing run, but only a Supervisor or Administrator may start another run.'),
                ]);
            }

            if ($overrideTypes->isNotEmpty()) {
                if (!$request->boolean('confirm_workflow_override')) {
                    throw ValidationException::withMessages([
                        'confirm_workflow_override' => __('Confirm that you want to ignore the workflow guard.'),
                    ]);
                }

                if (trim((string) ($data['workflow_override_reason'] ?? '')) === '') {
                    throw ValidationException::withMessages([
                        'workflow_override_reason' => __('Enter a reason for overriding the workflow guard.'),
                    ]);
                }
            }

            $definition = $definitions->forProfile($lockedAsset, $profile);
            $resolved = $definition['resolved_attributes'];
            $missing = $resolved->filter(function ($attribute) {
                return $attribute->definition->required_for_category && $attribute->value === null;
            });

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'attributes' => __('Complete the model specification before starting a test run. Missing: :list', [
                        'list' => $missing->map(fn ($attribute) => $attribute->definition->label)->implode(', '),
                    ]),
                ]);
            }

            $profileItems = $definition['profile_items'];
            $resolvedByDefinition = $definition['resolved_by_definition'];
            $extraItems = TestType::query()
                ->whereIn('id', array_values($data['extra_workflow_item_ids'] ?? []))
                ->ordered()
                ->get()
                ->reject(fn (TestType $item) => $profileItems->contains('workflow_item_id', $item->id))
                ->values();

            if ($profileItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'workflow_profile_id' => __('This workflow profile has no applicable items for this asset.'),
                ]);
            }

            $run = new TestRun();
            $run->asset()->associate($lockedAsset);
            $run->user()->associate($request->user());
            $run->model_number_id = $lockedAsset->getAttribute('model_number_id');
            $run->workflow_profile_id = $profile->id;
            $run->profile_name_snapshot = $profile->name;
            $run->profile_slug_snapshot = $profile->slug;
            $run->profile_display_order_snapshot = $profile->display_order;
            $run->readiness_context_hash = $definition['readiness_context_hash'];
            $run->prerequisite_snapshot = $decision['prerequisites']->all();
            $run->started_at = now();

            if ($overrideTypes->isNotEmpty()) {
                $run->guard_override_by = $request->user()->id;
                $run->guard_override_reason = trim((string) $data['workflow_override_reason']);
                $run->guard_override_at = now();
                $run->guard_override_details = [
                    'types' => $overrideTypes->all(),
                    'blockers' => $decision['blockers']->all(),
                    'previous_run_id' => $decision['run']?->id,
                ];
            }

            $run->save();

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
                    'is_required' => $profileItem->is_required,
                    'result_label_mode' => $profileItem->result_label_mode
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

            $lockedAsset->refreshTestCompletionFlag();

            return ['run' => $run, 'continued' => false];
        });

        /** @var TestRun $run */
        $run = $outcome['run'];

        return redirect()->route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]);
    }

    public function destroy(
        Asset $asset,
        TestRun $testRun,
        WorkflowEvidencePhotoService $workflowEvidencePhotos
    )
    {
        $this->authorize('delete', $testRun);
        abort_unless($testRun->asset_id === $asset->id, 404);

        $evidencePaths = $testRun->results()
            ->with('photos:id,workflow_result_id,path')
            ->get()
            ->flatMap(fn (TestResult $result) => $result->photos->pluck('path'))
            ->filter()
            ->unique()
            ->values();

        $testRun->delete();

        foreach ($evidencePaths as $path) {
            $workflowEvidencePhotos->delete($path);
        }

        $asset->refreshTestCompletionFlag();
        return redirect()->route('test-runs.index', $asset->id)
            ->with('success', trans('general.deleted'));
    }
}
