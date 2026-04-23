<?php

namespace App\Services\Components;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use Illuminate\Support\Collection;

class AssetComponentRosterService
{
    public function buildForAsset(Asset $asset, ?ModelNumber $overrideModelNumber = null): AssetComponentRoster
    {
        $asset->loadMissing([
            'model.primaryModelNumber',
            'modelNumber.componentTemplates.componentDefinition.category',
            'modelNumber.componentTemplates.componentDefinition.manufacturer',
            'modelNumber.componentTemplates.componentDefinition.attributeContributions.definition.options',
            'modelNumber.componentTemplates.componentDefinition.attributeContributions.option',
            'expectedComponentStates',
            'sourcedComponents.componentDefinition.category',
            'sourcedComponents.componentDefinition.manufacturer',
            'sourcedComponents.storageLocation.siteLocation',
            'sourcedComponents.currentAsset.model',
            'trackedComponents.componentDefinition.category',
            'trackedComponents.componentDefinition.manufacturer',
            'trackedComponents.componentDefinition.attributeContributions.definition.options',
            'trackedComponents.componentDefinition.attributeContributions.option',
            'trackedComponents.storageLocation.siteLocation',
            'trackedComponents.heldBy',
            'trackedComponents.createdBy',
        ]);

        $modelNumber = $overrideModelNumber
            ?? $asset->modelNumber
            ?? $asset->model?->primaryModelNumber;

        if (!$modelNumber) {
            return new AssetComponentRoster($this->classifyTrackedOnly($asset->trackedComponents), []);
        }

        $templates = $modelNumber->componentTemplates
            ->sortBy(fn (ModelNumberComponentTemplate $template) => [$template->sort_order ?? 0, $template->id ?? 0])
            ->values();
        $stateByTemplate = $asset->expectedComponentStates->keyBy('model_number_component_template_id');
        $tracked = $asset->trackedComponents
            ->sortBy(fn (ComponentInstance $component) => [$component->updated_at?->timestamp ?? 0, $component->id])
            ->values();

        $trackedByDefinition = $tracked
            ->filter(fn (ComponentInstance $component) => $component->component_definition_id)
            ->groupBy('component_definition_id')
            ->map(fn (Collection $group) => $group->values());
        $removedByTemplate = $asset->sourcedComponents
            ->filter(function (ComponentInstance $component) use ($asset): bool {
                return $component->source_type === ComponentInstance::SOURCE_EXPECTED_BASELINE
                    && (int) data_get($component->metadata_json, 'model_number_component_template_id', 0) > 0
                    && (int) ($component->current_asset_id ?? 0) !== (int) $asset->id;
            })
            ->groupBy(fn (ComponentInstance $component) => (int) data_get($component->metadata_json, 'model_number_component_template_id'))
            ->map(fn (Collection $group) => $group->sortByDesc('updated_at')->values());

        $rows = collect();
        $templateSummaries = [];

        foreach ($templates as $template) {
            $expectedQty = max(1, (int) $template->expected_qty);
            $removedQty = min(
                $expectedQty,
                max(0, (int) ($stateByTemplate->get($template->id)?->removed_qty ?? 0))
            );
            $assumedQty = max(0, $expectedQty - $removedQty);
            $fillQty = 0;

            if ($template->component_definition_id) {
                $pool = $trackedByDefinition->get($template->component_definition_id, collect());
                $fillQty = min($removedQty, $pool->count());

                for ($i = 0; $i < $fillQty; $i++) {
                    /** @var ComponentInstance $component */
                    $component = $pool->shift();
                    $rows->push(new AssetComponentRosterRow(
                        'expected_tracked',
                        __('Expected (Tracked)'),
                        $component->display_name,
                        $template,
                        $component,
                        $component->installed_as,
                        true
                    ));
                }

                $trackedByDefinition->put($template->component_definition_id, $pool->values());
            }

            for ($i = 0; $i < $assumedQty; $i++) {
                $rows->push(new AssetComponentRosterRow(
                    'expected',
                    __('Expected'),
                    $template->expected_name ?: ($template->componentDefinition?->name ?? __('Expected component')),
                    $template,
                    null,
                    null,
                    false
                ));
            }

            foreach ($removedByTemplate->get($template->id, collect()) as $removedComponent) {
                $rows->push(new AssetComponentRosterRow(
                    'removed',
                    __('Removed'),
                    $removedComponent->display_name,
                    $template,
                    $removedComponent,
                    null,
                    true
                ));
            }

            $templateSummaries[$template->id] = [
                'template' => $template,
                'expected_qty' => $expectedQty,
                'removed_qty' => $removedQty,
                'assumed_qty' => $assumedQty,
                'filled_qty' => $fillQty,
            ];
        }

        foreach ($tracked as $component) {
            if (!$component->component_definition_id) {
                $rows->push(new AssetComponentRosterRow(
                    'custom',
                    __('Custom'),
                    $component->display_name,
                    null,
                    $component,
                    $component->installed_as,
                    true
                ));
                continue;
            }

            $remaining = $trackedByDefinition->get($component->component_definition_id, collect());
            $index = $remaining->search(fn (ComponentInstance $candidate) => $candidate->is($component));

            if ($index === false) {
                continue;
            }

            $rows->push(new AssetComponentRosterRow(
                'extra',
                __('Extra'),
                $component->display_name,
                null,
                $component,
                $component->installed_as,
                true
            ));

            $remaining->forget($index);
            $trackedByDefinition->put($component->component_definition_id, $remaining->values());
        }

        return new AssetComponentRoster($rows->values(), $templateSummaries);
    }

    /**
     * @param Collection<int, ComponentInstance> $trackedComponents
     * @return Collection<int, AssetComponentRosterRow>
     */
    private function classifyTrackedOnly(Collection $trackedComponents): Collection
    {
        return $trackedComponents
            ->sortBy(fn (ComponentInstance $component) => [$component->updated_at?->timestamp ?? 0, $component->id])
            ->map(function (ComponentInstance $component): AssetComponentRosterRow {
                return new AssetComponentRosterRow(
                    $component->component_definition_id ? 'extra' : 'custom',
                    $component->component_definition_id ? __('Extra') : __('Custom'),
                    $component->display_name,
                    null,
                    $component,
                    $component->installed_as,
                    true
                );
            })
            ->values();
    }
}
