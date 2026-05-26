<?php

namespace App\Services\Components;

use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentExpectedSubcomponentState;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ComponentExpectedSubcomponentService
{
    public function __construct(
        private readonly ComponentLifecycleService $lifecycle,
    ) {
    }

    public function materializeAttachedChild(
        ComponentInstance $parent,
        ComponentDefinitionSubcomponentTemplate $template,
        User|int|null $performedBy = null,
        array $context = [],
    ): ComponentInstance {
        $parent->loadMissing(['componentDefinition', 'currentAsset']);
        $template->loadMissing('childComponentDefinition');

        if (!$parent->component_definition_id || (int) $template->parent_component_definition_id !== (int) $parent->component_definition_id) {
            throw new InvalidArgumentException('Expected subcomponent does not belong to this component definition.');
        }

        if ($parent->componentDefinition && !$parent->componentDefinition->canBeInstalledOnAsset()) {
            throw new InvalidArgumentException('Subcomponent-only definitions cannot act as top-level parents for expected subcomponents.');
        }

        if ($parent->parent_component_instance_id) {
            throw new InvalidArgumentException('Expected subcomponents can only be materialized from top-level parent components.');
        }

        if ($template->childComponentDefinition && !$template->childComponentDefinition->canBeUsedAsSubcomponent()) {
            throw new InvalidArgumentException('This child component definition is restricted to direct asset placement and cannot be used as a subcomponent.');
        }

        if ($parent->effectiveLifecycleStatus() !== ComponentInstance::LIFECYCLE_ATTACHED || !$parent->current_asset_id) {
            throw new InvalidArgumentException('Expected subcomponents can only be materialized while the parent component is installed.');
        }

        $this->lifecycle->assertConditionWarningConfirmedForCondition(
            ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION,
            $context,
            $template->expected_name ?: $template->childComponentDefinition?->name ?: 'Expected subcomponent',
        );

        return DB::transaction(function () use ($parent, $template, $performedBy, $context): ComponentInstance {
            $state = ComponentExpectedSubcomponentState::query()->firstOrCreate(
                [
                    'component_instance_id' => $parent->id,
                    'component_definition_subcomponent_template_id' => $template->id,
                ],
                [
                    'removed_qty' => 0,
                    'materialized_qty' => 0,
                ]
            );

            $expectedQty = max(1, (int) $template->expected_qty);
            $depletedQty = max(0, (int) $state->removed_qty) + max(0, (int) $state->materialized_qty);

            if ($depletedQty >= $expectedQty) {
                throw new InvalidArgumentException('All expected units for this subcomponent have already been materialized or removed.');
            }

            $reason = trim((string) ($context['reason'] ?? $context['note'] ?? 'Tracked from expected subcomponent'));
            if ($reason === '') {
                $reason = 'Tracked from expected subcomponent';
            }

            $state->forceFill([
                'materialized_qty' => $state->materialized_qty + 1,
            ])->save();

            return $this->lifecycle->createInstance([
                'component_definition_id' => $template->child_component_definition_id,
                'company_id' => $parent->company_id,
                'source_type' => ComponentInstance::SOURCE_EXPECTED_BASELINE,
                'source_asset_id' => $parent->current_asset_id,
                'current_asset_id' => $parent->current_asset_id,
                'parent_component_instance_id' => $parent->id,
                'root_asset_id' => $parent->root_asset_id ?: $parent->current_asset_id,
                'status' => ComponentInstance::STATUS_INSTALLED,
                'lifecycle_status' => ComponentInstance::LIFECYCLE_ATTACHED,
                'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
                'display_name' => $template->expected_name ?: $template->childComponentDefinition?->name ?: 'Expected subcomponent',
                'serial' => null,
                'is_materialized_expected' => true,
                'materialized_reason' => $reason,
                'notes' => $context['note'] ?? $reason,
                'metadata_json' => array_filter([
                    'expected_subcomponent' => true,
                    'component_definition_subcomponent_template_id' => $template->id,
                    'parent_component_instance_id' => $parent->id,
                    'parent_component_definition_id' => $parent->component_definition_id,
                    'materialized_from_asset_id' => $parent->current_asset_id,
                ]),
            ], $performedBy);
        });
    }
}
