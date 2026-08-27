<?php

namespace App\Console\Commands;

use App\Services\Components\ComponentIntegrityAuditService;
use Illuminate\Console\Command;

class AuditComponentIntegrity extends Command
{
    protected $signature = 'components:audit-integrity
        {--json : Emit the complete read-only report as JSON}';

    protected $description = 'Audit component hierarchy and expected-state integrity without writing data.';

    public function handle(ComponentIntegrityAuditService $auditService): int
    {
        $report = $auditService->audit();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Component integrity audit');
        $this->line('Read-only: this command never repairs or writes component data.');
        $this->newLine();
        $this->table(
            ['Finding', 'Count'],
            collect($report['summary'])
                ->map(fn (int $count, string $finding): array => [$finding, $count])
                ->values()
                ->all()
        );

        $this->renderOrphanedChildren($report['findings']['orphaned_children']);
        $this->renderPlacementMismatches($report['findings']['placement_mismatches']);
        $this->renderAttachedOrphans($report['findings']['attached_children_without_live_parent']);
        $this->renderExpectedStateFindings($report['findings']['impossible_expected_states']);
        $this->renderSchemaErrors($report['findings']['schema_errors']);

        $this->newLine();
        if ($report['ok']) {
            $this->info('PASS: no component integrity findings detected.');

            return self::SUCCESS;
        }

        $this->error('FAIL: component integrity findings require review before migration or release.');

        return self::FAILURE;
    }

    private function renderOrphanedChildren(array $findings): void
    {
        if ($findings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Live children with a missing or soft-deleted parent');
        $this->table(
            ['Child ID', 'Parent ID', 'Parent State', 'Lifecycle', 'Status'],
            collect($findings)->map(fn (array $finding): array => [
                $finding['child_component_id'],
                $finding['parent_component_id'],
                $finding['parent_state'],
                $finding['child_lifecycle_status'],
                $finding['child_status'],
            ])->all()
        );
    }

    private function renderPlacementMismatches(array $findings): void
    {
        if ($findings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Parent/child asset or root placement mismatches');
        $this->table(
            ['Child ID', 'Parent ID', 'Fields', 'Child Asset', 'Parent Asset', 'Child Root', 'Parent Root'],
            collect($findings)->map(fn (array $finding): array => [
                $finding['child_component_id'],
                $finding['parent_component_id'],
                implode(', ', $finding['mismatch_fields']),
                $finding['child_current_asset_id'] ?? 'null',
                $finding['parent_current_asset_id'] ?? 'null',
                $finding['child_root_asset_id'] ?? 'null',
                $finding['parent_root_asset_id'] ?? 'null',
            ])->all()
        );
    }

    private function renderAttachedOrphans(array $findings): void
    {
        if ($findings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Attached children without a live parent');
        $this->line('Child component IDs: '.implode(', ', array_column($findings, 'child_component_id')));
    }

    private function renderExpectedStateFindings(array $findings): void
    {
        if ($findings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Impossible expected-subcomponent counters');
        $this->table(
            ['State ID', 'Parent Component ID', 'Template ID', 'Materialized', 'Removed', 'Expected', 'Excess'],
            collect($findings)->map(fn (array $finding): array => [
                $finding['expected_state_id'],
                $finding['parent_component_id'],
                $finding['template_id'],
                $finding['materialized_qty'],
                $finding['removed_qty'],
                $finding['expected_qty'] ?? 'missing',
                $finding['excess_qty'] ?? 'n/a',
            ])->all()
        );
    }

    private function renderSchemaErrors(array $findings): void
    {
        if ($findings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Required audit schema is unavailable');
        $this->table(
            ['Table', 'Reason', 'Missing Columns'],
            collect($findings)->map(fn (array $finding): array => [
                $finding['table'],
                $finding['reason'],
                implode(', ', $finding['missing_columns']),
            ])->all()
        );
    }
}
