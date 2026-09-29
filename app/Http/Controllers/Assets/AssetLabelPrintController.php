<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\QrLabelPrintService;
use App\Services\QrLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AssetLabelPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Asset $asset, QrLabelService $labels, QrLabelPrintService $printer): JsonResponse
    {
        $this->authorize('view', $asset);

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
                'message' => trans('general.label_printer_queue_not_configured'),
            ], 422);
        }

        $template = $validated['template'];
        $result = $printer->printPdf($labels->pdfBinaryFor($asset, $template), $queue);

        if (! $result['successful']) {
            Log::warning('QR label print failed', [
                'asset_id' => $asset->id,
                'queue' => $queue,
                'template' => $template,
                'user_id' => auth()->id(),
                'output' => $result['output'],
                'error_output' => $result['error_output'],
            ]);

            return response()->json([
                'message' => trans('general.printing_failed_with_error', [
                    'error' => $result['error_output'] ?: $result['output'],
                ]),
            ], 500);
        }

        $jobId = $result['job_id'];

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
                ? trans('general.sent_to_printer_with_job', ['queue' => $queue, 'job' => $jobId])
                : trans('general.sent_to_printer', ['queue' => $queue]),
        ]);
    }
}
