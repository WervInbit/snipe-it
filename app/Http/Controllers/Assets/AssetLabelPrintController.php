<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\QrLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;

class AssetLabelPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Asset $asset, QrLabelService $labels): JsonResponse
    {
        $this->authorize('view', $asset);

        $templates = config('qr_templates.templates');
        $queues = array_values(array_filter(config('qr_templates.queues') ?? []));

        $validated = $request->validate([
            'template' => ['required', 'string', Rule::in(array_keys($templates))],
            'queue' => [
                'nullable',
                'string',
                'max:100',
                $queues ? Rule::in($queues) : 'regex:/^[A-Za-z0-9._:-]+$/',
            ],
        ]);

        // Default to configured queue when the client sends no queue or an empty string.
        $queue = $validated['queue'] ?: config('qr_templates.print_queue') ?: Arr::first($queues);

        if (! $queue) {
            return response()->json([
                'message' => 'Label printer queue is not configured. Set LABEL_PRINTER_QUEUE.',
            ], 422);
        }

        $template = $validated['template'];
        $pdf = $labels->pdfBinary($asset, $template);

        $tmp = tempnam(sys_get_temp_dir(), 'qr-label-');
        $pdfPath = $tmp.'.pdf';
        @rename($tmp, $pdfPath);
        file_put_contents($pdfPath, $pdf);

        $command = config('qr_templates.print_command', 'lp');
        $process = new Process([$command, '-d', $queue, $pdfPath]);
        $process->setTimeout(15);
        $process->run();
        @unlink($pdfPath);

        if (! $process->isSuccessful()) {
            Log::warning('QR label print failed', [
                'asset_id' => $asset->id,
                'queue' => $queue,
                'template' => $template,
                'user_id' => auth()->id(),
                'output' => trim($process->getOutput()),
                'error_output' => trim($process->getErrorOutput()),
            ]);

            return response()->json([
                'message' => 'Printing failed: '.trim($process->getErrorOutput() ?: $process->getOutput()),
            ], 500);
        }

        $jobId = $this->parseJobId($process->getOutput());

        Log::info('QR label sent to printer', [
            'asset_id' => $asset->id,
            'queue' => $queue,
            'template' => $template,
            'user_id' => auth()->id(),
            'job_id' => $jobId,
        ]);

        return response()->json([
            'status' => 'ok',
            'queue' => $queue,
            'job_id' => $jobId,
            'message' => $jobId
                ? "Sent to printer ({$queue}), job {$jobId}."
                : "Sent to printer ({$queue}).",
        ]);
    }

    protected function parseJobId(?string $output): ?string
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
