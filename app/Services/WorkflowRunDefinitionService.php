<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use App\Services\Components\AssetComponentRosterRow;
use App\Services\Components\AssetComponentRosterService;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Support\Collection;

class WorkflowRunDefinitionService
{
    public function __construct(
        private readonly EffectiveAttributeResolver $attributeResolver,
        private readonly AssetComponentRosterService $rosterService
    ) {
    }

    /**
     * Build the live, applicable definition used both to create and validate a run.
     *
     * @return array{
     *     profile_items: Collection<int, WorkflowProfileItem>,
     *     resolved_attributes: Collection,
     *     resolved_by_definition: Collection,
     *     readiness_context_hash: string
     * }
     */
    public function forProfile(Asset $asset, WorkflowProfile $profile): array
    {
        foreach ([
            'model',
            'modelNumber',
            'expectedComponentStates',
            'sourcedComponents',
            'trackedComponents',
        ] as $relation) {
            $asset->unsetRelation($relation);
        }

        $resolved = $this->attributeResolver->resolveForAsset($asset);
        $roster = $this->rosterService->buildForAsset($asset);
        $resolvedByDefinition = $resolved->keyBy(
            fn ($attribute) => $attribute->definition->id
        );
        $applicableItemIds = TestType::query()
            ->forAsset($asset)
            ->pluck('id')
            ->all();

        $profile->load([
            'categories',
            'items.item.attributeDefinition',
            'items.item.categories',
            'items.item.componentCategories',
            'items.item.componentDefinitions',
        ]);
        $profileItems = $profile->items
            ->filter(fn (WorkflowProfileItem $profileItem): bool => $profileItem->item !== null
                && in_array($profileItem->workflow_item_id, $applicableItemIds, true))
            ->values();

        $context = [
            'schema' => 1,
            'asset' => [
                'model_id' => $asset->model_id !== null ? (int) $asset->model_id : null,
                'model_number_id' => $asset->model_number_id !== null ? (int) $asset->model_number_id : null,
            ],
            'profile' => [
                'id' => (int) $profile->id,
                'name' => (string) $profile->name,
                'slug' => (string) $profile->slug,
                'description' => (string) $profile->description,
                'is_active' => (bool) $profile->is_active,
                'is_default' => (bool) $profile->is_default,
                'blocks_sale_readiness' => (bool) $profile->blocks_sale_readiness,
                'display_order' => (int) $profile->display_order,
                'category_ids' => $profile->categories->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            ],
            'components' => $roster->rows
                ->filter(fn ($row): bool => $row instanceof AssetComponentRosterRow)
                ->map(fn (AssetComponentRosterRow $row): array => $this->componentRowContext($row))
                ->sortBy(fn (array $row): string => json_encode($row, JSON_THROW_ON_ERROR))
                ->values()
                ->all(),
            'items' => $profileItems->map(function (WorkflowProfileItem $profileItem) use ($resolvedByDefinition): array {
                $item = $profileItem->item;
                $attribute = $item->attribute_definition_id
                    ? $resolvedByDefinition->get($item->attribute_definition_id)
                    : null;

                return [
                    'profile_item_id' => (int) $profileItem->id,
                    'workflow_item_id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'slug' => (string) $item->slug,
                    'display_order' => (int) $item->display_order,
                    'tooltip' => (string) $item->tooltip,
                    'instructions' => (string) $item->instructions,
                    'legacy_category' => (string) $item->category,
                    'applies_to_all' => (bool) $item->applies_to_all,
                    'attribute_definition_id' => $item->attribute_definition_id !== null
                        ? (int) $item->attribute_definition_id
                        : null,
                    'attribute_definition' => $item->attributeDefinition ? [
                        'key' => (string) $item->attributeDefinition->key,
                        'label' => (string) $item->attributeDefinition->label,
                        'datatype' => (string) $item->attributeDefinition->datatype,
                        'unit' => (string) $item->attributeDefinition->unit,
                        'instructions' => (string) $item->attributeDefinition->getAttribute('instructions'),
                        'help_text' => (string) $item->attributeDefinition->getAttribute('help_text'),
                        'constraints' => $this->normalizeHashValue($item->attributeDefinition->constraints),
                        'required_for_category' => (bool) $item->attributeDefinition->required_for_category,
                        'allow_custom_values' => (bool) $item->attributeDefinition->allow_custom_values,
                        'allow_asset_override' => (bool) $item->attributeDefinition->allow_asset_override,
                        'version' => (int) $item->attributeDefinition->version,
                        'hidden_at' => $item->attributeDefinition->hidden_at?->toAtomString(),
                        'deprecated_at' => $item->attributeDefinition->deprecated_at?->toAtomString(),
                    ] : null,
                    'is_required' => (bool) $item->is_required,
                    'result_label_mode' => $item->result_label_mode
                        ?: WorkflowProfileItem::LABEL_MODE_PASS_FAIL,
                    'category_ids' => $item->categories->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                    'component_category_ids' => $item->componentCategories->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                    'component_definition_ids' => $item->componentDefinitions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                    'sort_order' => (int) $profileItem->sort_order,
                    'expected_value' => $this->normalizeHashValue($attribute?->value),
                    'expected_raw_value' => $this->normalizeHashValue($attribute?->rawValue),
                ];
            })->values()->all(),
        ];

        return [
            'profile_items' => $profileItems,
            'resolved_attributes' => $resolved,
            'resolved_by_definition' => $resolvedByDefinition,
            'readiness_context_hash' => hash(
                'sha256',
                json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ),
        ];
    }

