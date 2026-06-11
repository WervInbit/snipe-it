<?php

namespace App\Http\Controllers\Components;

use App\Http\Controllers\Controller;
use App\Models\ComponentInstance;
use App\Services\QrLabelPrintService;
use App\Services\QrLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComponentLabelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function download(Request $request, ComponentInstance $component_id, QrLabelService $labels, QrLabelPrintService $printer): Response
    {
        $this->authorize('view', $component_id);

        $validated = $request->validate([
            'template' => ['nullable', 'string', Rule::in(array_keys($printer->templates()))],
        ]);

        $template = $validated['template'] ?? null;
        $stem = Str::slug($component_id->component_tag ?: $component_id->display_name ?: (string) $component_id->id);

        return response($labels->pngBinaryFor($component_id, $template), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-label-'.$stem.'.png"',
        ]);
    }

    public function store(Request $request, ComponentInstance $component_id, QrLabelService $labels, QrLabelPrintService $printer): JsonResponse
    {
        $this->authorize('view', $component_id);

        $templates = $printer->templates();
        $queues = $printer->queues();

        $validated = $request->validate([
            'template' => ['required', 'string', Rule::in(array_keys($templates))],
            'queue' => [
                'nullable',
                'string',
                'max:100',
                $queues ? Rule::in($queues) : 'regex:/^[A-Za-z0-9._:-]+$/',
            ],
        ]);

        $queue = $printer->resolveQueue($validated['queue'] ?? null);

        if (! $queue) {
            return response()->json([
                'message' => 'Label printer queue is not configured. Set LABEL_PRINTER_QUEUE.',
            ], 422);
        }

        $template = $validated['template'];
        $result = $printer->printPdf($labels->pdfBinaryFor($component_id, $template), $queue);

        if (! $result['successful']) {
            Log::warning('Component QR label print failed', [
                'component_instance_id' => $component_id->id,
                'component_tag' => $component_id->component_tag,
                'queue' => $queue,
                'template' => $template,
                'user_id' => auth()->id(),
                'output' => $result['output'],
                'error_output' => $result['error_output'],
            ]);

            return response()->json([
                'message' => 'Printing failed: '.($result['error_output'] ?: $result['output']),
            ], 500);
        }

        $jobId = $result['job_id'];

        Log::info('Component QR label sent to printer', [
            'component_instance_id' => $component_id->id,
            'component_tag' => $component_id->component_tag,
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
}
