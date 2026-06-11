<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;

class QrLabelPrintService
{
    /**
     * @return array<int, string>
     */
    public function queues(): array
    {
        return array_values(array_filter(config('qr_templates.queues') ?? []));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templates(): array
    {
        return config('qr_templates.templates') ?? [];
    }

    public function resolveQueue(?string $queue): ?string
    {
        $queue = trim((string) $queue);

        return $queue !== ''
            ? $queue
            : (config('qr_templates.print_queue') ?: Arr::first($this->queues()));
    }

    /**
     * @return array{successful: bool, output: string, error_output: string, job_id: string|null}
     */
    public function printPdf(string $pdf, string $queue): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qr-label-');
        $pdfPath = $tmp.'.pdf';
        @rename($tmp, $pdfPath);
        file_put_contents($pdfPath, $pdf);

        $command = config('qr_templates.print_command', 'lp');
        $options = config('qr_templates.print_options', []);
        $process = new Process(array_values(array_filter([
            $command,
            '-d',
            $queue,
            ...$options,
            $pdfPath,
        ])));
        $process->setTimeout(15);

        $cupsServer = env('CUPS_SERVER');
        if ($cupsServer) {
            $process->setEnv(['CUPS_SERVER' => $cupsServer]);
        }

        $process->run();
        @unlink($pdfPath);

        return [
            'successful' => $process->isSuccessful(),
            'output' => trim($process->getOutput()),
            'error_output' => trim($process->getErrorOutput()),
            'job_id' => $this->parseJobId($process->getOutput()),
        ];
    }

    public function parseJobId(?string $output): ?string
    {
        if (! $output) {
            return null;
        }

        if (preg_match('/request id is ([^\\s]+)/i', $output, $match)) {
            return $match[1];
        }

        return trim($output) ?: null;
    }
}
