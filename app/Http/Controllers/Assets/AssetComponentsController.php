<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AssetComponentsController extends Controller
{
    public function __construct(
        protected ComponentLifecycleService $lifecycle,
    ) {
    }

    public function extract(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('extract', new ComponentInstance());

        $data = $request->validate([
            'component_definition_id' => ['nullable', 'integer', 'exists:component_definitions,id'],
            'display_name' => ['required_without:component_definition_id', 'nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'condition_code' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->lifecycle->extractFromAsset($asset, [
                'component_definition_id' => $data['component_definition_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'serial' => $data['serial'] ?? null,
                'condition_code' => $data['condition_code'] ?? ComponentInstance::CONDITION_UNKNOWN,
                'event_note' => $data['note'] ?? null,
                'notes' => $data['note'] ?? null,
            ], $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Tracked component extracted to tray.'));
    }

    public function installFromTray(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);

        $data = $request->validate([
            'component_id' => ['required', 'integer', 'exists:component_instances,id'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $component = ComponentInstance::findOrFail($data['component_id']);
        $this->authorize('install', $component);

        if ($component->status !== ComponentInstance::STATUS_IN_TRANSFER) {
            return redirect()->back()->withInput()->with('error', __('Only tray components can be installed through the tray workflow.'));
        }

        try {
            $this->lifecycle->installIntoAsset($component, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $data['installed_as'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Tray component installed.'));
    }

    public function installExisting(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);

        $data = $request->validate([
            'component_id' => ['required', 'integer', 'exists:component_instances,id'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $component = ComponentInstance::findOrFail($data['component_id']);
        $this->authorize('install', $component);

        if ($component->status === ComponentInstance::STATUS_INSTALLED) {
            return redirect()->back()->withInput()->with('error', __('Installed components must be removed before they can be installed elsewhere.'));
        }

        try {
            $this->lifecycle->installIntoAsset($component, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $data['installed_as'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Component installed.'));
    }

    public function register(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('view', $asset);
        $this->authorize('create', ComponentInstance::class);
        $this->authorize('install', new ComponentInstance());

        $data = $request->validate([
            'component_definition_id' => ['nullable', 'integer', 'exists:component_definitions,id'],
            'display_name' => ['required_without:component_definition_id', 'nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'condition_code' => ['nullable', 'string', 'max:255'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $component = $this->lifecycle->createInstance([
                'component_definition_id' => $data['component_definition_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'serial' => $data['serial'] ?? null,
                'status' => ComponentInstance::STATUS_IN_STOCK,
                'condition_code' => $data['condition_code'] ?? ComponentInstance::CONDITION_UNKNOWN,
                'source_type' => $data['source_type'] ?? ComponentInstance::SOURCE_MANUAL,
                'company_id' => $asset->company_id,
                'notes' => $data['note'] ?? null,
            ], $request->user());

            $this->lifecycle->installIntoAsset($component, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $data['installed_as'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('Component registered and installed.'));
    }
}
