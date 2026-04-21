<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComponentStorageLocationSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage', ComponentStorageLocation::class);

        $search = trim((string) $request->input('search'));

        $locations = ComponentStorageLocation::query()
            ->with('siteLocation')
            ->withCount('componentInstances')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('siteLocation', fn ($locationQuery) => $locationQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('settings.component_storage_locations.index', [
            'storageLocations' => $locations,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ComponentStorageLocation::class);

        return view('settings.component_storage_locations.create', [
            'item' => new ComponentStorageLocation(),
            ...$this->formOptions(),
        ]);
    }

    public function edit(ComponentStorageLocation $componentStorageLocation): View
    {
        $this->authorize('update', $componentStorageLocation);

        return view('settings.component_storage_locations.edit', [
            'item' => $componentStorageLocation,
            'componentStorageLocation' => $componentStorageLocation,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComponentStorageLocation::class);

        $location = ComponentStorageLocation::create($this->validatedData($request));

        return redirect()
            ->route('settings.component_storage_locations.edit', $location)
            ->with('success', __('Component storage location created.'));
    }

    public function update(Request $request, ComponentStorageLocation $componentStorageLocation): RedirectResponse
    {
        $this->authorize('update', $componentStorageLocation);

        $componentStorageLocation->fill($this->validatedData($request, $componentStorageLocation))->save();

        return redirect()
            ->route('settings.component_storage_locations.index')
            ->with('success', __('Component storage location updated.'));
    }

    public function deactivate(ComponentStorageLocation $componentStorageLocation): RedirectResponse
    {
        $this->authorize('update', $componentStorageLocation);

        $componentStorageLocation->forceFill(['is_active' => false])->save();

        return redirect()
            ->route('settings.component_storage_locations.index')
            ->with('success', __('Component storage location deactivated.'));
    }

    public function activate(ComponentStorageLocation $componentStorageLocation): RedirectResponse
    {
        $this->authorize('update', $componentStorageLocation);

        $componentStorageLocation->forceFill(['is_active' => true])->save();

        return redirect()
            ->route('settings.component_storage_locations.index')
            ->with('success', __('Component storage location activated.'));
    }

    protected function validatedData(Request $request, ?ComponentStorageLocation $componentStorageLocation = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('component_storage_locations', 'code')->ignore($componentStorageLocation?->id),
            ],
            'site_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'type' => ['required', Rule::in([
                ComponentStorageLocation::TYPE_STOCK,
                ComponentStorageLocation::TYPE_GENERAL,
                ComponentStorageLocation::TYPE_DESTRUCTION,
                ComponentStorageLocation::TYPE_VERIFICATION,
            ])],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function formOptions(): array
    {
        return [
            'siteLocations' => Location::query()->orderBy('name')->pluck('name', 'id'),
            'types' => [
                ComponentStorageLocation::TYPE_STOCK => __('Stock'),
                ComponentStorageLocation::TYPE_GENERAL => __('General'),
                ComponentStorageLocation::TYPE_VERIFICATION => __('Verification'),
                ComponentStorageLocation::TYPE_DESTRUCTION => __('Destruction'),
            ],
        ];
    }
}
