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

        $locations = $this->storageLocationsByType();
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
            'stockLocations' => $locations['stock'],
            'verificationLocations' => $locations['verification'],
            'destructionLocations' => $locations['destruction'],
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

        return redirect()->back()->with('success', __('Component moved to tray.'));
    }

    public function install(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('install', $component_id);

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $asset = Asset::findOrFail($data['asset_id']);
            $this->lifecycle->installIntoAsset($component_id, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $data['installed_as'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Component installed.'));
    }

    public function moveToStock(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('move', $component_id);

        $data = $request->validate([
            'storage_location_id' => ['required', 'integer', 'exists:component_storage_locations,id'],
            'needs_verification' => ['nullable', 'boolean'],
            'verification_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $stockLocation = ComponentStorageLocation::findOrFail($data['storage_location_id']);
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

        return redirect()->back()->with('success', __('Component moved.'));
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

        return redirect()->back()->with('success', __('Verification required.'));
    }

    public function confirmVerification(Request $request, ComponentInstance $component_id): RedirectResponse
    {
        $this->authorize('verify', $component_id);

        $data = $request->validate([
            'storage_location_id' => ['required', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $location = ComponentStorageLocation::findOrFail($data['storage_location_id']);

            $this->lifecycle->confirmVerification($component_id, $location, [
                'performed_by' => $request->user(),
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Verification confirmed.'));
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

        return redirect()->back()->with('success', __('Component marked for destruction.'));
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

        return redirect()->back()->with('success', __('Component destroyed.'));
    }
}
