<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ComponentInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ScanController extends Controller
{
    /**
     * Display the asset scanning page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        Gate::authorize('scanning');
        return view('scan.index');
    }

    public function resolve(Request $request, string $code): RedirectResponse
    {
        Gate::authorize('scanning');

        if (Str::startsWith($code, 'CMP:')) {
            $component = ComponentInstance::query()
                ->where('qr_uid', Str::after($code, 'CMP:'))
                ->first();

            if ($component) {
                return redirect()->route('components.show', $component);
            }

            return redirect()
                ->route('scan')
                ->with('error', __('The scanned component label could not be matched to a tracked component.'));
        }

        if ($request->query('mode') === 'asset_destination') {
            $asset = Asset::query()->where('asset_tag', '=', $code)->first();
            $returnTo = trim((string) $request->query('return_to', ''));

            if (!$asset) {
                $fallback = $returnTo !== '' ? redirect()->to($returnTo) : redirect()->route('scan');

                return $fallback->with('error', __('The scanned asset label could not be matched to an asset.'));
            }

            if ($returnTo !== '') {
                return redirect()->to($this->appendQuery($returnTo, [
                    'destination_asset_id' => $asset->id,
                ]));
            }

            return redirect()->route('hardware.show', $asset);
        }

        return redirect()->route('findbytag/hardware', ['any' => $code]);
    }

    private function appendQuery(string $url, array $parameters): string
    {
        $parsed = parse_url($url);
        $query = [];

        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        foreach ($parameters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query[$key] = $value;
        }

        $prefix = '';
        if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
            $prefix = $parsed['scheme'] . '://' . $parsed['host'];

            if (!empty($parsed['port'])) {
                $prefix .= ':' . $parsed['port'];
            }
        }

        $path = $prefix . ($parsed['path'] ?? '');
        $queryString = http_build_query($query);

        return $queryString !== '' ? $path . '?' . $queryString : $path;
    }

}
