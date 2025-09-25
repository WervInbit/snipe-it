<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeDefinitionRequest;
use App\Models\AttributeDefinition;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttributeDefinitionsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AttributeDefinition::class);

        $definitions = AttributeDefinition::query()
            ->with(['categories'])
            ->withCount(['options'])
            ->orderBy('label')
            ->paginate(25);

        return view('attributes.index', [
            'definitions' => $definitions,
            'datatypes' => AttributeDefinition::DATATYPES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AttributeDefinition::class);

        return view('attributes.edit', [
            'definition' => new AttributeDefinition(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(AttributeDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', AttributeDefinition::class);

        $definition = new AttributeDefinition($this->payload($request));
        $definition->save();
        $definition->categories()->sync($request->input('category_ids', []));

        return redirect()
            ->route('attributes.edit', $definition)
            ->with('success', __('Attribute saved. Configure options if needed below.'));
    }

    public function edit(AttributeDefinition $attribute): View
    {
        $this->authorize('view', $attribute);

        $attribute->load(['categories', 'options' => function ($query) {
            $query->orderBy('sort_order')->orderBy('label');
        }]);

        return view('attributes.edit', [
            'definition' => $attribute,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(AttributeDefinitionRequest $request, AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $attribute->fill($this->payload($request));
        $attribute->save();
        $attribute->categories()->sync($request->input('category_ids', []));

        return redirect()
            ->route('attributes.edit', $attribute)
            ->with('success', __('Attribute updated.'));
    }

    public function destroy(AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return redirect()
            ->route('attributes.index')
            ->with('success', __('Attribute archived.'));
    }

    private function payload(AttributeDefinitionRequest $request): array
    {
        $data = $request->validated();

        return [
            'key' => $data['key'],
            'label' => $data['label'],
            'datatype' => $data['datatype'],
            'unit' => $data['unit'] ?? null,
            'required_for_category' => $request->boolean('required_for_category'),
            'needs_test' => $request->boolean('needs_test'),
            'allow_custom_values' => $request->boolean('allow_custom_values'),
            'allow_asset_override' => $request->boolean('allow_asset_override'),
            'constraints' => $this->filterConstraints($data['constraints'] ?? []),
        ];
    }

    private function filterConstraints(array $constraints): array
    {
        return array_filter([
            'min' => $constraints['min'] ?? null,
            'max' => $constraints['max'] ?? null,
            'step' => $constraints['step'] ?? null,
            'regex' => $constraints['regex'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
