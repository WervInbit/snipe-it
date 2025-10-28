<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ModelSpecificationRequest;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumber;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $assignedDefinitionIds = $modelNumber->attributes->pluck('attribute_definition_id')->values();

        $oldInput = collect(session()->getOldInput());
        $selectedDefinitionIds = $oldInput->has('attribute_order')
            ? collect($oldInput->get('attribute_order', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
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
            'selectedDefinitionIds' => $selectedDefinitionIds->all(),
            'definitionsById' => $definitionsById,
            'resolvedAttributes' => $resolvedAttributes,
            'availableAttributes' => $availableDefinitions,
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

        DB::transaction(function () use ($modelNumber, $attributeOrder, $attributeValues) {
            $this->attributeManager->syncModelNumberAssignments($modelNumber, $attributeOrder);
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
}




