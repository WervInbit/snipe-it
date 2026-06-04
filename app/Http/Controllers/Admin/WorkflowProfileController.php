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
use Illuminate\View\View;

class WorkflowProfileController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);

        $profiles = WorkflowProfile::query()
            ->with([
                'categories',
            ])
            ->withCount('items')
            ->ordered()
            ->get();

        $categories = Category::query()
            ->where('category_type', 'asset')
            ->orderBy('name')
            ->get();

        return view('settings.workflow-profiles', compact('profiles', 'categories'));
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

        [$data, $categoryIds] = $this->validatedProfileData($request);

        $profile = DB::transaction(function () use ($data, $categoryIds): WorkflowProfile {
            if ($data['is_default']) {
                WorkflowProfile::query()->update(['is_default' => false]);
            }

            $profile = WorkflowProfile::create($data);
            $profile->categories()->sync($categoryIds);

            return $profile;
        });

        return redirect()
            ->route('settings.workflow-profiles.index')
            ->with('success', __('Workflow profile ":name" was created.', ['name' => $profile->name]));
    }

    public function update(Request $request, WorkflowProfile $workflowProfile): RedirectResponse
    {
        $this->authorize('update', TestType::class);

        [$data, $categoryIds] = $this->validatedProfileData($request, $workflowProfile);

        DB::transaction(function () use ($workflowProfile, $data, $categoryIds): void {
            if ($data['is_default']) {
                WorkflowProfile::query()
                    ->whereKeyNot($workflowProfile->id)
                    ->update(['is_default' => false]);
            }

            $workflowProfile->update($data);
            $workflowProfile->categories()->sync($categoryIds);
        });

        return redirect()
            ->route('settings.workflow-profiles.index')
            ->with('success', __('Workflow profile ":name" was updated.', ['name' => $workflowProfile->name]));
    }

    public function destroy(WorkflowProfile $workflowProfile): RedirectResponse
    {
        $this->authorize('delete', TestType::class);

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

    /**
     * @return array{0: array<string, mixed>, 1: array<int>}
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
        ];

        return [$data, array_values($validated['category_ids'] ?? [])];
    }
}
