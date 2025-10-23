<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeDefinitionRequest;
use App\Http\Requests\AttributeDefinitionVersionRequest;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttributeDefinitionsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AttributeDefinition::class);

        $definitions = AttributeDefinition::query()
            ->with(['categories', 'previousVersion'])
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
            'item' => new AttributeDefinition(),
            'versionSource' => null,
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

        $attribute->load(['categories', 'options' => function ($query) {
            $query->orderBy('sort_order')->orderBy('label');
        }]);

        return view('attributes.edit', [
            'definition' => $attribute,
            'categories' => Category::orderBy('name')->get(),
            'item' => $attribute,
            'versionSource' => null,
        ]);
    }

    public function update(AttributeDefinitionRequest $request, AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $attribute->fill($this->payload($request, $attribute, includeIdentity: false));
        $attribute->save();
        $attribute->categories()->sync($request->input('category_ids', []));
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
            return back()->with('error', __('Deprecated attributes cannot be made visible. Create a new version instead.'));
        }

        $attribute->markVisible();

        return back()->with('success', __('Attribute is visible again.'));
    }

    public function createVersion(AttributeDefinition $attribute): View
    {
        $this->authorize('update', $attribute);

        $draft = $attribute->replicate([
            'id',
            'version',
            'supersedes_attribute_id',
            'deprecated_at',
            'hidden_at',
            'created_at',
            'updated_at',
        ]);
        $draft->exists = false;
        $draft->setRelation('categories', $attribute->categories);

        return view('attributes.edit', [
            'definition' => $draft,
            'categories' => Category::orderBy('name')->get(),
            'item' => $draft,
            'versionSource' => $attribute,
        ]);
    }

    public function storeVersion(AttributeDefinitionVersionRequest $request, AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $payload = $this->payloadForVersion($request);

        $newVersion = DB::transaction(function () use ($attribute, $payload, $request) {
            $clone = $attribute->createNewVersion($payload);
            $clone->categories()->sync($request->input('category_ids', $attribute->categories->pluck('id')->all()));
            $this->applyPendingOptions($clone, $request->input('options', []));

            return $clone;
        });

        return redirect()
            ->route('attributes.edit', $newVersion)
            ->with('success', __('New attribute version created. Update dependent models as needed.'));
    }

    private function payload(AttributeDefinitionRequest $request, ?AttributeDefinition $attribute = null, bool $includeIdentity = true): array
    {
        $data = $request->validated();

        $currentDatatype = $includeIdentity
            ? ($data['datatype'] ?? AttributeDefinition::DATATYPE_TEXT)
            : ($attribute?->datatype ?? AttributeDefinition::DATATYPE_TEXT);

        $payload = [
            'label' => $data['label'],
            'unit' => $data['unit'] ?? null,
            'required_for_category' => $request->boolean('required_for_category'),
            'needs_test' => $request->boolean('needs_test'),
            'allow_custom_values' => $request->boolean('allow_custom_values') && $currentDatatype === AttributeDefinition::DATATYPE_ENUM,
            'allow_asset_override' => $request->boolean('allow_asset_override'),
            'constraints' => $this->filterConstraints($data['constraints'] ?? []),
        ];

        if ($includeIdentity) {
            $payload['key'] = $data['key'];
            $payload['datatype'] = $currentDatatype;
        }

        return $payload;
    }

    private function payloadForVersion(AttributeDefinitionVersionRequest $request): array
    {
        $data = $request->validated();

        return [
            'label' => $data['label'],
            'datatype' => $data['datatype'],
            'unit' => $data['unit'] ?? null,
            'required_for_category' => $request->boolean('required_for_category'),
            'needs_test' => $request->boolean('needs_test'),
            'allow_custom_values' => $request->boolean('allow_custom_values') && $data['datatype'] === AttributeDefinition::DATATYPE_ENUM,
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

    private function applyPendingOptions(AttributeDefinition $attribute, array $options): void
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

            /** @var AttributeOption|null $existing */
            $existing = AttributeOption::withTrashed()
                ->where('attribute_definition_id', $attribute->id)
                ->where('value', $value)
                ->first();

            $payload = [
                'label' => $label,
                'sort_order' => $sortOrder,
                'active' => true,
            ];

            if ($existing) {
                $existing->fill($payload);
                $existing->restore();
                $existing->save();
            } else {
                $attribute->options()->create(array_merge([
                    'value' => $value,
                ], $payload));
            }
        }
    }
}


