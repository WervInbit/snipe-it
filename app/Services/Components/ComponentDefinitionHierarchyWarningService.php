<?php

namespace App\Services\Components;

use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use Illuminate\Support\Collection;

class ComponentDefinitionHierarchyWarningService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function overlapWarnings(ComponentDefinition $definition): Collection
    {
        $definition->loadMissing([
            'subcomponentTemplates.childComponentDefinition',
        ]);

        return $definition->subcomponentTemplates
            ->flatMap(function ($template) use ($definition): Collection {
                $childDefinition = $template->childComponentDefinition;

                if (!$childDefinition) {
                    return collect();
                }

                return $this->overlapWarningsForPair(
                    $definition,
                    $childDefinition,
                    $template->expected_name ?: $childDefinition->name
                );
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function overlapWarningsForPair(
        ComponentDefinition $parentDefinition,
        ComponentDefinition $childDefinition,
        ?string $childExpectedName = null
    ): Collection {
        $parentDefinition->loadMissing('attributeContributions.definition');
        $childDefinition->loadMissing('attributeContributions.definition');

        $parentContributions = $parentDefinition->attributeContributions
            ->filter(fn (ComponentDefinitionAttribute $contribution) => $this->isCalculatedSpecContribution($contribution))
            ->keyBy('attribute_definition_id');

        if ($parentContributions->isEmpty()) {
            return collect();
        }

        return $childDefinition->attributeContributions
            ->filter(fn (ComponentDefinitionAttribute $contribution) => $this->isCalculatedSpecContribution($contribution))
            ->map(function (ComponentDefinitionAttribute $childContribution) use ($parentDefinition, $childDefinition, $childExpectedName, $parentContributions): ?array {
                /** @var ComponentDefinitionAttribute|null $parentContribution */
                $parentContribution = $parentContributions->get($childContribution->attribute_definition_id);

                if (!$parentContribution) {
                    return null;
                }

                return [
                    'attribute_definition_id' => $childContribution->attribute_definition_id,
                    'attribute_label' => $childContribution->definition?->label ?? __('Unknown attribute'),
                    'parent_definition_id' => $parentDefinition->id,
                    'parent_definition_name' => $parentDefinition->name,
                    'parent_value' => $parentContribution->value,
                    'child_definition_id' => $childDefinition->id,
                    'child_definition_name' => $childDefinition->name,
                    'child_expected_name' => $childExpectedName ?: $childDefinition->name,
                    'child_value' => $childContribution->value,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param Collection<int, ComponentDefinition> $definitions
     * @return Collection<int, Collection<int, array<string, mixed>>>
     */
    public function overlapWarningsByDefinition(Collection $definitions): Collection
    {
        return $definitions
            ->mapWithKeys(fn (ComponentDefinition $definition) => [
                $definition->id => $this->overlapWarnings($definition),
            ]);
    }

    private function isCalculatedSpecContribution(ComponentDefinitionAttribute $contribution): bool
    {
        return (bool) $contribution->resolves_to_spec
            && (bool) $contribution->definition?->isNumericDatatype();
    }
}
