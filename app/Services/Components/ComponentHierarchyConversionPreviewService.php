<?php

namespace App\Services\Components;

use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ComponentHierarchyConversionPreviewService
{
    private const PARENT_KEYWORDS = [
        'motherboard',
        'main board',
        'system board',
        'logic board',
        'i/o board',
        'io board',
        'display assembly',
        'screen assembly',
        'panel',
        'dock',
        'hub',
        'assembly',
    ];

    private const EMBEDDED_CHILD_KEYWORDS = [
        'port',
        'usb',
        'usb-c',
        'usb c',
        'usb-a',
        'usb a',
        'hdmi',
        'vga',
        'displayport',
        'rj45',
        'ethernet',
        'jack',
        'reader',
        'fingerprint',
        'webcam',
        'camera',
        'module',
        'daughterboard',
        'daughter board',
        'connector',
    ];

    private const SERVICEABLE_CHILD_KEYWORDS = [
        'ram',
        'memory',
        'sodimm',
        'storage',
        'ssd',
        'hdd',
        'battery',
    ];

    public function __construct(
        private readonly ComponentDefinitionHierarchyWarningService $warningService
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function buildReport(array $options = []): array
    {
        $includeInactive = (bool) ($options['include_inactive'] ?? false);

        $definitions = $this->componentDefinitions($includeInactive);
        $modelTemplates = $this->modelNumberTemplates($includeInactive);
        $existingTemplates = $this->existingSubcomponentTemplates($includeInactive);
        $existingPairKeys = $this->existingPairKeys($existingTemplates);

        $candidateParents = $this->candidateParents($definitions);
        $candidateChildren = $this->candidateChildren($definitions);
        $suggestions = $this->suggestTemplates(
            $modelTemplates,
            $candidateParents->keys(),
            $candidateChildren->keys(),
            $definitions,
            $existingPairKeys
        );

        $parentSuggestionCounts = collect($suggestions)->countBy('parent_definition_id');
        $childSuggestionCounts = collect($suggestions)->countBy('child_definition_id');

        return [
            'summary' => [
                'read_only' => true,
                'include_inactive' => $includeInactive,
                'component_definitions_scanned' => $definitions->count(),
                'model_number_component_templates_scanned' => $modelTemplates->count(),
                'existing_subcomponent_templates' => $existingTemplates->count(),
                'candidate_parent_definitions' => $candidateParents->count(),
                'candidate_child_definitions' => $candidateChildren->count(),
                'suggested_subcomponent_templates' => count($suggestions),
                'suggestions_with_overlap_warnings' => collect($suggestions)
                    ->filter(fn (array $suggestion) => !empty($suggestion['overlap_warnings']))
                    ->count(),
            ],
            'detection_rules' => [
                'candidate_parent' => 'Active component definitions already used as top-level model-number expected components and matching assembly/board/display/dock/hub naming, plus definitions that already have expected subcomponent templates.',
                'candidate_child' => 'Active component definitions marked subcomponent-only, already used as an expected child, or matching embedded/serviceable child naming such as ports, readers, modules, RAM, storage, or battery.',
                'template_suggestion' => 'Suggest parent-child templates only when a candidate parent and candidate child currently co-occur as top-level expected components on the same model number; existing parent-child templates are skipped.',
                'overlap_warning' => 'Report numeric calculated spec overlaps when both parent and child definitions contribute the same attribute; warnings are advisory only.',
            ],
            'candidate_parents' => $this->attachSuggestionCounts($candidateParents, $parentSuggestionCounts),
            'candidate_children' => $this->attachSuggestionCounts($candidateChildren, $childSuggestionCounts),
            'suggested_templates' => $suggestions,
            'existing_overlap_warnings' => $this->existingOverlapWarnings($existingTemplates),
        ];
    }

    /**
     * @return Collection<int, ComponentDefinition>
     */
    private function componentDefinitions(bool $includeInactive): Collection
    {
        return ComponentDefinition::query()
            ->with([
                'category',
                'manufacturer',
                'attributeContributions.definition',
                'subcomponentTemplates.childComponentDefinition.attributeContributions.definition',
            ])
            ->withCount([
                'expectedTemplates as model_template_count',
                'subcomponentTemplates as existing_child_template_count',
                'usedAsSubcomponentTemplates as existing_parent_template_count',
            ])
            ->when(!$includeInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return Collection<int, ModelNumberComponentTemplate>
     */
    private function modelNumberTemplates(bool $includeInactive): Collection
    {
        return ModelNumberComponentTemplate::query()
            ->with([
                'modelNumber.model',
                'componentDefinition.category',
                'componentDefinition.manufacturer',
                'componentDefinition.attributeContributions.definition',
            ])
            ->whereNotNull('component_definition_id')
            ->when(!$includeInactive, function ($query) {
                $query->whereHas('componentDefinition', fn ($definitionQuery) => $definitionQuery->where('is_active', true));
            })
            ->orderBy('model_number_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ComponentDefinitionSubcomponentTemplate>
     */
    private function existingSubcomponentTemplates(bool $includeInactive): Collection
    {
        return ComponentDefinitionSubcomponentTemplate::query()
            ->with([
                'parentComponentDefinition.attributeContributions.definition',
                'childComponentDefinition.attributeContributions.definition',
            ])
            ->when(!$includeInactive, function ($query) {
                $query->whereHas('parentComponentDefinition', fn ($definitionQuery) => $definitionQuery->where('is_active', true))
                    ->where(function ($templateQuery) {
                        $templateQuery->whereNull('child_component_definition_id')
                            ->orWhereHas('childComponentDefinition', fn ($definitionQuery) => $definitionQuery->where('is_active', true));
                    });
            })
            ->orderBy('parent_component_definition_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Collection<int, ComponentDefinitionSubcomponentTemplate> $existingTemplates
     * @return array<string, bool>
     */
    private function existingPairKeys(Collection $existingTemplates): array
    {
        return $existingTemplates
            ->filter(fn (ComponentDefinitionSubcomponentTemplate $template) => $template->child_component_definition_id !== null)
            ->mapWithKeys(fn (ComponentDefinitionSubcomponentTemplate $template) => [
                $this->pairKey($template->parent_component_definition_id, $template->child_component_definition_id) => true,
            ])
            ->all();
    }

    /**
     * @param Collection<int, ComponentDefinition> $definitions
     * @return Collection<int, array<string, mixed>>
     */
    private function candidateParents(Collection $definitions): Collection
    {
        return $definitions
            ->mapWithKeys(function (ComponentDefinition $definition): array {
                $reasons = [];

                if ((int) $definition->existing_child_template_count > 0) {
                    $reasons[] = 'already_has_expected_children';
                }

                if ((int) $definition->model_template_count > 0 && $this->matchesKeywords($definition, self::PARENT_KEYWORDS)) {
                    $reasons[] = 'top_level_expected_parent_keyword';
                }

                if (empty($reasons)) {
                    return [];
                }

                return [
                    $definition->id => $this->definitionSummary($definition, $reasons),
                ];
            });
    }

    /**
     * @param Collection<int, ComponentDefinition> $definitions
     * @return Collection<int, array<string, mixed>>
     */
    private function candidateChildren(Collection $definitions): Collection
    {
        return $definitions
            ->mapWithKeys(function (ComponentDefinition $definition): array {
                $reasons = [];

                if ((int) $definition->existing_parent_template_count > 0) {
                    $reasons[] = 'already_used_as_expected_child';
                }

                if ($definition->placement_mode === ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY) {
                    $reasons[] = 'placement_mode_subcomponent_only';
                }

                if ($this->matchesKeywords($definition, self::EMBEDDED_CHILD_KEYWORDS)) {
                    $reasons[] = 'embedded_child_keyword';
                }

                if ($this->matchesKeywords($definition, self::SERVICEABLE_CHILD_KEYWORDS)) {
                    $reasons[] = 'serviceable_child_keyword';
                }

                if (empty($reasons)) {
                    return [];
                }

                return [
                    $definition->id => $this->definitionSummary($definition, array_values(array_unique($reasons))),
                ];
            });
    }

    /**
     * @param Collection<int, ModelNumberComponentTemplate> $modelTemplates
     * @param Collection<int, int> $parentIds
     * @param Collection<int, int> $childIds
     * @param Collection<int, ComponentDefinition> $definitions
     * @param array<string, bool> $existingPairKeys
     * @return array<int, array<string, mixed>>
     */
    private function suggestTemplates(
        Collection $modelTemplates,
        Collection $parentIds,
        Collection $childIds,
        Collection $definitions,
        array $existingPairKeys
    ): array {
        $parentIdSet = array_flip($parentIds->all());
        $childIdSet = array_flip($childIds->all());
        $suggestions = [];

        foreach ($modelTemplates->groupBy('model_number_id') as $templatesForModel) {
            $parents = $templatesForModel
                ->filter(fn (ModelNumberComponentTemplate $template) => isset($parentIdSet[$template->component_definition_id]));
            $children = $templatesForModel
                ->filter(fn (ModelNumberComponentTemplate $template) => isset($childIdSet[$template->component_definition_id]));

            foreach ($parents as $parentTemplate) {
                foreach ($children as $childTemplate) {
                    if ($parentTemplate->component_definition_id === $childTemplate->component_definition_id) {
                        continue;
                    }

                    $pairKey = $this->pairKey($parentTemplate->component_definition_id, $childTemplate->component_definition_id);

                    if (isset($existingPairKeys[$pairKey])) {
                        continue;
                    }

                    /** @var ComponentDefinition|null $parentDefinition */
                    $parentDefinition = $definitions->get($parentTemplate->component_definition_id);
                    /** @var ComponentDefinition|null $childDefinition */
                    $childDefinition = $definitions->get($childTemplate->component_definition_id);

                    if (!$parentDefinition || !$childDefinition) {
                        continue;
                    }

                    if (!isset($suggestions[$pairKey])) {
                        $suggestions[$pairKey] = [
                            'parent_definition_id' => $parentDefinition->id,
                            'parent_name' => $parentDefinition->name,
                            'parent_part_code' => $parentDefinition->part_code,
                            'child_definition_id' => $childDefinition->id,
                            'child_name' => $childDefinition->name,
                            'child_part_code' => $childDefinition->part_code,
                            'expected_names' => [],
                            'child_quantities' => [],
                            'model_numbers' => [],
                            'confidence' => $this->suggestionConfidence($parentDefinition, $childDefinition),
                            'reasons' => $this->suggestionReasons($parentDefinition, $childDefinition),
                        ];
                    }

                    $expectedName = $childTemplate->expected_name ?: $childDefinition->name;
                    $suggestions[$pairKey]['expected_names'][$expectedName] = ($suggestions[$pairKey]['expected_names'][$expectedName] ?? 0) + 1;
                    $suggestions[$pairKey]['child_quantities'][] = max(1, (int) ($childTemplate->expected_qty ?: 1));

                    if ($childTemplate->modelNumber) {
                        $suggestions[$pairKey]['model_numbers'][$childTemplate->model_number_id] = $this->modelNumberLabel($childTemplate->modelNumber);
                    }
                }
            }
        }

        return collect($suggestions)
            ->map(function (array $suggestion) use ($definitions): array {
                $expectedName = collect($suggestion['expected_names'])
                    ->sortDesc()
                    ->keys()
                    ->first() ?: $suggestion['child_name'];
                $suggestedQty = max($suggestion['child_quantities'] ?: [1]);
                /** @var ComponentDefinition $parentDefinition */
                $parentDefinition = $definitions->get($suggestion['parent_definition_id']);
                /** @var ComponentDefinition $childDefinition */
                $childDefinition = $definitions->get($suggestion['child_definition_id']);

                $overlapWarnings = $this->warningService
                    ->overlapWarningsForPair($parentDefinition, $childDefinition, $expectedName)
                    ->map(fn (array $warning) => $this->warningSummary($warning))
                    ->values()
                    ->all();

                unset($suggestion['expected_names'], $suggestion['child_quantities']);

                return array_merge($suggestion, [
                    'suggested_expected_name' => $expectedName,
                    'suggested_expected_qty' => $suggestedQty,
                    'model_number_count' => count($suggestion['model_numbers']),
                    'example_model_numbers' => array_slice(array_values($suggestion['model_numbers']), 0, 5),
                    'overlap_warnings' => $overlapWarnings,
                ]);
            })
            ->sort(function (array $a, array $b): int {
                $countComparison = $b['model_number_count'] <=> $a['model_number_count'];

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcmp($a['parent_name'].' '.$a['child_name'], $b['parent_name'].' '.$b['child_name']);
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, ComponentDefinitionSubcomponentTemplate> $existingTemplates
     * @return array<int, array<string, mixed>>
     */
    private function existingOverlapWarnings(Collection $existingTemplates): array
    {
        return $existingTemplates
            ->flatMap(function (ComponentDefinitionSubcomponentTemplate $template): Collection {
                $parentDefinition = $template->parentComponentDefinition;
                $childDefinition = $template->childComponentDefinition;

                if (!$parentDefinition || !$childDefinition) {
                    return collect();
                }

                return $this->warningService
                    ->overlapWarningsForPair($parentDefinition, $childDefinition, $template->expected_name ?: $childDefinition->name)
                    ->map(fn (array $warning) => array_merge([
                        'template_id' => $template->id,
                    ], $this->warningSummary($warning)));
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $candidates
     * @param Collection<int|string, int> $suggestionCounts
     * @return array<int, array<string, mixed>>
     */
    private function attachSuggestionCounts(Collection $candidates, Collection $suggestionCounts): array
    {
        return $candidates
            ->map(function (array $candidate) use ($suggestionCounts): array {
                $candidate['suggestion_count'] = (int) ($suggestionCounts->get($candidate['component_definition_id'], 0));

                return $candidate;
            })
            ->sort(function (array $a, array $b): int {
                $countComparison = $b['suggestion_count'] <=> $a['suggestion_count'];

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcmp($a['name'], $b['name']);
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $reasons
     * @return array<string, mixed>
     */
    private function definitionSummary(ComponentDefinition $definition, array $reasons): array
    {
        return [
            'component_definition_id' => $definition->id,
            'name' => $definition->name,
            'part_code' => $definition->part_code,
            'category' => $definition->category?->name,
            'manufacturer' => $definition->manufacturer?->name,
            'placement_mode' => $definition->placement_mode,
            'model_template_count' => (int) $definition->model_template_count,
            'existing_child_template_count' => (int) $definition->existing_child_template_count,
            'existing_parent_template_count' => (int) $definition->existing_parent_template_count,
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function suggestionReasons(ComponentDefinition $parentDefinition, ComponentDefinition $childDefinition): array
    {
        $reasons = ['same_model_number_expected_components'];

        if ($this->matchesKeywords($parentDefinition, self::PARENT_KEYWORDS)) {
            $reasons[] = 'parent_keyword';
        }

        if ($this->matchesKeywords($childDefinition, self::EMBEDDED_CHILD_KEYWORDS)) {
            $reasons[] = 'embedded_child_keyword';
        }

        if ($this->matchesKeywords($childDefinition, self::SERVICEABLE_CHILD_KEYWORDS)) {
            $reasons[] = 'serviceable_child_keyword';
        }

        if ($childDefinition->placement_mode === ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY) {
            $reasons[] = 'child_subcomponent_only';
        }

        return $reasons;
    }

    private function suggestionConfidence(ComponentDefinition $parentDefinition, ComponentDefinition $childDefinition): string
    {
        if ($this->matchesKeywords($parentDefinition, self::PARENT_KEYWORDS)
            && $this->matchesKeywords($childDefinition, self::EMBEDDED_CHILD_KEYWORDS)) {
            return 'high_review_required';
        }

        return 'review_required';
    }

    /**
     * @param array<string, mixed> $warning
     * @return array<string, mixed>
     */
    private function warningSummary(array $warning): array
    {
        return [
            'attribute_definition_id' => $warning['attribute_definition_id'],
            'attribute_label' => $warning['attribute_label'],
            'parent_value' => $warning['parent_value'],
            'child_value' => $warning['child_value'],
        ];
    }

    private function matchesKeywords(ComponentDefinition $definition, array $keywords): bool
    {
        return Str::contains($this->searchableDefinitionText($definition), $keywords);
    }

    private function searchableDefinitionText(ComponentDefinition $definition): string
    {
        return Str::lower(implode(' ', array_filter([
            $definition->name,
            $definition->part_code,
            $definition->model_number,
            $definition->spec_summary,
        ])));
    }

    private function pairKey(int $parentDefinitionId, int $childDefinitionId): string
    {
        return $parentDefinitionId.':'.$childDefinitionId;
    }

    private function modelNumberLabel(ModelNumber $modelNumber): string
    {
        $modelName = $modelNumber->model?->name;
        $numberName = $modelNumber->name ?: $modelNumber->code;

        return $modelName ? "{$modelName} / {$numberName}" : $numberName;
    }
}
