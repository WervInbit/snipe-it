<?php

namespace App\Http\Controllers;

use App\Models\Actionlog;
use App\Services\SafeRasterImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActionlogController extends Controller
{
    public function displaySig(string $filename, SafeRasterImageService $images): RedirectResponse | Response
    {
        if ($filename !== basename($filename)) {
            abort(404);
        }

        $log = Actionlog::query()
            ->where('accept_signature', $filename)
            ->first();
        if (! $log) {
            abort(404);
        }
        $item = $log->item;

        if (! $item) {
            abort(404);
        }

        $this->authorize('view', $item);

        $path = 'private_uploads/signatures/'.$filename;
        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($path)) {
            return redirect()->back()->with('error', trans('general.file_does_not_exist'));
        }

        try {
            $prepared = $images->prepareContents($disk->get($path), 'signature');
        } catch (\Throwable) {
            abort(404);
        }

        return response($prepared['contents'], 200, [
            'Content-Type' => $prepared['mime'],
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function getStoredEula(string $filename): RedirectResponse | StreamedResponse
    {
        if ($filename !== basename($filename)) {
            abort(404);
        }

        $log = Actionlog::query()
            ->where('action_type', 'accepted')
            ->where('filename', $filename)
            ->first();

        if (! $log || ! $log->item) {
            abort(404);
        }

        $this->authorize('view', $log->item);

        $path = 'private_uploads/eula-pdfs/'.$filename;
        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($path)) {
            return redirect()->back()->with('error', trans('general.file_does_not_exist'));
        }

        return $disk->download($path, $filename, [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
