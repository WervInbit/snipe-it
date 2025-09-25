<?php

namespace App\Services\ModelAttributes;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
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
     * @param array<int|string, mixed> $payload keyed by attribute definition id or key.
     */
    public function saveModelAttributes(AssetModel $model, array $payload): void
    {
        $definitions = $this->fetchDefinitionsForModel($model);

        DB::transaction(function () use ($model, $payload, $definitions) {
            $persisted = ModelNumberAttribute::query()
                ->where('model_id', $model->id)
                ->get()
                ->keyBy('attribute_definition_id');

            foreach ($definitions as $definition) {
                $key = $this->resolvePayloadKey($payload, $definition);
                $hasKey = array_key_exists($key, $payload);
                $value = $payload[$key] ?? null;

                if (!$hasKey || $this->isEmpty($value)) {
                    if ($definition->required_for_category) {
                        $this->missingRequired($definition);
                    }

                    ModelNumberAttribute::query()
                        ->where('model_id', $model->id)
                        ->where('attribute_definition_id', $definition->id)
                        ->delete();

                    $persisted->forget($definition->id);
                    continue;
                }

                $normalized = $this->valueService->validateAndNormalize($definition, $value);

                $record = ModelNumberAttribute::query()->updateOrCreate(
                    [
                        'model_id' => $model->id,
                        'attribute_definition_id' => $definition->id,
                    ],
                    [
                        'value' => $normalized->value,
                        'raw_value' => $normalized->rawValue,
                        'attribute_option_id' => $normalized->attributeOptionId,
                    ]
                );

                $persisted->put($definition->id, $record);
            }

            $this->ensureRequiredComplete($model, $definitions, $persisted);
        });
    }

    /**
     * @param array<int|string, mixed> $payload keyed by attribute definition id or key.
     */
    public function saveAssetOverrides(Asset $asset, array $payload): void
    {
        $asset->loadMissing('model', 'attributeOverrides');
        $keys = array_unique(array_merge(array_keys($payload), $asset->attributeOverrides->pluck('attribute_definition_id')->all()));
        $definitions = $this->fetchDefinitionsForModel($asset->model, $keys);

        DB::transaction(function () use ($asset, $payload, $definitions) {
            foreach ($definitions as $definition) {
                $key = $this->resolvePayloadKey($payload, $definition);
                $hasKey = array_key_exists($key, $payload);
                $value = $payload[$key] ?? null;

                if (!$definition->allow_asset_override) {
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

    private function fetchDefinitionsForModel(AssetModel $model, array $keys = []): Collection
    {
        $query = $model->attributeDefinitionsForCategory();

        if (!empty($keys)) {
            $numeric = array_values(array_filter($keys, static fn ($key) => is_numeric($key)));
            $stringKeys = array_values(array_filter($keys, static fn ($key) => is_string($key)));

            $query->where(function ($inner) use ($numeric, $stringKeys) {
                $hasClause = false;

                if (!empty($numeric)) {
                    $inner->whereIn('id', $numeric);
                    $hasClause = true;
                }

                if (!empty($stringKeys)) {
                    if ($hasClause) {
                        $inner->orWhereIn('key', $stringKeys);
                    } else {
                        $inner->whereIn('key', $stringKeys);
                    }
                }
            });
        }

        return $query->with('options')->get();
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

    private function ensureRequiredComplete(AssetModel $model, Collection $definitions, Collection $persisted): void
    {
        $missing = $definitions->filter(function (AttributeDefinition $definition) use ($persisted, $model) {
            if (!$definition->required_for_category) {
                return false;
            }

            if ($persisted->has($definition->id)) {
                return false;
            }

            return !ModelNumberAttribute::query()
                ->where('model_id', $model->id)
                ->where('attribute_definition_id', $definition->id)
                ->exists();
        });

        if ($missing->isNotEmpty()) {
            $labels = $missing->pluck('label')->implode(', ');

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
