<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\Manufacturer;
use App\Services\Components\ComponentDefinitionHierarchyWarningService;
use App\Services\Components\ComponentDefinitionSubcomponentTemplateManager;
use App\Services\ModelAttributes\ComponentDefinitionAttributeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComponentDefinitionSettingsController extends Controller
{
    public function __construct(
        private readonly ComponentDefinitionAttributeManager $attributeManager,
        private readonly ComponentDefinitionSubcomponentTemplateManager $subcomponentTemplateManager,
        private readonly ComponentDefinitionHierarchyWarningService $hierarchyWarningService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('manage', ComponentDefinition::class);

        $search = trim((string) $request->input('search'));

        $definitions = ComponentDefinition::query()
            ->with(['category', 'manufacturer'])
            ->withCount(['instances', 'expectedTemplates'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('part_code', 'like', "%{$search}%")
                        ->orWhere('model_number', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('manufacturer', fn ($manufacturerQuery) => $manufacturerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('settings.component_definitions.index', compact('definitions', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', ComponentDefinition::class);

        return view('settings.component_definitions.create', [
            'item' => new ComponentDefinition(),
            ...$this->formOptions(),
        ]);
    }

    public function edit(ComponentDefinition $componentDefinition): View
    {
        $this->authorize('update', $componentDefinition);

        $componentDefinition->load([
            'attributeContributions.definition.options',
            'attributeContributions.option',
            'subcomponentTemplates.childComponentDefinition.category',
            'subcomponentTemplates.childComponentDefinition.manufacturer',
            'subcomponentTemplates.childComponentDefinition.attributeContributions.definition',
        ]);

        return view('settings.component_definitions.edit', [
            'item' => $componentDefinition,
            'componentDefinition' => $componentDefinition,
            'hierarchyOverlapWarnings' => $this->hierarchyWarningService->overlapWarnings($componentDefinition),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComponentDefinition::class);

        $data = $this->validatedData($request);
        $definition = DB::transaction(function () use ($data, $request) {
            $definition = new ComponentDefinition($data);
            $definition->created_by = $request->user()?->id;
            $definition->updated_by = $request->user()?->id;
            $definition->save();
            $this->attributeManager->sync($definition, $request->input('attribute_contributions', []));
            $this->subcomponentTemplateManager->sync($definition, $request->input('expected_subcomponents', []));

            return $definition;
        });

        return redirect()
            ->route('settings.component_definitions.edit', $definition)
            ->with('success', __('Component definition created.'));
    }

    public function update(Request $request, ComponentDefinition $componentDefinition): RedirectResponse
    {
        $this->authorize('update', $componentDefinition);

        if ($this->requiresLifecycleChange($request, $componentDefinition)) {
            $this->authorize('manageLifecycle', $componentDefinition);
        }

        $data = $this->validatedData($request);
        DB::transaction(function () use ($request, $componentDefinition, $data): void {
            $componentDefinition->fill($data);
            $componentDefinition->updated_by = $request->user()?->id;
            $componentDefinition->save();
            $this->attributeManager->sync($componentDefinition, $request->input('attribute_contributions', []));
            $this->subcomponentTemplateManager->sync($componentDefinition, $request->input('expected_subcomponents', []));
        });

        return redirect()
            ->route('settings.component_definitions.index')
            ->with('success', __('Component definition updated.'));
    }

    public function deactivate(ComponentDefinition $componentDefinition): RedirectResponse
    {
        $this->authorize('manageLifecycle', $componentDefinition);

        $componentDefinition->forceFill([
            'is_active' => false,
            'updated_by' => request()->user()?->id,
        ])->save();

        return redirect()
            ->route('settings.component_definitions.index')
            ->with('success', __('Component definition deactivated.'));
    }

    public function activate(ComponentDefinition $componentDefinition): RedirectResponse
    {
        $this->authorize('manageLifecycle', $componentDefinition);

        $componentDefinition->forceFill([
            'is_active' => true,
            'updated_by' => request()->user()?->id,
        ])->save();

        return redirect()
            ->route('settings.component_definitions.index')
            ->with('success', __('Component definition activated.'));
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'part_code' => ['nullable', 'string', 'max:255'],
            'spec_summary' => ['nullable', 'string'],
            'spec_display_label' => ['nullable', 'string', 'max:255'],
            'serial_tracking_mode' => ['nullable', Rule::in(['optional', 'required', 'not_tracked'])],
            'placement_mode' => ['nullable', Rule::in(ComponentDefinition::placementModes())],
            'is_active' => ['sometimes', 'boolean'],
            'expected_subcomponents' => ['nullable', 'array'],
            'expected_subcomponents.*.id' => ['nullable', 'integer'],
            'expected_subcomponents.*.child_component_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query->whereIn('placement_mode', ComponentDefinition::subcomponentPlacementModes())
                ),
            ],
            'expected_subcomponents.*.expected_name' => ['nullable', 'string', 'max:255'],
            'expected_subcomponents.*.expected_qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'expected_subcomponents.*.is_required' => ['nullable', 'boolean'],
            'expected_subcomponents.*.notes' => ['nullable', 'string'],
            'attribute_contributions' => ['nullable', 'array'],
            'attribute_contributions.*.attribute_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('attribute_definitions', 'id')->where(
                    fn ($query) => $query
                        ->whereNull('deleted_at')
                        ->whereNull('deprecated_at')
                        ->whereNull('hidden_at')
                ),
            ],
            'attribute_contributions.*.attribute_search' => ['nullable', 'string', 'max:255'],
            'attribute_contributions.*.value' => ['nullable'],
            'attribute_contributions.*.resolves_to_spec' => ['nullable', 'boolean'],
            'attribute_contributions.*.include_in_component_label' => ['nullable', 'boolean'],
        ]) + [
            'serial_tracking_mode' => $request->input('serial_tracking_mode', 'optional'),
            'placement_mode' => $request->input('placement_mode', ComponentDefinition::PLACEMENT_EITHER),
        ];

        $data['spec_display_label'] = trim((string) ($data['spec_display_label'] ?? '')) ?: null;

        return $data;
    }

    protected function formOptions(): array
    {
        return [
            'categories' => Category::query()->where('category_type', 'component')->orderBy('name')->pluck('name', 'id'),
            'manufacturers' => Manufacturer::query()->orderBy('name')->pluck('name', 'id'),
            'componentDefinitions' => ComponentDefinition::query()
                ->with(['category', 'manufacturer', 'attributeContributions.definition'])
                ->whereIn('placement_mode', ComponentDefinition::subcomponentPlacementModes())
                ->orderBy('name')
                ->get(),
            'attributeDefinitions' => AttributeDefinition::query()
                ->current()
                ->with('options')
                ->orderBy('label')
                ->get(),
        ];
    }

    private function requiresLifecycleChange(Request $request, ComponentDefinition $componentDefinition): bool
    {
        if ($request->has('is_active') && $request->boolean('is_active') !== (bool) $componentDefinition->is_active) {
            return true;
        }

        $submittedAttributeIds = collect($request->input('attribute_contributions', []))
            ->filter(fn ($row) => is_array($row))
            ->pluck('attribute_definition_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingAttributeIds = $componentDefinition->attributeContributions()
            ->pluck('attribute_definition_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (array_diff($existingAttributeIds, $submittedAttributeIds) !== []) {
            return true;
        }

        $submittedTemplateIds = collect($request->input('expected_subcomponents', []))
            ->filter(fn ($row) => is_array($row))
            ->pluck('id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingTemplateIds = $componentDefinition->subcomponentTemplates()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_diff($existingTemplateIds, $submittedTemplateIds) !== [];
    }
}
