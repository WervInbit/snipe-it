<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ModelSpecificationRequest;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModelSpecificationController extends Controller
{
    public function __construct(
        private readonly EffectiveAttributeResolver $resolver,
        private readonly ModelAttributeManager $attributeManager
    ) {
    }

    public function edit(Request $request, AssetModel $model): View
    {
        $this->authorize('update', $model);

        $model->loadMissing([
            'category',
            'modelNumbers' => fn ($query) => $query->orderBy('code'),
        ]);

        $modelNumbers = $model->modelNumbers;
        $availableDefinitions = AttributeDefinition::query()
            ->forCategory($model->category_id, $model->category?->category_type)
            ->current()
            ->with('options')
            ->orderBy('label')
            ->get();

        if ($modelNumbers->isEmpty()) {
            return view('models.spec', [
                'model' => $model,
                'item' => $model,
                'modelNumber' => null,
                'modelNumbers' => $modelNumbers,
                'selectedDefinitionIds' => [],
                'definitionsById' => collect(),
                'resolvedAttributes' => collect(),
                'availableAttributes' => $availableDefinitions,
            ]);
        }

        $modelNumberId = (int) $request->input('model_number_id');
        /** @var ModelNumber|null $modelNumber */
        $modelNumber = $modelNumbers->firstWhere('id', $modelNumberId);

        if (!$modelNumber) {
            $modelNumber = $model->primaryModelNumber ?? $modelNumbers->first();
        }

        $modelNumber->loadMissing(['attributes.definition.options', 'attributes.option', 'attributes.definition.categories']);
        $modelNumber->loadMissing([
            'componentTemplates.componentDefinition.category',
            'componentTemplates.componentDefinition.manufacturer',
            'componentTemplates.componentDefinition.attributeContributions.definition.options',
            'componentTemplates.componentDefinition.attributeContributions.option',
        ]);
        $lockedDefinitionIds = $this->attributeManager->componentResolvedNumericDefinitionIds($modelNumber);

        $assignedDefinitionIds = $modelNumber->attributes
            ->pluck('attribute_definition_id')
            ->reject(fn ($id) => in_array((int) $id, $lockedDefinitionIds, true))
            ->values();
        $availableDefinitions = $availableDefinitions
            ->reject(fn (AttributeDefinition $definition) => in_array((int) $definition->id, $lockedDefinitionIds, true))
            ->values();

        $oldInput = collect(session()->getOldInput());
        $selectedDefinitionIds = $oldInput->has('attribute_order')
            ? collect($oldInput->get('attribute_order', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->reject(fn ($id) => in_array((int) $id, $lockedDefinitionIds, true))
                ->values()
            : $assignedDefinitionIds;

        if ($selectedDefinitionIds->isEmpty() && $assignedDefinitionIds->isNotEmpty()) {
            $selectedDefinitionIds = $assignedDefinitionIds;
        }

        $definitionIds = $availableDefinitions->pluck('id')
            ->merge($selectedDefinitionIds)
            ->unique()
            ->all();

        $definitionsById = AttributeDefinition::query()
            ->whereIn('id', $definitionIds)
            ->with('options')
            ->get()
            ->keyBy('id');

        $resolvedAssignments = $this->resolver
            ->resolveForModelNumber($modelNumber)
            ->keyBy(fn ($resolvedAttribute) => $resolvedAttribute->definition->id);

        $resolvedAttributes = collect();

        foreach ($definitionIds as $definitionId) {
            $definition = $definitionsById->get($definitionId);

            if (!$definition) {
                continue;
            }

            $resolvedAttributes->put(
                $definitionId,
                $resolvedAssignments->get($definitionId) ?? $this->resolver->createResolved($definition)
            );
        }

        return view('models.spec', [
            'model' => $model,
            'item' => $model,
            'modelNumber' => $modelNumber,
            'modelNumbers' => $modelNumbers,
            'lockedDefinitionIds' => $lockedDefinitionIds,
            'selectedDefinitionIds' => $selectedDefinitionIds->all(),
            'definitionsById' => $definitionsById,
            'resolvedAttributes' => $resolvedAttributes,
            'resolvedPreviewAttributes' => $resolvedAssignments->values(),
            'availableAttributes' => $availableDefinitions,
            'componentDefinitions' => $this->activeComponentDefinitions(),
        ]);
    }

    public function update(ModelSpecificationRequest $request, AssetModel $model): RedirectResponse
    {
        $this->authorize('update', $model);

        $modelNumberId = (int) $request->input('model_number_id');
        $modelNumber = $modelNumberId
            ? $model->modelNumbers()->whereKey($modelNumberId)->first()
            : $model->primaryModelNumber;

        if (!$modelNumber) {
            return redirect()
                ->route('models.spec.edit', $model)
                ->with('error', __('Add a model number before editing the specification.'));
        }

        $attributeOrder = $request->input('attribute_order', []);
        $attributeValues = $request->input('attributes', []);
        $componentTemplates = $request->input('component_templates', []);

        DB::transaction(function () use ($modelNumber, $attributeOrder, $attributeValues, $componentTemplates) {
            $this->syncComponentTemplates($modelNumber, $componentTemplates);
            $modelNumber->unsetRelation('componentTemplates');
            $lockedDefinitionIds = $this->attributeManager->componentResolvedNumericDefinitionIds($modelNumber);
            $filteredOrder = collect($attributeOrder)
                ->map(fn ($id) => (int) $id)
                ->reject(fn (int $id) => in_array($id, $lockedDefinitionIds, true))
                ->values()
                ->all();
            $this->attributeManager->syncModelNumberAssignments($modelNumber, $filteredOrder);
            $this->attributeManager->saveModelAttributes($modelNumber, $attributeValues);
        });

        return redirect()
            ->route('models.spec.edit', ['model' => $model, 'model_number_id' => $modelNumber->id])
            ->with('success', __('Model specification updated.'));
    }

    public function editForNumber(Request $request, AssetModel $model, ModelNumber $modelNumber): View
    {
        $this->ensureModelNumber($model, $modelNumber);

        $request = $request->duplicate(array_merge($request->all(), [
            'model_number_id' => $modelNumber->id,
        ]));

        return $this->edit($request, $model);
    }

    public function updateForNumber(ModelSpecificationRequest $request, AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->ensureModelNumber($model, $modelNumber);

        $request = $request->duplicate(array_merge($request->all(), [
            'model_number_id' => $modelNumber->id,
        ]));

        return $this->update($request, $model);
    }

    private function ensureModelNumber(AssetModel $model, ModelNumber $candidate): void
    {
        if ($candidate->model_id !== $model->id) {
            abort(404);
        }
    }

    private function activeComponentDefinitions(): Collection
    {
        return ComponentDefinition::query()
            ->with(['category', 'manufacturer', 'attributeContributions.definition.options', 'attributeContributions.option'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function syncComponentTemplates(ModelNumber $modelNumber, array $rows): void
    {
        $rows = collect($rows)
            ->map(fn ($row) => is_array($row) ? $row : [])
            ->values();

        $existingTemplates = $modelNumber->componentTemplates()->get()->keyBy('id');
        $componentDefinitions = $this->activeComponentDefinitions()->keyBy('id');
        $retainedIds = [];

        foreach ($rows as $index => $row) {
            $templateId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
            $componentDefinitionId = isset($row['component_definition_id']) && $row['component_definition_id'] !== ''
                ? (int) $row['component_definition_id']
                : null;

            $componentDefinition = $componentDefinitionId
                ? $componentDefinitions->get($componentDefinitionId)
                : null;

            $hasMeaningfulData = $componentDefinitionId !== null;

            if (!$hasMeaningfulData) {
                continue;
            }

            $template = $templateId ? $existingTemplates->get($templateId) : null;
            if (!$template) {
                $template = new ModelNumberComponentTemplate();
                $template->model_number_id = $modelNumber->id;
            }

            $template->fill([
                'component_definition_id' => $componentDefinition?->id,
                'expected_name' => $componentDefinition?->name,
                'slot_name' => null,
                'expected_qty' => max(1, (int) ($row['expected_qty'] ?? 1)),
                'is_required' => true,
                'sort_order' => $index,
                'notes' => null,
            ]);
            $template->save();

            $retainedIds[] = $template->id;
        }

        $query = ModelNumberComponentTemplate::query()
            ->where('model_number_id', $modelNumber->id);

        if ($retainedIds !== []) {
            $query->whereNotIn('id', $retainedIds);
        }

        $query->delete();
    }
}




