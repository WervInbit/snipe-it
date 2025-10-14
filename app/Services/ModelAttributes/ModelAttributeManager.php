<?php

namespace App\Services\ModelAttributes;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\ModelNumber;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModelAttributeManager
{
    public function __construct(private readonly AttributeValueService $valueService)
    {
    }

    /**
     * @param array<int|string> $orderedDefinitionIds
     */
    public function syncModelNumberAssignments(ModelNumber $modelNumber, array $orderedDefinitionIds): void
    {
        $modelNumber->loadMissing('model');
        $model = $modelNumber->model;

        if (!$model) {
            return;
        }

        $definitionIds = collect($orderedDefinitionIds)
            ->map(function ($value) {
                if (is_int($value)) {
                    return $value;
                }

                if (is_string($value) && ctype_digit($value)) {
                    return (int) $value;
                }

                return null;
            })
            ->filter(fn ($value) => $value !== null)
            ->unique()
            ->values();

        DB::transaction(function () use ($modelNumber, $definitionIds, $model) {
            $existingAssignments = ModelNumberAttribute::query()
                ->where('model_number_id', $modelNumber->id)
                ->get()
                ->keyBy('attribute_definition_id');

            $retainedDefinitionIds = [];

            foreach ($definitionIds as $position => $definitionId) {
                /** @var ModelNumberAttribute|null $assignment */
                $assignment = $existingAssignments->get($definitionId);

                if (!$assignment) {
                    $definition = AttributeDefinition::query()
                        ->forCategory($model->category_id)
                        ->whereKey($definitionId)
                        ->first();

                    if (!$definition) {
                        continue;
                    }

                    $assignment = ModelNumberAttribute::create([
                        'model_number_id' => $modelNumber->id,
                        'attribute_definition_id' => $definitionId,
                        'display_order' => $position,
                        'value' => null,
                        'raw_value' => null,
                        'attribute_option_id' => null,
                    ]);

                    $existingAssignments->put($definitionId, $assignment);
                } elseif ($assignment->display_order !== $position) {
                    $assignment->display_order = $position;
                    $assignment->save();
                }

                $retainedDefinitionIds[] = $definitionId;
            }

            $assignmentsToRemoveQuery = ModelNumberAttribute::query()
                ->where('model_number_id', $modelNumber->id);

            if (!empty($retainedDefinitionIds)) {
                $assignmentsToRemoveQuery->whereNotIn('attribute_definition_id', $retainedDefinitionIds);
            }

            $assignmentsToRemove = $assignmentsToRemoveQuery->get();

            if ($assignmentsToRemove->isEmpty()) {
                return;
            }

            $definitionIdsToRemove = $assignmentsToRemove->pluck('attribute_definition_id')->all();

            AssetAttributeOverride::query()
                ->whereIn('attribute_definition_id', $definitionIdsToRemove)
                ->whereHas('asset', fn ($query) => $query->where('model_number_id', $modelNumber->id))
                ->delete();

            ModelNumberAttribute::query()
                ->where('model_number_id', $modelNumber->id)
                ->whereIn('attribute_definition_id', $definitionIdsToRemove)
                ->delete();
        });
    }

    /**
     * @param array<int|string, mixed> $payload keyed by attribute definition id or key.
     */
    public function saveModelAttributes(ModelNumber $modelNumber, array $payload): void
    {
        $modelNumber->loadMissing('model');
        $model = $modelNumber->model;

        if (!$model) {
            throw ValidationException::withMessages([
                'model_number_id' => __('The selected model number is invalid.'),
            ]);
        }

        $assignments = $this->fetchAssignments($modelNumber);

        DB::transaction(function () use ($modelNumber, $payload, $assignments) {
            $persisted = collect();

            foreach ($assignments as $assignment) {
                $definition = $assignment->definition;
                $key = $this->resolvePayloadKey($payload, $definition);
                $hasKey = array_key_exists($key, $payload);
                $value = $payload[$key] ?? null;

                if (!$hasKey) {
                    $persisted->put($definition->id, $assignment);
                    continue;
                }

                if ($this->isEmpty($value)) {
                    if ($definition->required_for_category) {
                        $this->missingRequired($definition);
                    }

                    $assignment->fill([
                        'value' => null,
                        'raw_value' => null,
                        'attribute_option_id' => null,
                    ])->save();

                    $persisted->put($definition->id, $assignment->fresh());
                    continue;
                }

                $normalized = $this->valueService->validateAndNormalize($definition, $value);

                $assignment->fill([
                    'value' => $normalized->value,
                    'raw_value' => $normalized->rawValue,
                    'attribute_option_id' => $normalized->attributeOptionId,
                ])->save();

                $persisted->put($definition->id, $assignment->fresh());
            }

            $this->ensureRequiredComplete($modelNumber, $persisted);
        });
    }

