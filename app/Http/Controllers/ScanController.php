<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentInstance;
use App\Support\SameOriginRedirect;
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

    public function lookup(Request $request): RedirectResponse
    {
        Gate::authorize('scanning');

        $code = trim((string) $request->query('code', ''));
        if ($code === '' || Str::length($code) > 255) {
            return redirect()
                ->route('scan')
                ->with('error', trans('general.scan_manual_required'));
        }

        $request->query->set('mode', 'manual');

        return $this->resolve($request, $code);
    }

    public function resolve(Request $request, string $code): RedirectResponse
    {
        Gate::authorize('scanning');
        $code = trim($code);

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
            $returnTo = SameOriginRedirect::sanitize($request->query('return_to'));

            if (!$asset) {
                $fallback = $returnTo ? redirect()->to($returnTo) : redirect()->route('scan');

                return $fallback->with('error', __('The scanned asset label could not be matched to an asset.'));
            }

            if ($returnTo) {
                return redirect()->to($this->appendQuery($returnTo, [
                    'destination_asset_id' => $asset->id,
                ]));
            }

            return redirect()->route('hardware.show', $asset);
        }

        $component = ComponentInstance::query()
            ->where('component_tag', $code)
            ->first();

        if ($component) {
            return redirect()->route('components.show', $component);
        }

        if ($request->query('mode') === 'manual') {
            $assetTagMatch = Company::scopeCompanyables(
                Asset::query()->where('asset_tag', $code)
            )->first();

            if ($assetTagMatch) {
                return redirect()->route('findbytag/hardware', ['any' => $code]);
            }

            $serialMatches = Company::scopeCompanyables(
                Asset::query()->where('serial', $code)
            )->limit(2)->get();

            if ($serialMatches->count() === 1) {
                $asset = $serialMatches->first();
                $this->authorize('view', $asset);

                return redirect()->route('hardware.show', $asset);
            }

            return redirect()
                ->route('hardware.index')
                ->with('search', $code)
                ->with('warning', trans('admin/hardware/message.does_not_exist_var', [
                    'asset_tag' => $code,
                ]));
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
        $fragment = isset($parsed['fragment']) && $parsed['fragment'] !== ''
            ? '#' . $parsed['fragment']
            : '';

        return ($queryString !== '' ? $path . '?' . $queryString : $path) . $fragment;
    }
}
