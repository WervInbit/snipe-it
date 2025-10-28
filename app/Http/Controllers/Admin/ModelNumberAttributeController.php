<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignModelNumberAttributeRequest;
use App\Http\Requests\ReorderModelNumberAttributesRequest;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberAttribute;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ResolvedAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ModelNumberAttributeController extends Controller
{
    public function __construct(private readonly EffectiveAttributeResolver $resolver)
    {
    }

    public function store(AssignModelNumberAttributeRequest $request, AssetModel $model, ModelNumber $modelNumber): JsonResponse
    {
        $this->ensureModelNumber($model, $modelNumber);

        $definitionId = (int) $request->input('attribute_definition_id');
        $definition = $this->resolveDefinitionForModel($model, $definitionId);

        $assignment = ModelNumberAttribute::query()
            ->where('model_number_id', $modelNumber->id)
            ->where('attribute_definition_id', $definition->id)
            ->first();

        if (!$assignment) {
            $nextOrder = ModelNumberAttribute::query()
                ->where('model_number_id', $modelNumber->id)
                ->max('display_order');

            $assignment = ModelNumberAttribute::create([
                'model_number_id' => $modelNumber->id,
                'attribute_definition_id' => $definition->id,
                'display_order' => is_null($nextOrder) ? 0 : $nextOrder + 1,
                'value' => null,
                'raw_value' => null,
                'attribute_option_id' => null,
            ]);
        }

        $modelNumber->unsetRelation('attributes');
        $modelNumber->load(['attributes.definition.options', 'attributes.option']);

        $resolved = $this->resolver
            ->resolveForModelNumber($modelNumber)
            ->first(fn ($item) => $item->definition->id === $definition->id);

        if (!$resolved) {
            $resolved = $this->resolver->createResolved($definition);
        }

        $selectedItem = view('models.model_numbers.partials.selected-attribute-item', [
            'definition' => $definition,
        ])->render();

        $detail = view('models.model_numbers.partials.attribute-detail', [
            'resolved' => $resolved,
        ])->render();

        return response()->json([
            'attribute' => [
                'id' => $definition->id,
                'label' => $definition->label,
                'key' => $definition->key,
            ],
            'selected_item' => $selectedItem,
            'detail' => $detail,
        ]);
    }

    public function destroy(AssetModel $model, ModelNumber $modelNumber, AttributeDefinition $attributeDefinition): JsonResponse
    {
        $this->ensureModelNumber($model, $modelNumber);
        $definition = $this->resolveDefinitionForModel($model, $attributeDefinition->id);

        $deleted = ModelNumberAttribute::query()
            ->where('model_number_id', $modelNumber->id)
            ->where('attribute_definition_id', $definition->id)
            ->delete();

        AssetAttributeOverride::query()
            ->where('attribute_definition_id', $definition->id)
            ->whereHas('asset', fn ($query) => $query->where('model_number_id', $modelNumber->id))
            ->delete();

        return response()->json([
            'status' => $deleted ? 'removed' : 'skipped',
        ]);
    }

    public function reorder(ReorderModelNumberAttributesRequest $request, AssetModel $model, ModelNumber $modelNumber): JsonResponse
    {
        $this->ensureModelNumber($model, $modelNumber);

        $order = array_values(array_map('intval', $request->input('order', [])));

        $assignments = ModelNumberAttribute::query()
            ->where('model_number_id', $modelNumber->id)
            ->get()
            ->keyBy('attribute_definition_id');

        DB::transaction(function () use ($order, $assignments) {
            foreach ($order as $position => $definitionId) {
                if (!$assignments->has($definitionId)) {
                    continue;
                }

                /** @var ModelNumberAttribute $assignment */
                $assignment = $assignments->get($definitionId);
                $assignment->display_order = $position;
                $assignment->save();
            }
        });

        return response()->json([
            'status' => 'ok',
        ]);
    }

    private function ensureModelNumber(AssetModel $model, ModelNumber $candidate): void
    {
        if ($candidate->model_id !== $model->id) {
            abort(404);
        }
    }

    private function resolveDefinitionForModel(AssetModel $model, int $definitionId): AttributeDefinition
    {
        $model->loadMissing('category');

        $definition = AttributeDefinition::query()
            ->forCategory($model->category_id, $model->category?->category_type)
            ->current()
            ->whereKey($definitionId)
            ->first();

        if (!$definition) {
            abort(404);
        }

        return $definition;
    }
}

