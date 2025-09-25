<?php

namespace App\Services\ModelAttributes;

use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use Illuminate\Support\Collection;

class EffectiveAttributeResolver
{
    public function resolveForModel(AssetModel $model): Collection
    {
        $definitions = $model->attributeDefinitionsForCategory()->with('options')->get();
        $modelValues = $model->specAttributes()->with('option')->get()->keyBy('attribute_definition_id');

        return $definitions->map(function ($definition) use ($modelValues) {
            /** @var ModelNumberAttribute|null $value */
            $value = $modelValues->get($definition->id);

            return $this->buildResolved($definition, $value, null);
        });
    }

    public function resolveForAsset(Asset $asset): Collection
    {
        $asset->loadMissing('model', 'attributeOverrides.option');
        $model = $asset->model;

        $definitions = $model->attributeDefinitionsForCategory()->with('options')->get();
        $modelValues = $model->specAttributes()->with('option')->get()->keyBy('attribute_definition_id');
        $overrides = $asset->attributeOverrides->keyBy('attribute_definition_id');

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
}
