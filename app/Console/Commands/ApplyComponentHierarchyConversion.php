<?php

namespace App\Console\Commands;

use App\Services\Components\ComponentHierarchyConversionApplyService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ApplyComponentHierarchyConversion extends Command
{
    protected $signature = 'component-hierarchy:apply-conversion
        {--pair=* : Selected parent_id:child_id definition pair to convert; repeat for multiple pairs}
        {--apply : Persist selected templates. Omit for dry-run output}
        {--json : Emit the full conversion plan/result as JSON}
        {--include-inactive : Include inactive definitions when resolving preview suggestions}';

    protected $description = 'Dry-run or apply selected component hierarchy conversion suggestions.';

    public function handle(ComponentHierarchyConversionApplyService $conversionService): int
    {
        try {
            $result = $this->option('apply')
                ? $conversionService->apply((array) $this->option('pair'), [
                    'include_inactive' => (bool) $this->option('include-inactive'),
                ])
                : $conversionService->dryRun((array) $this->option('pair'), [
                    'include_inactive' => (bool) $this->option('include-inactive'),
                ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCodeForResult($result);
        }

        $this->info($result['summary']['dry_run']
            ? 'Component hierarchy conversion dry-run'
            : 'Component hierarchy conversion apply result');

        $this->line($result['summary']['dry_run']
            ? 'Dry-run only: no database writes were performed.'
            : 'Selected conversion templates were written.');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            collect($result['summary'])
                ->map(fn ($value, string $key) => [$key, is_bool($value) ? ($value ? 'yes' : 'no') : $value])
                ->values()
                ->all()
        );

        if (!empty($result['templates_to_create'])) {
            $this->newLine();
            $this->info($result['summary']['dry_run'] ? 'Templates that would be created' : 'Selected templates');
            $this->table(
                ['Pair', 'Parent', 'Child', 'Expected Name', 'Qty', 'Models', 'Overlap Warnings'],
                collect($result['templates_to_create'])->map(fn (array $template) => [
                    $template['parent_definition_id'].':'.$template['child_definition_id'],
                    $template['parent_name'],
                    $template['child_name'],
                    $template['suggested_expected_name'],
                    $template['suggested_expected_qty'],
                    $template['model_number_count'],
                    count($template['overlap_warnings']),
                ])->all()
            );
        }

        if (!empty($result['created_templates'])) {
            $this->newLine();
            $this->info('Created templates');
            $this->table(
                ['Template ID', 'Parent', 'Child', 'Expected Name', 'Qty', 'Sort'],
                collect($result['created_templates'])->map(fn (array $template) => [
                    $template['template_id'],
                    $template['parent_name'],
                    $template['child_name'],
                    $template['expected_name'],
                    $template['expected_qty'],
                    $template['sort_order'],
                ])->all()
            );
        }

        if (!empty($result['unavailable_pairs'])) {
            $this->newLine();
            $this->warn('Unavailable selected pairs');
            $this->table(
                ['Pair', 'Reason'],
                collect($result['unavailable_pairs'])->map(fn (array $pair) => [
                    $pair['pair'],
                    $pair['reason'],
                ])->all()
            );
        }

        $this->newLine();
        $this->line($result['rollback']['guidance']);

        if ($result['rollback']['artisan_tinker_example']) {
            $this->line('Rollback tinker example: '.$result['rollback']['artisan_tinker_example']);
        }

        if ($result['summary']['dry_run']) {
            $this->line('Add --apply only after reviewing the selected pair output.');
        }

        return $this->exitCodeForResult($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function exitCodeForResult(array $result): int
    {
        return $result['summary']['templates_to_create'] > 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
