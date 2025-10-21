<?php

namespace Database\Seeders;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Services\ModelAttributes\AttributeValueService;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevicePresetSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    public function run(): void
    {
        if (!AttributeDefinition::whereIn('key', array_keys($this->attributeBlueprints()))->exists()) {
            $this->call(DeviceAttributeSeeder::class);
        }

        DB::transaction(function () {
            $this->seedModelCatalog();
        });
    }

    private function seedModelCatalog(): void
    {
        $definitions = AttributeDefinition::whereIn('key', array_keys($this->attributeBlueprints()))
            ->get()
            ->keyBy('key');

        if ($definitions->isEmpty()) {
            return;
        }

        /** @var AttributeValueService $valueService */
        $valueService = app(AttributeValueService::class);
        $blueprints = $this->modelBlueprints();

        foreach ($blueprints as $modelName => $config) {
            $model = AssetModel::where('name', $modelName)->first();

            if (!$model && isset($config['factory']) && is_callable($config['factory'])) {
                $model = $config['factory']();
            }

            if (!$model) {
                continue;
            }

            $modelNumber = $model->primaryModelNumber ?: $model->ensurePrimaryModelNumber();

            if (!empty($config['code']) && $modelNumber->code !== $config['code']) {
                $modelNumber->code = $config['code'];
            }

            if (!empty($config['label'])) {
                $modelNumber->label = $config['label'];
            }

            $modelNumber->save();

            if ($model->primary_model_number_id !== $modelNumber->id) {
                $model->forceFill([
                    'primary_model_number_id' => $modelNumber->id,
                    'model_number' => $modelNumber->code,
                ])->save();
            }

            $assignedDefinitionIds = [];
            $position = 0;

            foreach ($config['attributes'] as $key => $value) {
                /** @var AttributeDefinition|null $definition */
                $definition = $definitions->get($key);

                if (!$definition) {
                    continue;
                }

                try {
                    $tuple = $valueService->validateAndNormalize($definition, $value);
                } catch (\Throwable) {
                    continue;
                }

                $assignment = ModelNumberAttribute::firstOrNew([
                    'model_number_id' => $modelNumber->id,
                    'attribute_definition_id' => $definition->id,
                ]);

                $assignment->value = $tuple->value;
                $assignment->raw_value = $tuple->rawValue;
                $assignment->attribute_option_id = $tuple->attributeOptionId;
                $assignment->display_order = $position;
                $assignment->save();

                $assignedDefinitionIds[] = $definition->id;
                $position++;
            }

            if (!empty($assignedDefinitionIds)) {
                ModelNumberAttribute::query()
                    ->where('model_number_id', $modelNumber->id)
                    ->whereNotIn('attribute_definition_id', $assignedDefinitionIds)
                    ->delete();
            }
        }
    }
}
