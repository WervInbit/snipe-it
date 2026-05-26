<?php

namespace App\Console\Commands;

use App\Services\Components\ComponentHierarchyConversionPreviewService;
use Illuminate\Console\Command;

class PreviewComponentHierarchyConversion extends Command
{
    protected $signature = 'component-hierarchy:preview-conversion
        {--json : Emit the full read-only report as JSON}
        {--include-inactive : Include inactive component definitions}
        {--limit=25 : Maximum suggested templates to display in table output}';

    protected $description = 'Preview component-definition hierarchy conversion candidates without writing data.';

    public function handle(ComponentHierarchyConversionPreviewService $previewService): int
    {
        $report = $previewService->buildReport([
            'include_inactive' => (bool) $this->option('include-inactive'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Component hierarchy conversion preview');
        $this->line('Read-only: no database writes are performed.');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            collect($report['summary'])
                ->map(fn ($value, string $key) => [$key, is_bool($value) ? ($value ? 'yes' : 'no') : $value])
                ->values()
                ->all()
        );

        $this->newLine();
        $this->info('Detection rules');
        foreach ($report['detection_rules'] as $rule => $description) {
            $this->line("- {$rule}: {$description}");
        }

        $this->newLine();
        $this->info('Suggested expected subcomponent templates');
        $suggestions = collect($report['suggested_templates'])
            ->take(max(1, (int) $this->option('limit')));

        if ($suggestions->isEmpty()) {
            $this->line('No suggested templates found with the current detection rules.');
        } else {
            $this->table(
                ['Parent', 'Child', 'Expected Name', 'Qty', 'Models', 'Confidence', 'Overlap Warnings'],
                $suggestions->map(fn (array $suggestion) => [
                    $suggestion['parent_name'],
                    $suggestion['child_name'],
                    $suggestion['suggested_expected_name'],
                    $suggestion['suggested_expected_qty'],
                    $suggestion['model_number_count'],
                    $suggestion['confidence'],
                    count($suggestion['overlap_warnings']),
                ])->all()
            );
        }

        if (!empty($report['existing_overlap_warnings'])) {
            $this->newLine();
            $this->warn('Existing expected-subcomponent overlap warnings');
            $this->table(
                ['Template ID', 'Attribute', 'Parent Value', 'Child Value'],
                collect($report['existing_overlap_warnings'])->map(fn (array $warning) => [
                    $warning['template_id'],
                    $warning['attribute_label'],
                    $warning['parent_value'],
                    $warning['child_value'],
                ])->all()
            );
        }

        $this->newLine();
        $this->line('Use --json for the full candidate and model-number evidence.');

        return self::SUCCESS;
    }
}