    private function componentRowContext(AssetComponentRosterRow $row): array
    {
        $template = $row->template;
        $component = $row->component;
        $definition = $component?->componentDefinition ?? $template?->componentDefinition;

        return [
            'classification' => $row->classification,
            'quantity' => max(1, $row->quantity),
            'installed_as' => $row->installedAs,
            'template' => $template ? [
                'id' => (int) $template->id,
                'model_number_id' => (int) $template->model_number_id,
                'component_definition_id' => $template->component_definition_id !== null
                    ? (int) $template->component_definition_id
                    : null,
                'expected_name' => (string) $template->expected_name,
                'slot_name' => (string) $template->slot_name,
                'expected_qty' => (int) $template->expected_qty,
                'is_required' => (bool) $template->is_required,
                'sort_order' => (int) $template->sort_order,
                'metadata' => $this->normalizeHashValue($template->metadata_json),
            ] : null,
            'expected_subcomponents' => $definition?->subcomponentTemplates
                ->map(fn ($subcomponentTemplate): array => [
                    'id' => (int) $subcomponentTemplate->id,
                    'child_component_definition_id' => (int) $subcomponentTemplate->child_component_definition_id,
                    'expected_name' => (string) $subcomponentTemplate->expected_name,
                    'expected_qty' => (int) $subcomponentTemplate->expected_qty,
                    'is_required' => (bool) $subcomponentTemplate->is_required,
                    'sort_order' => (int) $subcomponentTemplate->sort_order,
                    'metadata' => $this->normalizeHashValue($subcomponentTemplate->metadata_json),
                ])
                ->sortBy('id')
                ->values()
                ->all() ?? [],
            'component' => $component ? [
                'id' => (int) $component->id,
                'component_definition_id' => $component->component_definition_id !== null
                    ? (int) $component->component_definition_id
                    : null,
                'parent_component_instance_id' => $component->parent_component_instance_id !== null
                    ? (int) $component->parent_component_instance_id
                    : null,
                'current_asset_id' => $component->current_asset_id !== null
                    ? (int) $component->current_asset_id
                    : null,
                'root_asset_id' => $component->root_asset_id !== null
                    ? (int) $component->root_asset_id
                    : null,
                'source_type' => (string) $component->source_type,
                'is_materialized_expected' => (bool) $component->is_materialized_expected,
                'status' => (string) $component->status,
                'lifecycle_status' => $component->effectiveLifecycleStatus(),
                'condition_code' => (string) $component->condition_code,
                'condition_status' => $component->effectiveConditionStatus(),
                'installed_as' => (string) $component->installed_as,
                'attributes' => $component->instanceAttributes
                    ->map(fn ($attribute): array => [
                        'attribute_definition_id' => (int) $attribute->attribute_definition_id,
                        'attribute_option_id' => $attribute->attribute_option_id !== null
                            ? (int) $attribute->attribute_option_id
                            : null,
                        'value' => $this->normalizeHashValue($attribute->value),
                        'raw_value' => $this->normalizeHashValue($attribute->raw_value),
                        'resolves_to_spec' => (bool) $attribute->resolves_to_spec,
                        'sort_order' => (int) $attribute->sort_order,
                    ])
                    ->sortBy(fn (array $attribute): string => json_encode($attribute, JSON_THROW_ON_ERROR))
                    ->values()
                    ->all(),
                'expected_subcomponent_states' => $component->expectedSubcomponentStates
                    ->map(fn ($state): array => [
                        'template_id' => (int) $state->component_definition_subcomponent_template_id,
                        'removed_qty' => (int) $state->removed_qty,
                        'materialized_qty' => (int) $state->materialized_qty,
                    ])
                    ->sortBy('template_id')
                    ->values()
                    ->all(),
            ] : null,
        ];
    }

    private function normalizeHashValue(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        } elseif (is_object($value)) {
            $value = method_exists($value, 'toArray') ? $value->toArray() : (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->normalizeHashValue($nestedValue);
        }

        return $value;
    }
}
