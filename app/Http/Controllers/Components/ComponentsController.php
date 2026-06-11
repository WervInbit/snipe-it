<?php

namespace App\Http\Controllers\Components;

use App\Exceptions\ComponentConditionWarningException;
use App\Http\Controllers\Concerns\BuildsComponentWorkflowOptions;
use App\Http\Controllers\Controller;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentInstance;
use App\Services\Components\ComponentExpectedSubcomponentService;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ComponentsController extends Controller
{
    use BuildsComponentWorkflowOptions;

    public function __construct(
        protected ComponentLifecycleService $lifecycle,
        protected ComponentExpectedSubcomponentService $expectedSubcomponents,
    ) {
    }

    public function index(): View
    {
        $this->authorize('view', ComponentInstance::class);

        return view('components.index');
    }

    public function create(): View
    {
        $this->authorize('create', ComponentInstance::class);

        $locations = $this->storageLocationsByType();

        return view('components.create', [
            'componentDefinitions' => $this->activeComponentDefinitions(),
            'stockLocations' => $locations['stock'],
            'sourceTypeOptions' => array_diff_key(
                $this->sourceTypeOptions(),
                [ComponentInstance::SOURCE_EXTRACTED => __('Extracted')]
            ),
            'conditionOptions' => $this->conditionOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComponentInstance::class);

        $data = $request->validate([
            'component_definition_id' => ['nullable', 'integer', 'exists:component_definitions,id'],
            'display_name' => ['required_without:component_definition_id', 'nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:255'],
            'condition_code' => ['required', Rule::in(array_keys($this->conditionOptions()))],
            'storage_location_id' => ['required', 'integer', 'exists:component_storage_locations,id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $component = $this->lifecycle->createInstance([
                'component_definition_id' => $data['component_definition_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'serial' => $data['serial'] ?? null,
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'condition_code' => $data['condition_code'],
                'source_type' => $data['source_type'],
                'storage_location_id' => $data['storage_location_id'],
                'notes' => $data['notes'] ?? null,
            ], $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('components.show', $component)
            ->with('success', __('Component registered.'));
    }

    public function show(ComponentInstance $component_id): View
    {
        $this->authorize('view', $component_id);

        $locations = $this->storageLocationsByType();

        $component = $component_id->load([
            'componentDefinition.category',
            'componentDefinition.manufacturer',
            'componentDefinition.subcomponentTemplates.childComponentDefinition.category',
            'componentDefinition.subcomponentTemplates.childComponentDefinition.manufacturer',
            'childComponents.componentDefinition.category',
            'childComponents.componentDefinition.manufacturer',
            'childComponents.currentAsset.model',
            'childComponents.storageLocation.siteLocation',
            'expectedSubcomponentStates',
            'company',
            'sourceAsset.model',
            'currentAsset.model',
            'storageLocation.siteLocation',
            'heldBy',
            'supplier',
            'createdBy',
            'updatedBy',
            'events.performedBy',
            'events.fromAsset.model',
            'events.toAsset.model',
            'events.fromStorageLocation',
            'events.toStorageLocation',
            'events.relatedWorkOrder',
            'events.relatedWorkOrderTask.workOrder',
            'uploads.adminuser',
        ]);

        $removedExpectedSubcomponents = ComponentInstance::query()
            ->with([
                'componentDefinition.category',
                'componentDefinition.manufacturer',
                'currentAsset.model',
                'storageLocation.siteLocation',
                'heldBy',
            ])
            ->where('source_type', ComponentInstance::SOURCE_EXPECTED_BASELINE)
            ->where('is_materialized_expected', true)
            ->where('ancestry_parent_component_instance_id', $component->id)
            ->whereNull('parent_component_instance_id')
            ->orderByDesc('updated_at')
            ->get();

        return view('components.view', [
            'component' => $component,
            'removedExpectedSubcomponents' => $removedExpectedSubcomponents,
            'installableAssets' => Asset::query()
                ->with(['model', 'assetstatus'])
                ->NotArchived()
                ->orderBy('asset_tag')
                ->get(),
            'editableStorageLocations' => $locations['stock']
                ->concat($locations['verification'])
                ->concat($locations['destruction'])
                ->unique('id')
                ->sortBy(fn ($location) => [$location->type, $location->name, $location->id])
                ->values(),
            'stockLocations' => $locations['stock'],
            'verificationLocations' => $locations['verification'],
            'destructionLocations' => $locations['destruction'],
            'childComponentDefinitions' => $this->activeSubcomponentDefinitions(),
            'conditionOptions' => $this->conditionOptions(),
        ]);
    }

    public function edit(ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('update', $component_id);

        return redirect()
            ->route('components.show', $component_id)
            ->with('info', 'Component editing UI is not implemented yet.');
    }

    public function update(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('update', $component_id);

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'storage_location_note' => ['nullable', 'string'],
        ]);

        if ($request->has('storage_location_id')) {
            $locationId = $data['storage_location_id'] ?? null;

            try {
                $this->lifecycle->updateStorageLocation(
                    $component_id,
                    $locationId ? \App\Models\ComponentStorageLocation::findOrFail($locationId) : null,
                    [
                        'performed_by' => $request->user(),
                        'note' => $data['storage_location_note'] ?? null,
                    ]
                );
            } catch (InvalidArgumentException $exception) {
                return redirect()->back()->withInput()->with('error', $exception->getMessage());
            }

            return redirect()
                ->route('components.show', ['component_id' => $component_id])
                ->with('success', __('Component storage location updated.'));
        }

        $notes = trim((string) ($data['notes'] ?? ''));

        $component_id->forceFill([
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $request->user()->id,
        ])->save();

        return redirect()
            ->route('components.show', ['component_id' => $component_id])
            ->with('success', __('Component note updated.'));
    }

    public function materializeExpectedSubcomponent(Request $request, ComponentInstance $component_id, ComponentDefinitionSubcomponentTemplate $template): RedirectResponse
    {
        $this->authorize('view', $component_id);
        $this->authorize('install', new ComponentInstance());

        $data = $request->validate([
            'condition_warning_confirmed' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->expectedSubcomponents->materializeAttachedChild($component_id, $template, $request->user(), [
                'condition_warning_confirmed' => $request->boolean('condition_warning_confirmed'),
                'note' => $data['note'] ?? null,
            ]);
        } catch (ComponentConditionWarningException $exception) {
            return redirect()->back()->withInput()->with('warning', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('components.show', ['component_id' => $component_id])
            ->with('success', __('Expected subcomponent tracked.'));
    }

    public function storeChild(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('view', $component_id);
        $this->authorize('create', ComponentInstance::class);
        $this->authorize('install', new ComponentInstance());

        $data = $request->validate([
            'creation_mode' => ['required', Rule::in(['definition', 'custom'])],
            'component_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('placement_mode', ComponentDefinition::subcomponentPlacementModes())
                ),
                Rule::requiredIf(fn () => $request->input('creation_mode') === 'definition'),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->input('creation_mode') === 'custom'),
            ],
            'serial' => ['nullable', 'string', 'max:255'],
            'condition_code' => ['required', Rule::in(array_keys($this->conditionOptions()))],
            'condition_warning_confirmed' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['creation_mode'] === 'custom') {
            $data['component_definition_id'] = null;
        }

        try {
            $component_id->loadMissing('currentAsset');

            if ($component_id->parent_component_instance_id) {
                throw new InvalidArgumentException('Child components cannot have their own child components.');
            }

            if ($component_id->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_ATTACHED || !$component_id->current_asset_id) {
                throw new InvalidArgumentException('Child components can only be added while the parent component is installed.');
            }

            $this->lifecycle->assertConditionWarningConfirmedForCondition(
                ComponentInstance::conditionStatusForConditionCode($data['condition_code']),
                ['condition_warning_confirmed' => $request->boolean('condition_warning_confirmed')],
                $data['display_name'] ?? 'Child component',
            );

            $this->lifecycle->createInstance([
                'component_definition_id' => $data['component_definition_id'] ?? null,
                'company_id' => $component_id->company_id,
                'source_type' => ComponentInstance::SOURCE_MANUAL,
                'source_asset_id' => $component_id->current_asset_id,
                'current_asset_id' => $component_id->current_asset_id,
                'parent_component_instance_id' => $component_id->id,
                'root_asset_id' => $component_id->root_asset_id ?: $component_id->current_asset_id,
                'status' => ComponentInstance::STATUS_INSTALLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'condition_code' => $data['condition_code'],
                'display_name' => $data['display_name'] ?? null,
                'serial' => $data['serial'] ?? null,
                'notes' => $data['note'] ?? null,
                'metadata_json' => [
                    'manual_child_component' => true,
                    'parent_component_instance_id' => $component_id->id,
                    'materialized_from_asset_id' => $component_id->current_asset_id,
                ],
            ], $request->user());
        } catch (ComponentConditionWarningException $exception) {
            return redirect()->back()->withInput()->with('warning', $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('components.show', ['component_id' => $component_id])
            ->with('success', __('Child component created.'));
    }

    public function destroy(ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('delete', $component_id);

        if ($component_id->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_ATTACHED) {
            return redirect()
                ->route('components.show', $component_id)
                ->with('error', 'Installed components must be removed before deletion.');
        }

        $logAction = new Actionlog();
        $logAction->item_type = ComponentInstance::class;
        $logAction->item_id = $component_id->id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();
        $logAction->logaction('delete');

        $component_id->delete();

        return redirect()
            ->route('components.index')
            ->with('success', 'Component deleted.');
    }
}
