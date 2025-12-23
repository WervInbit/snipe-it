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

        return $this->resolveWithContext($modelNumber, collect());
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

        return $this->resolveWithContext($modelNumber, $overrides);
    }

    protected function requiresTest(AttributeDefinition $definition): bool
    {
        if ($definition->relationLoaded('tests')) {
            return $definition->tests->isNotEmpty();
        }

        return $definition->tests()->exists();
    }

    public function createResolved(AttributeDefinition $definition, ?ModelNumberAttribute $modelValue = null, ?AssetAttributeOverride $override = null, bool $isOverride = false): ResolvedAttribute
    {
        $modelValueString = $modelValue?->value;
        $modelRaw = $modelValue?->raw_value;
        $requiresTest = $this->requiresTest($definition);

        if ($override && $isOverride) {
            $override->loadMissing('option');

            return new ResolvedAttribute(
                $definition,
                $override->value,
                $override->raw_value,
                $override->option,
                'override',
                $requiresTest,
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
                $requiresTest,
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
            $requiresTest,
            false,
            $modelValueString,
            $modelRaw
        );
    }

    private function resolveWithContext(ModelNumber $modelNumber, Collection $overrides): Collection
    {
        $assignments = $modelNumber->attributes()->with(['definition.options', 'definition.tests', 'option'])->get();

        return $assignments->map(function (ModelNumberAttribute $assignment) use ($overrides) {
            $definition = $assignment->definition;
            $override = $overrides->get($definition->id);

            if ($override && $definition->allow_asset_override) {
                return $this->createResolved($definition, $assignment, $override, true);
            }

            return $this->createResolved($definition, $assignment, null);
        });
    }
}
