<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowProfileController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);

        $profiles = WorkflowProfile::query()
            ->with([
                'categories',
                'prerequisites',
            ])
            ->withCount('items')
            ->ordered()
            ->get();

        $categories = Category::query()
            ->where('category_type', 'asset')
            ->orderBy('name')
            ->get();

        return view('settings.workflow-profiles', [
            'profiles' => $profiles,
            'categories' => $categories,
            'dependencyProfiles' => $profiles,
        ]);
    }

    public function editItems(WorkflowProfile $workflowProfile): View
    {
        $this->authorize('update', TestType::class);

        $workflowProfile->load([
            'categories',
            'items.item.attributeDefinition',
            'items.item.categories',
            'items.item.componentCategories',
            'items.item.componentDefinitions',
        ]);

        $workflowItems = TestType::query()
            ->with(['attributeDefinition', 'categories', 'componentCategories', 'componentDefinitions'])
            ->ordered()
            ->get();

        return view('settings.workflow-profile-items', compact('workflowProfile', 'workflowItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TestType::class);

        [$data, $categoryIds, $dependencies] = $this->validatedProfileData($request);

        $profile = DB::transaction(function () use ($data, $categoryIds, $dependencies): WorkflowProfile {
            WorkflowProfile::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);
            $this->ensureDependenciesAreActive($dependencies);

            if ($data['is_default']) {
                WorkflowProfile::query()->update(['is_default' => false]);
            }

            $profile = WorkflowProfile::create($data);
            $profile->categories()->sync($categoryIds);
            $profile->prerequisites()->sync($dependencies);

            return $profile;
        });

        return redirect()
            ->route('settings.workflow-profiles.index')
            ->with('success', __('Workflow profile ":name" was created.', ['name' => $profile->name]));
    }

    public function update(Request $request, WorkflowProfile $workflowProfile): RedirectResponse
    {
        $this->authorize('update', TestType::class);

        [$data, $categoryIds, $dependencies] = $this->validatedProfileData($request, $workflowProfile);

        DB::transaction(function () use ($workflowProfile, $data, $categoryIds, $dependencies): void {
            WorkflowProfile::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $this->ensureDependenciesAreActive($dependencies);
            $this->ensureDependenciesAreAcyclic($workflowProfile, $dependencies);

            if (!$data['is_active'] && $workflowProfile->dependents()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => __('Disconnect dependent workflows before making this prerequisite inactive.'),
                ]);
            }

            if ($data['is_default']) {
                WorkflowProfile::query()
                    ->whereKeyNot($workflowProfile->id)
                    ->update(['is_default' => false]);
            }

            $workflowProfile->update($data);
            $workflowProfile->categories()->sync($categoryIds);
            $workflowProfile->prerequisites()->sync($dependencies);
        });

        return redirect()
            ->route('settings.workflow-profiles.index')
            ->with('success', __('Workflow profile ":name" was updated.', ['name' => $workflowProfile->name]));
    }

    public function destroy(WorkflowProfile $workflowProfile): RedirectResponse
    {
        $this->authorize('delete', TestType::class);

        if ($workflowProfile->dependents()->exists()) {
            return redirect()
                ->route('settings.workflow-profiles.index')
                ->withErrors([
                    'workflow_profile' => __('Disconnect this workflow from its dependent workflows before deleting it.'),
                ]);
        }

        $workflowProfile->delete();

        return redirect()
            ->route('settings.workflow-profiles.index')
            ->with('success', __('Workflow profile was deleted.'));
    }

    public function updateItems(Request $request, WorkflowProfile $workflowProfile): RedirectResponse
    {
        $this->authorize('update', TestType::class);

        $data = $request->validate([
            'items' => ['array'],
            'items.*.enabled' => ['nullable', 'boolean'],
            'items.*.remove' => ['nullable', 'boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.result_label_mode' => ['nullable', Rule::in(WorkflowProfileItem::LABEL_MODES)],
        ]);

        $payloads = $data['items'] ?? [];
        $itemIds = collect(array_keys($payloads))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $items = TestType::query()
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $enabledItemIds = collect();

        DB::transaction(function () use ($workflowProfile, $payloads, $items, $enabledItemIds): void {
            foreach ($payloads as $itemId => $payload) {
                $itemId = (int) $itemId;

                if (!$items->has($itemId) || empty($payload['enabled'])) {
                    continue;
                }

                if (!empty($payload['remove'])) {
                    continue;
                }

                $item = $items->get($itemId);
                $enabledItemIds->push($itemId);

                WorkflowProfileItem::updateOrCreate(
                    [
                        'workflow_profile_id' => $workflowProfile->id,
                        'workflow_item_id' => $itemId,
                    ],
                    [
                        'sort_order' => (int) ($payload['sort_order'] ?? 0),
                        'is_required' => array_key_exists('is_required', $payload)
                            ? !empty($payload['is_required'])
                            : (bool) $item->is_required,
                        'result_label_mode' => $payload['result_label_mode']
                            ?? $item->result_label_mode
                            ?? WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    ]
                );
            }

            $workflowProfile->items()
                ->when(
                    $enabledItemIds->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('workflow_item_id', $enabledItemIds->all()),
                    fn ($query) => $query
                )
                ->delete();
        });

        return redirect()
            ->route('settings.workflow-profiles.items.edit', $workflowProfile)
            ->with('success', __('Workflow profile items were updated.'));
    }

    public function reorderItems(Request $request, WorkflowProfile $workflowProfile): JsonResponse
    {
        $this->authorize('update', TestType::class);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $order = array_values(array_map('intval', $data['order']));
        $profileItems = $workflowProfile->items()
            ->whereIn('id', $order)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($order, $profileItems): void {
            foreach ($order as $position => $id) {
                /** @var WorkflowProfileItem|null $profileItem */
                $profileItem = $profileItems->get($id);
                if (!$profileItem || (int) $profileItem->sort_order === $position) {
                    continue;
                }

                $profileItem->sort_order = $position;
                $profileItem->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('update', TestType::class);

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct', Rule::exists('workflow_profiles', 'id')],
        ]);
        $order = array_values(array_map('intval', $data['order']));

        DB::transaction(function () use ($order): void {
            $profiles = WorkflowProfile::query()
                ->ordered()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $completeOrder = collect($order)
                ->concat($profiles->keys()->map(fn ($id): int => (int) $id)->diff($order))
                ->values();

            foreach ($completeOrder as $position => $id) {
                /** @var WorkflowProfile $profile */
                $profile = $profiles->get($id);

                if ((int) $profile->display_order === $position) {
                    continue;
                }

                $profile->display_order = $position;
                $profile->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int>, 2: array<int>}
     */
    private function validatedProfileData(Request $request, ?WorkflowProfile $profile = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('category_type', 'asset')],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'blocks_sale_readiness' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'repeat_policy' => ['nullable', Rule::in(WorkflowProfile::REPEAT_POLICIES)],
            'execution_level' => ['nullable', Rule::in(WorkflowProfile::EXECUTION_LEVELS)],
            'dependency_profile_ids' => ['nullable', 'array'],
            'dependency_profile_ids.*' => ['integer', 'distinct', Rule::exists('workflow_profiles', 'id')],
        ]);

        $manualSlug = trim((string) ($validated['slug'] ?? ''));
        $slugSource = $manualSlug !== '' ? $manualSlug : $validated['name'];
        $isDefault = $request->boolean('is_default');

        $data = [
            'name' => $validated['name'],
            'slug' => WorkflowProfile::generateUniqueSlug($slugSource, $profile?->id),
            'description' => $validated['description'] ?? null,
            'is_active' => $isDefault || $request->boolean('is_active'),
            'is_default' => $isDefault,
            'blocks_sale_readiness' => $request->boolean('blocks_sale_readiness'),
            'display_order' => isset($validated['display_order'])
                ? (int) $validated['display_order']
                : (((int) WorkflowProfile::query()->max('display_order')) + 1),
            'repeat_policy' => $validated['repeat_policy']
                ?? $profile?->repeat_policy
                ?? WorkflowProfile::REPEAT_OVERRIDE_REQUIRED,
            'execution_level' => $validated['execution_level']
                ?? $profile?->execution_level
                ?? WorkflowProfile::EXECUTION_OPERATOR,
        ];

        $enabledDependencyIds = collect($validated['dependency_profile_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($profile && $enabledDependencyIds->contains((int) $profile->id)) {
            throw ValidationException::withMessages([
                'dependency_profile_ids' => __('A workflow cannot depend on itself.'),
            ]);
        }

        $existingDependencies = WorkflowProfile::query()
            ->whereIn('id', $enabledDependencyIds)
            ->get(['id', 'is_active']);
        $existingDependencyIds = $existingDependencies->pluck('id')->map(fn ($id): int => (int) $id);

        if ($existingDependencyIds->count() !== $enabledDependencyIds->count()) {
            throw ValidationException::withMessages([
                'dependency_profile_ids' => __('One or more selected workflow dependencies no longer exist.'),
            ]);
        }

        if ($existingDependencies->contains(fn (WorkflowProfile $dependency): bool => !$dependency->is_active)) {
            throw ValidationException::withMessages([
                'dependency_profile_ids' => __('Inactive workflows cannot be selected as prerequisites.'),
            ]);
        }

        $dependencies = $enabledDependencyIds->all();

        return [$data, array_values($validated['category_ids'] ?? []), $dependencies];
    }

    /**
     * Reject dependency edits that would make a workflow depend on itself indirectly.
     *
     * @param array<int> $proposedPrerequisiteIds
     */
    private function ensureDependenciesAreAcyclic(
        WorkflowProfile $profile,
        array $proposedPrerequisiteIds
    ): void {
        $adjacency = DB::table('workflow_profile_dependencies')
            ->get(['workflow_profile_id', 'prerequisite_workflow_profile_id'])
            ->groupBy('workflow_profile_id')
            ->map(fn ($rows) => $rows
                ->pluck('prerequisite_workflow_profile_id')
                ->map(fn ($id): int => (int) $id)
                ->all())
            ->all();

        $profileId = (int) $profile->id;
        $adjacency[$profileId] = array_values(array_map('intval', $proposedPrerequisiteIds));

        $reachesProfile = function (int $node, array $visited = []) use (&$reachesProfile, $adjacency, $profileId): bool {
            if ($node === $profileId) {
                return true;
            }

            if (isset($visited[$node])) {
                return false;
            }

            $visited[$node] = true;

            foreach ($adjacency[$node] ?? [] as $prerequisiteId) {
                if ($reachesProfile((int) $prerequisiteId, $visited)) {
                    return true;
                }
            }

            return false;
        };

        foreach ($proposedPrerequisiteIds as $prerequisiteId) {
            if ($reachesProfile((int) $prerequisiteId)) {
                throw ValidationException::withMessages([
                    'dependency_profile_ids' => __('This dependency would create a circular workflow chain.'),
                ]);
            }
        }
    }

    /**
     * Recheck after the graph-wide row lock so a concurrent deactivation
     * cannot create a silently waived dependency.
     *
     * @param array<int> $dependencyIds
     */
    private function ensureDependenciesAreActive(array $dependencyIds): void
    {
        if ($dependencyIds === []) {
            return;
        }

        if (WorkflowProfile::query()->whereIn('id', $dependencyIds)->where('is_active', false)->exists()) {
            throw ValidationException::withMessages([
                'dependency_profile_ids' => __('Inactive workflows cannot be selected as prerequisites.'),
            ]);
        }
    }
}
