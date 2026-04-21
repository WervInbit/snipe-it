<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\Manufacturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComponentDefinitionSettingsController extends Controller
{
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

        return view('settings.component_definitions.edit', [
            'item' => $componentDefinition,
            'componentDefinition' => $componentDefinition,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComponentDefinition::class);

        $data = $this->validatedData($request);
        $definition = new ComponentDefinition($data);
        $definition->created_by = $request->user()?->id;
        $definition->updated_by = $request->user()?->id;
        $definition->save();

        return redirect()
            ->route('settings.component_definitions.edit', $definition)
            ->with('success', __('Component definition created.'));
    }

    public function update(Request $request, ComponentDefinition $componentDefinition): RedirectResponse
    {
        $this->authorize('update', $componentDefinition);

        $data = $this->validatedData($request);
        $componentDefinition->fill($data);
        $componentDefinition->updated_by = $request->user()?->id;
        $componentDefinition->save();

        return redirect()
            ->route('settings.component_definitions.index')
            ->with('success', __('Component definition updated.'));
    }

    public function deactivate(ComponentDefinition $componentDefinition): RedirectResponse
    {
        $this->authorize('update', $componentDefinition);

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
        $this->authorize('update', $componentDefinition);

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
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'part_code' => ['nullable', 'string', 'max:255'],
            'spec_summary' => ['nullable', 'string'],
            'serial_tracking_mode' => ['nullable', Rule::in(['optional', 'required', 'not_tracked'])],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'serial_tracking_mode' => $request->input('serial_tracking_mode', 'optional'),
        ];
    }

    protected function formOptions(): array
    {
        return [
            'categories' => Category::query()->where('category_type', 'component')->orderBy('name')->pluck('name', 'id'),
            'manufacturers' => Manufacturer::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
