<?php

namespace App\Http\Controllers\Components;

use App\Http\Controllers\Concerns\BuildsComponentWorkflowOptions;
use App\Http\Controllers\Controller;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ComponentsController extends Controller
{
    use BuildsComponentWorkflowOptions;

    public function __construct(
        protected ComponentLifecycleService $lifecycle,
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
            'condition_code' => ['required', 'string', 'max:255'],
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

        return view('components.view', [
            'component' => $component,
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

    public function destroy(ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('delete', $component_id);

        if ($component_id->status === ComponentInstance::STATUS_INSTALLED) {
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
