<?php

namespace App\Services\Components;

use App\Models\ComponentInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComponentIntegrityAuditService
{
    /**
     * Build a deterministic, read-only component hierarchy integrity report.
     */
    public function audit(): array
    {
        $schemaErrors = $this->schemaErrors();
        if ($schemaErrors !== []) {
            return $this->report([], [], [], [], $schemaErrors);
        }

        $orphanedChildren = $this->orphanedChildren();
        $placementMismatches = $this->placementMismatches();
        $attachedChildrenWithoutLiveParent = array_values(array_filter(
            $orphanedChildren,
            fn (array $finding): bool => $finding['child_lifecycle_status'] === ComponentInstance::LIFECYCLE_ATTACHED
                || $finding['child_status'] === ComponentInstance::STATUS_INSTALLED
        ));
        $impossibleExpectedStates = $this->impossibleExpectedStates();

        return $this->report(
            $orphanedChildren,
            $placementMismatches,
            $attachedChildrenWithoutLiveParent,
            $impossibleExpectedStates,
            []
        );
    }

    private function orphanedChildren(): array
    {
        return DB::table('component_instances as child')
            ->leftJoin(
                'component_instances as parent',
                'parent.id',
                '=',
                'child.parent_component_instance_id'
            )
            ->whereNull('child.deleted_at')
            ->whereNotNull('child.parent_component_instance_id')
            ->where(function ($query): void {
                $query
                    ->whereNull('parent.id')
                    ->orWhereNotNull('parent.deleted_at');
            })
            ->orderBy('child.id')
            ->get([
                'child.id as child_component_id',
                'child.parent_component_instance_id as parent_component_id',
                'child.status as child_status',
                'child.lifecycle_status as child_lifecycle_status',
                'child.current_asset_id as child_current_asset_id',
                'child.root_asset_id as child_root_asset_id',
                'parent.id as resolved_parent_component_id',
                'parent.deleted_at as parent_deleted_at',
            ])
            ->map(fn ($row): array => [
                'child_component_id' => (int) $row->child_component_id,
                'parent_component_id' => (int) $row->parent_component_id,
                'parent_state' => $row->resolved_parent_component_id === null
                    ? 'missing'
                    : 'soft_deleted',
                'parent_deleted_at' => $row->parent_deleted_at,
                'child_status' => $row->child_status,
                'child_lifecycle_status' => $row->child_lifecycle_status,
                'child_current_asset_id' => $this->nullableInt($row->child_current_asset_id),
                'child_root_asset_id' => $this->nullableInt($row->child_root_asset_id),
            ])
            ->all();
    }

    private function placementMismatches(): array
    {
        return DB::table('component_instances as child')
            ->join(
                'component_instances as parent',
                'parent.id',
                '=',
                'child.parent_component_instance_id'
            )
            ->whereNull('child.deleted_at')
            ->whereNull('parent.deleted_at')
            ->where(function ($query): void {
                $query
                    ->whereRaw(
                        'COALESCE(child.current_asset_id, 0) <> COALESCE(parent.current_asset_id, 0)'
                    )
                    ->orWhereRaw(
                        'COALESCE(child.root_asset_id, 0) <> COALESCE(parent.root_asset_id, 0)'
                    );
            })
            ->orderBy('child.id')
            ->get([
                'child.id as child_component_id',
                'parent.id as parent_component_id',
                'child.current_asset_id as child_current_asset_id',
                'parent.current_asset_id as parent_current_asset_id',
                'child.root_asset_id as child_root_asset_id',
                'parent.root_asset_id as parent_root_asset_id',
            ])
            ->map(function ($row): ?array {
                $childCurrentAssetId = $this->nullableInt($row->child_current_asset_id);
                $parentCurrentAssetId = $this->nullableInt($row->parent_current_asset_id);
                $childRootAssetId = $this->nullableInt($row->child_root_asset_id);
                $parentRootAssetId = $this->nullableInt($row->parent_root_asset_id);
                $mismatchFields = [];

                if ($childCurrentAssetId !== $parentCurrentAssetId) {
                    $mismatchFields[] = 'current_asset_id';
                }

                if ($childRootAssetId !== $parentRootAssetId) {
                    $mismatchFields[] = 'root_asset_id';
                }

                if ($mismatchFields === []) {
                    return null;
                }

                return [
                    'child_component_id' => (int) $row->child_component_id,
                    'parent_component_id' => (int) $row->parent_component_id,
                    'mismatch_fields' => $mismatchFields,
                    'child_current_asset_id' => $childCurrentAssetId,
                    'parent_current_asset_id' => $parentCurrentAssetId,
                    'child_root_asset_id' => $childRootAssetId,
                    'parent_root_asset_id' => $parentRootAssetId,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function impossibleExpectedStates(): array
    {
        return DB::table('component_expected_subcomponent_states as state')
            ->leftJoin(
                'component_definition_subcomponent_templates as template',
                'template.id',
                '=',
                'state.component_definition_subcomponent_template_id'
            )
            ->where(function ($query): void {
                $query
                    ->whereNull('template.id')
                    ->orWhereRaw(
                        '(COALESCE(state.materialized_qty, 0) + COALESCE(state.removed_qty, 0)) > template.expected_qty'
                    );
            })
            ->orderBy('state.id')
            ->get([
                'state.id as expected_state_id',
                'state.component_instance_id as parent_component_id',
                'state.component_definition_subcomponent_template_id as template_id',
                'state.materialized_qty',
                'state.removed_qty',
                'template.expected_qty',
            ])
            ->map(function ($row): ?array {
                $materializedQty = (int) $row->materialized_qty;
                $removedQty = (int) $row->removed_qty;
                $accountedQty = $materializedQty + $removedQty;
                $expectedQty = $row->expected_qty === null ? null : (int) $row->expected_qty;

                if ($expectedQty !== null && $accountedQty <= $expectedQty) {
                    return null;
                }

                return [
                    'expected_state_id' => (int) $row->expected_state_id,
                    'parent_component_id' => (int) $row->parent_component_id,
                    'template_id' => (int) $row->template_id,
                    'materialized_qty' => $materializedQty,
                    'removed_qty' => $removedQty,
                    'accounted_qty' => $accountedQty,
                    'expected_qty' => $expectedQty,
                    'excess_qty' => $expectedQty === null ? null : $accountedQty - $expectedQty,
                    'reason' => $expectedQty === null
                        ? 'missing_expected_template'
                        : 'accounted_quantity_exceeds_expected_quantity',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function report(
        array $orphanedChildren,
        array $placementMismatches,
        array $attachedChildrenWithoutLiveParent,
        array $impossibleExpectedStates,
        array $schemaErrors
    ): array {
        $summary = [
            'live_children_with_missing_or_deleted_parent' => count($orphanedChildren),
            'parent_child_asset_or_root_mismatches' => count($placementMismatches),
            'attached_children_without_live_parent' => count($attachedChildrenWithoutLiveParent),
            'impossible_expected_state_counters' => count($impossibleExpectedStates),
            'schema_errors' => count($schemaErrors),
        ];
        $summary['total_finding_rows'] = array_sum($summary);

        return [
            'read_only' => true,
            'ok' => $summary['total_finding_rows'] === 0,
            'summary' => $summary,
            'findings' => [
                'orphaned_children' => $orphanedChildren,
                'placement_mismatches' => $placementMismatches,
                'attached_children_without_live_parent' => $attachedChildrenWithoutLiveParent,
                'impossible_expected_states' => $impossibleExpectedStates,
                'schema_errors' => $schemaErrors,
            ],
        ];
    }

    private function schemaErrors(): array
    {
        $requiredColumns = [
            'component_instances' => [
                'id',
                'parent_component_instance_id',
                'current_asset_id',
                'root_asset_id',
                'status',
                'lifecycle_status',
                'deleted_at',
            ],
            'component_expected_subcomponent_states' => [
                'id',
                'component_instance_id',
                'component_definition_subcomponent_template_id',
                'materialized_qty',
                'removed_qty',
            ],
            'component_definition_subcomponent_templates' => [
                'id',
                'expected_qty',
            ],
        ];
        $errors = [];

        foreach ($requiredColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $errors[] = [
                    'table' => $table,
                    'missing_columns' => $columns,
                    'reason' => 'missing_table',
                ];
                continue;
            }

            $missingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => !Schema::hasColumn($table, $column)
            ));

            if ($missingColumns !== []) {
                $errors[] = [
                    'table' => $table,
                    'missing_columns' => $missingColumns,
                    'reason' => 'missing_columns',
                ];
            }
        }

        return $errors;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
