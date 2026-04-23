<?php

namespace App\Http\Controllers\Components;

use App\Http\Controllers\Concerns\BuildsComponentWorkflowOptions;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ComponentWorkflowController extends Controller
{
    use BuildsComponentWorkflowOptions;

    public function __construct(
        protected ComponentLifecycleService $lifecycle,
    ) {
    }

    public function tray(Request $request): View
    {
        $this->authorize('view', ComponentInstance::class);

        $trayComponents = ComponentInstance::query()
            ->with([
                'componentDefinition.category',
                'componentDefinition.manufacturer',
                'sourceAsset.model',
                'storageLocation.siteLocation',
            ])
            ->inTray()
            ->heldBy($request->user())
            ->orderByDesc('transfer_started_at')
            ->get()
            ->each(function (ComponentInstance $component): void {
                $component->tray_warning = $this->trayWarningState($component->transfer_started_at);
                $component->transfer_age_human = $component->transfer_started_at?->diffForHumans();
            });

        return view('components.tray', [
            'trayComponents' => $trayComponents,
        ]);
    }

    public function createRemoveToTray(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('move', $component_id);

        abort_unless($component_id->status === ComponentInstance::STATUS_INSTALLED, 404);

        return view('components.workflows.remove-to-tray', [
            'component' => $component_id->loadMissing(['currentAsset.model']),
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function removeToTray(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->lifecycle->removeToTray($component_id, $request->user(), [
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component moved to tray.'));
    }

    public function createInstall(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('install', $component_id);

        abort_if(in_array($component_id->status, [
            ComponentInstance::STATUS_INSTALLED,
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
            ComponentInstance::STATUS_DEFECTIVE,
        ], true), 404);

        return view('components.workflows.install', [
            'component' => $component_id->loadMissing(['sourceAsset.model', 'storageLocation.siteLocation', 'heldBy']),
            'installableAssets' => $this->installableAssets(),
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function install(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('install', $component_id);

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $asset = Asset::findOrFail($data['asset_id']);
            $this->lifecycle->installIntoAsset($component_id, $asset, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component installed.'));
    }

    public function createMoveToStock(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('move', $component_id);

        abort_if(in_array($component_id->status, [
            ComponentInstance::STATUS_INSTALLED,
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true), 404);

        return view('components.workflows.storage', [
            'component' => $component_id->loadMissing(['storageLocation.siteLocation', 'currentAsset.model', 'heldBy']),
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function moveToStock(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

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

            $this->lifecycle->moveToStock($component_id, $stockLocation, [
                'performed_by' => $request->user(),
                'needs_verification' => $request->boolean('needs_verification'),
                'storage_location' => $verificationLocation,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component moved to stock.'));
    }

    public function createFlagNeedsVerification(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('verify', $component_id);

        abort_if(in_array($component_id->status, [
            ComponentInstance::STATUS_INSTALLED,
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true), 404);

        $locations = $this->storageLocationsByType();

        return view('components.workflows.verification', [
            'component' => $component_id->loadMissing(['storageLocation.siteLocation', 'currentAsset.model', 'heldBy']),
            'mode' => 'flag',
            'verificationLocations' => $locations['verification'],
            'stockLocations' => $locations['stock'],
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function flagNeedsVerification(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('verify', $component_id);

        $data = $request->validate([
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $location = !empty($data['storage_location_id'])
                ? ComponentStorageLocation::findOrFail($data['storage_location_id'])
                : $component_id->storageLocation;

            $this->lifecycle->flagNeedsVerification($component_id, [
                'performed_by' => $request->user(),
                'storage_location' => $location,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Verification required.'));
    }

    public function createConfirmVerification(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('verify', $component_id);

        abort_unless($component_id->status === ComponentInstance::STATUS_NEEDS_VERIFICATION, 404);

        $locations = $this->storageLocationsByType();

        return view('components.workflows.verification', [
            'component' => $component_id->loadMissing(['storageLocation.siteLocation', 'currentAsset.model', 'heldBy']),
            'mode' => 'confirm',
            'verificationLocations' => $locations['verification'],
            'stockLocations' => $locations['stock'],
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function confirmVerification(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('verify', $component_id);

        $data = $request->validate([
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $location = !empty($data['storage_location_id'])
                ? ComponentStorageLocation::findOrFail($data['storage_location_id'])
                : null;

            $this->lifecycle->confirmVerification($component_id, $location, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Verification confirmed.'));
    }

    public function createMarkDestructionPending(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('move', $component_id);

        abort_if(in_array($component_id->status, [
            ComponentInstance::STATUS_INSTALLED,
            ComponentInstance::STATUS_DESTROYED_RECYCLED,
            ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true), 404);

        $locations = $this->storageLocationsByType();

        return view('components.workflows.destruction', [
            'component' => $component_id->loadMissing(['storageLocation.siteLocation', 'currentAsset.model', 'heldBy']),
            'mode' => 'pending',
            'destructionLocations' => $locations['destruction'],
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function markDestructionPending(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

        $data = $request->validate([
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $location = !empty($data['storage_location_id'])
                ? ComponentStorageLocation::findOrFail($data['storage_location_id'])
                : null;

            $this->lifecycle->markDestructionPending($component_id, $location, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component marked for destruction.'));
    }

    public function createMarkDestroyed(Request $request, ComponentInstance $component_id): View
    {
        $this->authorize('move', $component_id);

        abort_unless($component_id->status === ComponentInstance::STATUS_DESTRUCTION_PENDING, 404);

        return view('components.workflows.destruction', [
            'component' => $component_id->loadMissing(['storageLocation.siteLocation', 'currentAsset.model', 'heldBy']),
            'mode' => 'destroyed',
            'destructionLocations' => collect(),
            'returnTo' => $this->returnTo($request, $component_id),
        ]);
    }

    public function markDestroyed(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->lifecycle->markDestroyed($component_id, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component destroyed.'));
    }

    public function markDefective(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->lifecycle->markDefective($component_id, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->returnTo($request, $component_id))->with('success', __('Component marked defective.'));
    }

    private function installableAssets()
    {
        return Asset::query()
            ->with(['model', 'assetstatus'])
            ->NotArchived()
            ->orderBy('asset_tag')
            ->get();
    }

    private function returnTo(Request $request, ComponentInstance $component): string
    {
        $returnTo = trim((string) $request->input('return_to', $request->query('return_to', '')));

        if ($returnTo === '') {
            return route('components.show', $component);
        }

        if (str_starts_with($returnTo, '/')) {
            return url($returnTo);
        }

        if (str_starts_with($returnTo, url('/'))) {
            return $returnTo;
        }

        return route('components.show', $component);
    }
}
