<?php

namespace App\Services\ModelAttributes;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\ModelNumber;
use Illuminate\Support\Collection;

class EffectiveAttributeResolver
{
    public function resolveForModel(AssetModel $model): Collection
    {
        $model->loadMissing('primaryModelNumber');

        $modelNumber = $model->primaryModelNumber;

        if (!$modelNumber) {
            return collect();
        }

        return $this->resolveForModelNumber($modelNumber);
    }

    public function resolveForModelNumber(ModelNumber $modelNumber): Collection
    {
        $modelNumber->loadMissing('model.category');
        $model = $modelNumber->model;

        if (!$model) {
            return collect();
        }

        return $this->resolveWithContext($model, $modelNumber, collect());
    }

    public function resolveForAsset(Asset $asset, ?ModelNumber $overrideModelNumber = null): Collection
    {
        $asset->loadMissing('model', 'modelNumber', 'attributeOverrides.option');

        $model = $asset->model;

        $modelNumber = $overrideModelNumber
            ?? $asset->modelNumber
            ?? $model?->primaryModelNumber;

        if (!$model || !$modelNumber) {
            return collect();
        }

        $overrides = $asset->attributeOverrides->keyBy('attribute_definition_id');

        return $this->resolveWithContext($model, $modelNumber, $overrides);
    }

    private function buildResolved(AttributeDefinition $definition, ?ModelNumberAttribute $modelValue, ?AssetAttributeOverride $override, bool $isOverride = false): ResolvedAttribute
    {
        $modelValueString = $modelValue?->value;
        $modelRaw = $modelValue?->raw_value;

        if ($override && $isOverride) {
            $override->loadMissing('option');

            return new ResolvedAttribute(
                $definition,
                $override->value,
                $override->raw_value,
                $override->option,
                'override',
                $definition->needs_test,
                true,
                $modelValueString,
                $modelRaw
            );
        }

        if ($modelValue) {
            $modelValue->loadMissing('option');

            return new ResolvedAttribute(
                $definition,
                $modelValue->value,
                $modelValue->raw_value,
                $modelValue->option,
                'model',
                $definition->needs_test,
                false,
                $modelValueString,
                $modelRaw
            );
        }

        return new ResolvedAttribute(
            $definition,
            null,
            null,
            null,
            'missing',
            $definition->needs_test,
            false,
            $modelValueString,
            $modelRaw
        );
    }

    private function resolveWithContext(AssetModel $model, ModelNumber $modelNumber, Collection $overrides): Collection
    {
        $definitions = $model->attributeDefinitionsForCategory()->with('options')->get();
        $modelValues = $modelNumber->attributes()->with('option')->get()->keyBy('attribute_definition_id');

        return $definitions->map(function ($definition) use ($modelValues, $overrides) {
            /** @var ModelNumberAttribute|null $modelValue */
            $modelValue = $modelValues->get($definition->id);
            /** @var AssetAttributeOverride|null $override */
            $override = $overrides->get($definition->id);

            if ($override && $definition->allow_asset_override) {
                return $this->buildResolved($definition, $modelValue, $override, true);
            }

            return $this->buildResolved($definition, $modelValue, null);
        });
    }
}
