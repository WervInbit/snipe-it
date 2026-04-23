<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Concerns\BuildsComponentWorkflowOptions;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\ModelNumberComponentTemplate;
use App\Services\Components\AssetExpectedComponentService;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AssetComponentsController extends Controller
{
    use BuildsComponentWorkflowOptions;

    public function __construct(
        protected ComponentLifecycleService $lifecycle,
        protected AssetExpectedComponentService $expectedComponents,
    ) {
    }

    public function add(Request $request, Asset $asset): View
    {
        $this->authorize('view', $asset);

        return view('components.asset-add', [
            'asset' => $asset,
            'trayComponents' => $this->trayComponents($request),
            'stockComponents' => $this->stockInstallableComponents(),
            'componentDefinitions' => $this->activeComponentDefinitions(),
        ]);
    }

    public function install(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);

        $data = $request->validate([
            'component_id' => ['required', 'integer', 'exists:component_instances,id'],
        ]);

        $component = ComponentInstance::findOrFail($data['component_id']);
        $this->authorize('install', $component);

        if (
            ($component->status === ComponentInstance::STATUS_IN_TRANSFER)
            || (
                in_array($component->status, [
                    ComponentInstance::STATUS_IN_STOCK,
                    ComponentInstance::STATUS_NEEDS_VERIFICATION,
                ], true)
                && !$component->current_asset_id
            )
        ) {
            try {
                $this->lifecycle->installIntoAsset($component, $asset, [
                    'performed_by' => $request->user(),
                ]);
            } catch (InvalidArgumentException $exception) {
                return redirect()->back()->withInput()->with('error', $exception->getMessage());
            }

            return redirect()->route('hardware.show', $asset)->with('success', __('Component installed.'));
        }

        return redirect()->back()->withInput()->with('error', __('Only tray or storage components can be installed through this workflow.'));
    }

    public function installFromTray(Request $request, Asset $asset): RedirectResponse
    {
        return $this->install($request, $asset);
    }

    public function installExisting(Request $request, Asset $asset): RedirectResponse
    {
        return $this->install($request, $asset);
    }

    public function register(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('create', ComponentInstance::class);
        $this->authorize('install', new ComponentInstance());

        $data = $request->validate([
            'creation_mode' => ['required', Rule::in(['definition', 'custom'])],
            'component_definition_id' => [
                'nullable',
                'integer',
                'exists:component_definitions,id',
                Rule::requiredIf(fn () => $request->input('creation_mode') === 'definition'),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->input('creation_mode') === 'custom'),
            ],
            'serial' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['creation_mode'] === 'custom') {
            $data['component_definition_id'] = null;
        }

        try {
            $component = $this->lifecycle->createInstance([
                'component_definition_id' => $data['component_definition_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'serial' => $data['serial'] ?? null,
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
                'source_type' => ComponentInstance::SOURCE_MANUAL,
                'company_id' => $asset->company_id,
                'notes' => $data['note'] ?? null,
            ], $request->user());

            $this->lifecycle->installIntoAsset($component, $asset, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $asset)->with('success', __('Component created and installed.'));
    }

    public function expectedToTray(Request $request, Asset $asset, ModelNumberComponentTemplate $template): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('move', new ComponentInstance());
        $this->ensureTemplateBelongsToAsset($asset, $template);

        try {
            $this->expectedComponents->materializeToTray($asset, $template, $request->user(), [
                'installed_as' => $request->input('installed_as'),
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $asset)->with('success', __('Expected component moved to tray.'));
    }

    public function createTrackedStorage(Asset $asset, ComponentInstance $component): View
    {
        $this->authorize('view', $asset);
        $this->authorize('move', $component);
        $component = $this->ensureTrackedComponentOnAsset($asset, $component);

        return view('components.asset-storage', [
            'asset' => $asset,
            'component' => $component,
            'template' => null,
        ]);
    }

    public function storeTrackedStorage(Request $request, Asset $asset, ComponentInstance $component): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('move', $component);
        $component = $this->ensureTrackedComponentOnAsset($asset, $component);

        $data = $request->validate([
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'needs_verification' => ['nullable', 'boolean'],
            'verification_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $stockLocation = !empty($data['storage_location_id'])
                ? ComponentStorageLocation::findOrFail($data['storage_location_id'])
                : null;
            $verificationLocation = !empty($data['verification_location_id'])
                ? ComponentStorageLocation::findOrFail($data['verification_location_id'])
                : $stockLocation;

            $this->lifecycle->moveToStock($component, $stockLocation, [
                'performed_by' => $request->user(),
                'needs_verification' => $request->boolean('needs_verification'),
                'storage_location' => $verificationLocation,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $asset)->with('success', __('Component moved to stock.'));
    }

    public function createExpectedStorage(Asset $asset, ModelNumberComponentTemplate $template): View
    {
        $this->authorize('view', $asset);
        $this->authorize('move', new ComponentInstance());
        $this->ensureTemplateBelongsToAsset($asset, $template);

        return view('components.asset-storage', [
            'asset' => $asset,
            'component' => null,
            'template' => $template,
        ]);
    }

    public function storeExpectedStorage(Request $request, Asset $asset, ModelNumberComponentTemplate $template): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('move', new ComponentInstance());
        $this->ensureTemplateBelongsToAsset($asset, $template);

        $data = $request->validate([
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'needs_verification' => ['nullable', 'boolean'],
            'verification_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $stockLocation = !empty($data['storage_location_id'])
                ? ComponentStorageLocation::findOrFail($data['storage_location_id'])
                : null;
            $verificationLocation = !empty($data['verification_location_id'])
                ? ComponentStorageLocation::findOrFail($data['verification_location_id'])
                : $stockLocation;

            $this->expectedComponents->materializeToStock($asset, $template, $stockLocation, $request->user(), [
                'needs_verification' => $request->boolean('needs_verification'),
                'verification_location' => $verificationLocation,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $asset)->with('success', __('Expected component moved to stock.'));
    }

    public function createTrackedTransfer(Request $request, Asset $asset, ComponentInstance $component): View
    {
        $this->authorize('view', $asset);
        $this->authorize('install', $component);
        $component = $this->ensureTrackedComponentOnAsset($asset, $component);

        return view('components.asset-transfer', [
            'asset' => $asset,
            'component' => $component,
            'template' => null,
            'destinationAsset' => $this->selectedDestinationAsset($request, $asset),
            'scanQuery' => $this->transferScanQuery($asset, $component, null),
        ]);
    }

    public function storeTrackedTransfer(Request $request, Asset $asset, ComponentInstance $component): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('install', $component);
        $component = $this->ensureTrackedComponentOnAsset($asset, $component);

        $data = $request->validate([
            'destination_asset_id' => ['required', 'integer', 'exists:assets,id'],
            'note' => ['nullable', 'string'],
        ]);

        $destinationAsset = Asset::findOrFail($data['destination_asset_id']);
        $this->authorize('view', $destinationAsset);

        if ($destinationAsset->is($asset)) {
            return redirect()->back()->withInput()->with('error', __('Choose a different destination asset.'));
        }

        try {
            $this->lifecycle->installIntoAsset($component, $destinationAsset, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $destinationAsset)->with('success', __('Component moved to the destination asset.'));
    }

    public function createExpectedTransfer(Request $request, Asset $asset, ModelNumberComponentTemplate $template): View
    {
        $this->authorize('view', $asset);
        $this->authorize('install', new ComponentInstance());
        $this->ensureTemplateBelongsToAsset($asset, $template);

        return view('components.asset-transfer', [
            'asset' => $asset,
            'component' => null,
            'template' => $template,
            'destinationAsset' => $this->selectedDestinationAsset($request, $asset),
            'scanQuery' => $this->transferScanQuery($asset, null, $template),
        ]);
    }

    public function storeExpectedTransfer(Request $request, Asset $asset, ModelNumberComponentTemplate $template): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('install', new ComponentInstance());
        $this->ensureTemplateBelongsToAsset($asset, $template);

        $data = $request->validate([
            'destination_asset_id' => ['required', 'integer', 'exists:assets,id'],
            'note' => ['nullable', 'string'],
        ]);

        $destinationAsset = Asset::findOrFail($data['destination_asset_id']);
        $this->authorize('view', $destinationAsset);

        if ($destinationAsset->is($asset)) {
            return redirect()->back()->withInput()->with('error', __('Choose a different destination asset.'));
        }

        try {
            $this->expectedComponents->materializeToAsset($asset, $template, $destinationAsset, $request->user(), [
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('hardware.show', $destinationAsset)->with('success', __('Expected component moved to the destination asset.'));
    }

    private function trayComponents(Request $request)
    {
        return ComponentInstance::query()
            ->with(['componentDefinition.category', 'componentDefinition.manufacturer', 'sourceAsset.model'])
            ->inTray()
            ->heldBy($request->user())
            ->orderBy('component_tag')
            ->get();
    }

    private function stockInstallableComponents()
    {
        return ComponentInstance::query()
            ->with(['componentDefinition.category', 'componentDefinition.manufacturer', 'storageLocation.siteLocation'])
            ->whereIn('status', [
                ComponentInstance::STATUS_IN_STOCK,
                ComponentInstance::STATUS_NEEDS_VERIFICATION,
            ])
            ->whereNull('current_asset_id')
            ->orderBy('status')
            ->orderBy('component_tag')
            ->get();
    }

    private function ensureTrackedComponentOnAsset(Asset $asset, ComponentInstance $component): ComponentInstance
    {
        if ((int) $component->current_asset_id !== (int) $asset->id) {
            abort(404);
        }

        return $component;
    }

    private function ensureTemplateBelongsToAsset(Asset $asset, ModelNumberComponentTemplate $template): void
    {
        $modelNumberId = $asset->model_number_id ?: $asset->model?->primaryModelNumber?->id;

        if ((int) $template->model_number_id !== (int) $modelNumberId) {
            abort(404);
        }
    }

    private function selectedDestinationAsset(Request $request, Asset $asset): ?Asset
    {
        $destinationAssetId = (int) $request->query('destination_asset_id', 0);
        if (!$destinationAssetId) {
            return null;
        }

        $destinationAsset = Asset::find($destinationAssetId);

        if (!$destinationAsset || $destinationAsset->is($asset) || auth()->user()->cannot('view', $destinationAsset)) {
            return null;
        }

        return $destinationAsset;
    }

    private function transferScanQuery(Asset $asset, ?ComponentInstance $component, ?ModelNumberComponentTemplate $template): array
    {
        return array_filter([
            'mode' => 'asset_destination',
            'return_to' => $component
                ? route('hardware.components.transfer.create', [$asset, $component])
                : route('hardware.components.expected.transfer.create', [$asset, $template]),
        ]);
    }
}
