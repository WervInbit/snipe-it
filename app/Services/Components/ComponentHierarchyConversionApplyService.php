<?php

namespace App\Services\Components;

use App\Models\ComponentDefinitionSubcomponentTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ComponentHierarchyConversionApplyService
{
    public function __construct(
        private readonly ComponentHierarchyConversionPreviewService $previewService
    ) {
    }

    /**
     * @param array<int, string> $selectedPairs
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function dryRun(array $selectedPairs, array $options = []): array
    {
        return $this->buildPlan($selectedPairs, $options + ['apply' => false]);
    }

    /**
     * @param array<int, string> $selectedPairs
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function apply(array $selectedPairs, array $options = []): array
    {
        $plan = $this->buildPlan($selectedPairs, $options + ['apply' => true]);

        if (empty($plan['templates_to_create'])) {
            return $plan;
        }

        $createdTemplates = DB::transaction(function () use ($plan): array {
            $sortOrdersByParent = $this->nextSortOrdersByParent(collect($plan['templates_to_create'])
                ->pluck('parent_definition_id')
                ->unique()
                ->all());

            return collect($plan['templates_to_create'])
                ->map(function (array $templatePlan) use (&$sortOrdersByParent): array {
                    $parentDefinitionId = (int) $templatePlan['parent_definition_id'];
                    $sortOrder = $sortOrdersByParent[$parentDefinitionId] ?? 0;
                    $sortOrdersByParent[$parentDefinitionId] = $sortOrder + 1;

                    $template = ComponentDefinitionSubcomponentTemplate::create([
                        'parent_component_definition_id' => $parentDefinitionId,
                        'child_component_definition_id' => $templatePlan['child_definition_id'],
                        'expected_name' => $templatePlan['suggested_expected_name'],
                        'expected_qty' => $templatePlan['suggested_expected_qty'],
                        'is_required' => true,
                        'sort_order' => $sortOrder,
                        'metadata_json' => [
                            'created_by_command' => 'component-hierarchy:apply-conversion',
                            'source' => 'component_hierarchy_preview',
                            'source_model_numbers' => $templatePlan['model_numbers'],
                            'confidence' => $templatePlan['confidence'],
                            'reasons' => $templatePlan['reasons'],
                            'applied_at' => now()->toISOString(),
                        ],
                        'notes' => 'Created by component hierarchy conversion tooling. Review before production use.',
                    ]);

                    return [
                        'template_id' => $template->id,
                        'parent_definition_id' => $template->parent_component_definition_id,
                        'parent_name' => $templatePlan['parent_name'],
                        'child_definition_id' => $template->child_component_definition_id,
                        'child_name' => $templatePlan['child_name'],
                        'expected_name' => $template->expected_name,
                        'expected_qty' => $template->expected_qty,
                        'sort_order' => $template->sort_order,
                    ];
                })
                ->all();
        });

        $plan['summary']['templates_created'] = count($createdTemplates);
        $plan['created_templates'] = $createdTemplates;
        $plan['rollback'] = $this->rollbackGuidance($createdTemplates);

        return $plan;
    }

    /**
     * @param array<int, string> $selectedPairs
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildPlan(array $selectedPairs, array $options): array
    {
        $pairKeys = $this->normalizePairs($selectedPairs);

        if (empty($pairKeys)) {
            throw new InvalidArgumentException('Select at least one parent:child pair with --pair=parent_id:child_id.');
        }

        $report = $this->previewService->buildReport([
            'include_inactive' => (bool) ($options['include_inactive'] ?? false),
        ]);

        $suggestionsByPair = collect($report['suggested_templates'])
            ->keyBy(fn (array $suggestion) => $this->pairKey($suggestion['parent_definition_id'], $suggestion['child_definition_id']));

        $available = [];
        $unavailable = [];

        foreach ($pairKeys as $pairKey) {
            $suggestion = $suggestionsByPair->get($pairKey);

            if (!$suggestion) {
                $unavailable[] = [
                    'pair' => $pairKey,
                    'reason' => 'No current preview suggestion for this pair. It may already exist, lack same-model-number evidence, or be filtered by active status.',
                ];

                continue;
            }

            $available[] = $suggestion;
        }

        return [
            'summary' => [
                'apply' => (bool) ($options['apply'] ?? false),
                'dry_run' => !(bool) ($options['apply'] ?? false),
                'selected_pairs' => count($pairKeys),
                'templates_to_create' => count($available),
                'unavailable_pairs' => count($unavailable),
                'templates_created' => 0,
            ],
            'templates_to_create' => $available,
            'unavailable_pairs' => $unavailable,
            'created_templates' => [],
            'rollback' => $this->rollbackGuidance([]),
        ];
    }

    /**
     * @param array<int, string> $selectedPairs
     * @return array<int, string>
     */
    private function normalizePairs(array $selectedPairs): array
    {
        return collect($selectedPairs)
            ->flatMap(fn ($pair) => is_array($pair) ? $pair : explode(',', (string) $pair))
            ->map(fn ($pair) => trim((string) $pair))
            ->filter()
            ->map(function (string $pair): string {
                if (!preg_match('/^\d+:\d+$/', $pair)) {
                    throw new InvalidArgumentException("Invalid pair '{$pair}'. Expected parent_id:child_id.");
                }

                [$parentDefinitionId, $childDefinitionId] = array_map('intval', explode(':', $pair, 2));

                if ($parentDefinitionId === $childDefinitionId) {
                    throw new InvalidArgumentException("Invalid pair '{$pair}'. Parent and child definitions must differ.");
                }

                return $this->pairKey($parentDefinitionId, $childDefinitionId);
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $parentDefinitionIds
     * @return array<int, int>
     */
    private function nextSortOrdersByParent(array $parentDefinitionIds): array
    {
        return collect($parentDefinitionIds)
            ->mapWithKeys(function (int $parentDefinitionId): array {
                $maxSortOrder = ComponentDefinitionSubcomponentTemplate::query()
                    ->where('parent_component_definition_id', $parentDefinitionId)
                    ->max('sort_order');

                return [$parentDefinitionId => $maxSortOrder === null ? 0 : ((int) $maxSortOrder + 1)];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $createdTemplates
     * @return array<string, mixed>
     */
    private function rollbackGuidance(array $createdTemplates): array
    {
        $ids = collect($createdTemplates)
            ->pluck('template_id')
            ->filter()
            ->values()
            ->all();

        return [
            'created_template_ids' => $ids,
            'guidance' => empty($ids)
                ? 'Dry-run only: no rollback needed because no templates were created.'
                : 'Rollback by deleting these component_definition_subcomponent_templates rows before dependent expected-subcomponent states are created.',
            'artisan_tinker_example' => empty($ids)
                ? null
                : "DB::table('component_definition_subcomponent_templates')->whereIn('id', [".implode(', ', $ids)."])->delete();",
        ];
    }

    private function pairKey(int $parentDefinitionId, int $childDefinitionId): string
    {
        return $parentDefinitionId.':'.$childDefinitionId;
    }
}