    /**
     * @param array<int|string, mixed> $payload keyed by attribute definition id or key.
     */
    public function saveAssetOverrides(Asset $asset, array $payload): void
    {
        $asset->loadMissing('model', 'attributeOverrides', 'modelNumber');
        $keys = array_unique(array_merge(array_keys($payload), $asset->attributeOverrides->pluck('attribute_definition_id')->all()));

        $modelNumber = $asset->modelNumber ?: $asset->model?->primaryModelNumber;

        if (!$modelNumber) {
            return;
        }

        $assignments = $this->fetchAssignments($modelNumber, $keys)->keyBy('attribute_definition_id');

        if ($assignments->isEmpty()) {
            AssetAttributeOverride::query()
                ->where('asset_id', $asset->id)
                ->delete();

            return;
        }

        $validIds = $assignments->pluck('attribute_definition_id')->all();

        AssetAttributeOverride::query()
            ->where('asset_id', $asset->id)
            ->whereNotIn('attribute_definition_id', $validIds)
            ->delete();

        DB::transaction(function () use ($asset, $payload, $assignments) {
            foreach ($assignments as $assignment) {
                $definition = $assignment->definition;
                $key = $this->resolvePayloadKey($payload, $definition);
                $hasKey = array_key_exists($key, $payload);
                $value = $payload[$key] ?? null;

                if (!$definition->allow_asset_override) {
                    if ($hasKey && !$this->isEmpty($value)) {
                        $this->fail($definition, __('Overrides are disabled for :label.', ['label' => $definition->label]));
                    }

                    AssetAttributeOverride::query()
                        ->where('asset_id', $asset->id)
                        ->where('attribute_definition_id', $definition->id)
                        ->delete();
                    continue;
                }

                if (!$hasKey) {
                    continue;
                }

                if ($this->isEmpty($value)) {
                    AssetAttributeOverride::query()
                        ->where('asset_id', $asset->id)
                        ->where('attribute_definition_id', $definition->id)
                        ->delete();
                    continue;
                }

                $normalized = $this->valueService->validateAndNormalize($definition, $value);

                AssetAttributeOverride::query()->updateOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'attribute_definition_id' => $definition->id,
                    ],
                    [
                        'value' => $normalized->value,
                        'raw_value' => $normalized->rawValue,
                        'attribute_option_id' => $normalized->attributeOptionId,
                    ]
                );
            }
        });
    }

    private function fetchAssignments(ModelNumber $modelNumber, array $keys = []): Collection
    {
        $assignments = ModelNumberAttribute::query()
            ->where('model_number_id', $modelNumber->id)
            ->with(['definition' => fn ($query) => $query->with('options')])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        if (empty($keys)) {
            return $assignments;
        }

        $normalizedKeys = collect($keys)
            ->filter(fn ($key) => $key !== null && $key !== '')
            ->values();

        if ($normalizedKeys->isEmpty()) {
            return $assignments;
        }

        $numeric = $normalizedKeys->filter(fn ($key) => is_numeric($key))->map(fn ($key) => (int) $key)->all();
        $stringKeys = $normalizedKeys->filter(fn ($key) => is_string($key))->all();

        return $assignments->filter(function (ModelNumberAttribute $assignment) use ($numeric, $stringKeys) {
            $definition = $assignment->definition;

            return (in_array($definition->id, $numeric, true))
                || (in_array($definition->key, $stringKeys, true));
        })->values();
    }

    private function resolvePayloadKey(array $payload, AttributeDefinition $definition): int|string
    {
        if (array_key_exists($definition->id, $payload)) {
            return $definition->id;
        }

        if (array_key_exists($definition->key, $payload)) {
            return $definition->key;
        }

        return $definition->id;
    }

    private function ensureRequiredComplete(ModelNumber $modelNumber, Collection $assignments): void
    {
        $missing = $assignments->filter(function (ModelNumberAttribute $assignment) {
            $definition = $assignment->definition;

            if (!$definition->required_for_category) {
                return false;
            }

            return $this->isEmpty($assignment->value);
        });

        if ($missing->isNotEmpty()) {
            $labels = $missing->map(fn (ModelNumberAttribute $assignment) => $assignment->definition->label)->implode(', ');

            throw ValidationException::withMessages([
                'attributes' => __('Complete required attributes: :list', ['list' => $labels]),
            ]);
        }
    }

    private function missingRequired(AttributeDefinition $definition): void
    {
        $this->fail($definition, __('This field is required.'));
    }

    private function fail(AttributeDefinition $definition, string $message): void
    {
        throw ValidationException::withMessages([
            $definition->key => [$message],
        ]);
    }

    private function isEmpty($value): bool
    {
        return $value === null || $value === '';
    }
}

