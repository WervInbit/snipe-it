<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeDefinitionRequest;
use App\Models\AssetAttributeOverride;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ModelNumberAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeDefinitionsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttributeDefinition::class);

        $search = trim((string) $request->input('search'));

        $definitions = AttributeDefinition::query()
            ->with('categories')
            ->withCount(['options'])
            ->when($search, function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('label', 'like', $like)
                        ->orWhere('key', 'like', $like);
                });
            })
            ->orderBy('label')
            ->paginate(25)
            ->appends(['search' => $search]);

        return view('attributes.index', [
            'definitions' => $definitions,
            'datatypes' => AttributeDefinition::DATATYPES,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AttributeDefinition::class);

        return view('attributes.edit', [
            'definition' => new AttributeDefinition(),
            'categories' => Category::orderBy('name')->get(),
            'item' => new AttributeDefinition(),
            'usageSummary' => $this->usageSummary(new AttributeDefinition()),
        ]);
    }

    public function store(AttributeDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', AttributeDefinition::class);

        $definition = new AttributeDefinition($this->payload($request));
        $definition->version = 1;
        $definition->save();
        $definition->categories()->sync($request->input('category_ids', []));
        $this->applyPendingOptions($definition, $request->input('options', []));

        return redirect()
            ->route('attributes.edit', $definition)
            ->with('success', __('Attribute saved. Configure options if needed below.'));
    }

    public function edit(AttributeDefinition $attribute): View
    {
        $this->authorize('view', $attribute);

        $attribute->load([
            'categories',
            'options' => function ($query) {
                $query->orderBy('sort_order')->orderBy('label');
            },
        ]);

        return view('attributes.edit', [
            'definition' => $attribute,
            'categories' => Category::orderBy('name')->get(),
            'item' => $attribute,
            'usageSummary' => $this->usageSummary($attribute),
        ]);
    }

    public function update(AttributeDefinitionRequest $request, AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $attribute->fill($this->payload($request, $attribute, includeDatatype: false));
        $attribute->save();
        $attribute->categories()->sync($request->input('category_ids', []));
        $this->syncExistingOptions($attribute, $request->input('options', []));
        $this->applyPendingOptions($attribute, $request->input('options', []));

        return redirect()
            ->route('attributes.edit', $attribute)
            ->with('success', __('Attribute updated.'));
    }

    public function destroy(AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        if (
            $attribute->modelValues()->exists()
            || $attribute->assetOverrides()->exists()
            || $attribute->componentDefinitionAttributes()->exists()
            || $attribute->testResults()->exists()
            || $attribute->nextVersions()->exists()
        ) {
            return redirect()
                ->route('attributes.edit', $attribute)
                ->with('error', __('You cannot delete an attribute that is in use. Hide it instead.'));
        }

        $attribute->delete();

        return redirect()
            ->route('attributes.index')
            ->with('success', __('Attribute archived.'));
    }

    public function hide(AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);
        $attribute->markHidden();

        return back()->with('success', __('Attribute hidden from selectors.'));
    }

    public function unhide(AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        if ($attribute->isDeprecated()) {
            return back()->with('error', __('Deprecated attributes cannot be made visible again.'));
        }

        $attribute->markVisible();

        return back()->with('success', __('Attribute is visible again.'));
    }

    private function payload(AttributeDefinitionRequest $request, ?AttributeDefinition $attribute = null, bool $includeDatatype = true): array
    {
        $data = $request->validated();

        $currentDatatype = $includeDatatype
            ? ($data['datatype'] ?? AttributeDefinition::DATATYPE_TEXT)
            : ($attribute?->datatype ?? AttributeDefinition::DATATYPE_TEXT);

        $payload = [
            'key' => $data['key'],
            'label' => $data['label'],
            'unit' => $data['unit'] ?? null,
            'required_for_category' => $request->boolean('required_for_category'),
            'allow_custom_values' => $request->boolean('allow_custom_values') && $currentDatatype === AttributeDefinition::DATATYPE_ENUM,
            'allow_asset_override' => $request->boolean('allow_asset_override'),
            'constraints' => $this->filterConstraints($data['constraints'] ?? []),
        ];

        if ($includeDatatype) {
            $payload['datatype'] = $currentDatatype;
        }

        return $payload;
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

    private function applyPendingOptions(AttributeDefinition $attribute, array $options, bool $allowRestore = true): void
    {
        if (!$attribute->isEnum()) {
            return;
        }

        $pending = $options['new'] ?? [];
        if (empty($pending)) {
            return;
        }

        $currentSort = $attribute->options()->max('sort_order') ?? -1;
        $seenValues = [];

        foreach ($pending as $option) {
            $value = trim((string) ($option['value'] ?? ''));
            $label = trim((string) ($option['label'] ?? ''));
            if ($value === '' || $label === '' || isset($seenValues[$value])) {
                continue;
            }
            $seenValues[$value] = true;

            $sortOrder = $option['sort_order'] ?? null;
            $sortOrder = ($sortOrder === '' || $sortOrder === null) ? null : (int) $sortOrder;

            if ($sortOrder === null) {
                $currentSort++;
                $sortOrder = $currentSort;
            } else {
                $currentSort = max($currentSort, $sortOrder);
            }

            $payload = [
                'label' => $label,
                'sort_order' => $sortOrder,
                'active' => array_key_exists('active', $option) ? (bool) $option['active'] : true,
            ];

            if ($allowRestore) {
                /** @var AttributeOption|null $existing */
                $existing = AttributeOption::withTrashed()
                    ->where('attribute_definition_id', $attribute->id)
                    ->where('value', $value)
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->restore();
                    $existing->save();
                    continue;
                }
            }

            $attribute->options()->create(array_merge([
                'value' => $value,
            ], $payload));
        }
    }

    private function syncExistingOptions(AttributeDefinition $attribute, array $options): void
    {
        if (!$attribute->isEnum()) {
            return;
        }

        $existingPayload = $options['existing'] ?? [];
        if (!is_array($existingPayload) || $existingPayload === []) {
            return;
        }

        $existingOptions = $attribute->options()->get()->keyBy('id');

        foreach ($existingPayload as $optionId => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            /** @var AttributeOption|null $option */
            $option = $existingOptions->get((int) $optionId);
            if (!$option) {
                continue;
            }

            if (!empty($payload['delete'])) {
                $option->delete();
                continue;
            }

            $value = trim((string) ($payload['value'] ?? $option->value));
            $label = trim((string) ($payload['label'] ?? $option->label));

            if ($value === '' || $label === '') {
                continue;
            }

            $valueChanged = $value !== $option->value;

            $option->fill([
                'value' => $value,
                'label' => $label,
                'sort_order' => isset($payload['sort_order']) && $payload['sort_order'] !== ''
                    ? (int) $payload['sort_order']
                    : $option->sort_order,
                'active' => array_key_exists('active', $payload) ? (bool) $payload['active'] : false,
            ])->save();

            if ($valueChanged) {
                $this->syncCurrentOptionValue($option);
            }
        }
    }

    private function syncCurrentOptionValue(AttributeOption $option): void
    {
        ModelNumberAttribute::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);

        AssetAttributeOverride::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);

        ComponentDefinitionAttribute::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);
    }

    private function usageSummary(AttributeDefinition $attribute): array
    {
        if (!$attribute->exists) {
            return [
                'model_values' => 0,
                'asset_overrides' => 0,
                'tests' => 0,
                'test_results' => 0,
                'component_definitions' => 0,
                'total' => 0,
            ];
        }

        $summary = [
            'model_values' => $attribute->modelValues()->count(),
            'asset_overrides' => $attribute->assetOverrides()->count(),
            'tests' => $attribute->tests()->count(),
            'test_results' => $attribute->testResults()->count(),
            'component_definitions' => $attribute->componentDefinitionAttributes()->count(),
        ];

        $summary['total'] = array_sum($summary);

        return $summary;
    }
}
