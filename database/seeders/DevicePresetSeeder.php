<?php

namespace Database\Seeders;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberAttribute;
use App\Services\ModelAttributes\AttributeValueService;
use App\Services\ModelAttributes\ModelAttributeManager;
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
        $blueprints = $this->seedableModelBlueprints();

        foreach ($blueprints as $modelName => $config) {
            $model = AssetModel::where('name', $modelName)->first();

            if (!$model && isset($config['factory']) && is_callable($config['factory'])) {
                $model = $config['factory']();
            }

            if (!$model) {
                continue;
            }

            $seedCode = trim((string) ($config['code'] ?? ''));
            /** @var ModelNumber|null $modelNumber */
            $modelNumber = $seedCode !== ''
                ? $model->modelNumbers()->where('code', $seedCode)->first()
                : null;

            if (!$modelNumber) {
                $modelNumber = $seedCode !== ''
                    ? $model->modelNumbers()->create(['code' => $seedCode])
                    : ($model->primaryModelNumber ?: $model->ensurePrimaryModelNumber());
            }

            if (!empty($config['label'])) {
                $modelNumber->label = $config['label'];
            }

            $modelNumber->save();

            if (!$model->primary_model_number_id) {
                $model->forceFill([
                    'primary_model_number_id' => $modelNumber->id,
                    'model_number' => $modelNumber->code,
                ])->save();
            } elseif ((int) $model->primary_model_number_id === (int) $modelNumber->id
                && $model->model_number !== $modelNumber->code) {
                $model->forceFill(['model_number' => $modelNumber->code])->save();
            }

            $position = 0;
            $modelNumber->unsetRelation('componentTemplates');
            $componentBackedDefinitionIds = app(ModelAttributeManager::class)
                ->componentResolvedSpecDefinitionIds($modelNumber);

            foreach ($config['attributes'] as $key => $value) {
                /** @var AttributeDefinition|null $definition */
                $definition = $definitions->get($key);

                if (!$definition) {
                    continue;
                }

                if (in_array((int) $definition->id, $componentBackedDefinitionIds, true)) {
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

                $position++;
            }
        }
    }
}
