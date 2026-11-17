<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\ModelNumber;

class DraftAssetWorkflowContextFactory
{
    public function make(AssetModel $model, ModelNumber $modelNumber): Asset
    {
        $model->loadMissing('category', 'primaryModelNumber');
        $modelNumber->loadMissing([
            'model.category',
            'componentTemplates.componentDefinition.category',
            'componentTemplates.componentDefinition.manufacturer',
            'componentTemplates.componentDefinition.subcomponentTemplates.childComponentDefinition.category',
            'componentTemplates.componentDefinition.subcomponentTemplates.childComponentDefinition.manufacturer',
            'componentTemplates.componentDefinition.subcomponentTemplates.childComponentDefinition.attributeContributions.definition.options',
            'componentTemplates.componentDefinition.subcomponentTemplates.childComponentDefinition.attributeContributions.option',
            'componentTemplates.componentDefinition.attributeContributions.definition.options',
            'componentTemplates.componentDefinition.attributeContributions.option',
            'attributes.definition.options',
            'attributes.option',
        ]);

        $asset = new Asset();
        $asset->forceFill([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);
        $asset->setRelation('model', $model);
        $asset->setRelation('modelNumber', $modelNumber);

        foreach ([
            'attributeOverrides',
            'expectedComponentStates',
            'sourcedComponents',
            'trackedComponents',
        ] as $relation) {
            $asset->setRelation($relation, collect());
        }

        return $asset;
    }
}
