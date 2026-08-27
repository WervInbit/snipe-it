<?php

namespace Tests\Unit\Support;

use App\Helpers\Helper;
use App\Support\SameOriginRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SameOriginRedirectTest extends TestCase
{
    public function test_relative_and_exact_same_origin_urls_are_accepted(): void
    {
        $relative = '/components/tray?filter=mine#pending';
        $absolute = url('/components/tray?filter=mine#pending');

        $this->assertSame($relative, SameOriginRedirect::sanitize($relative));
        $this->assertSame($absolute, SameOriginRedirect::sanitize($absolute));
        $this->assertSame(url('/'), SameOriginRedirect::sanitize(url('/')));
    }

    public function test_external_or_ambiguous_urls_are_rejected(): void
    {
        $origin = parse_url(url('/'));
        $scheme = $origin['scheme'];
        $host = $origin['host'];
        $differentScheme = $scheme === 'https' ? 'http' : 'https';

        foreach ([
            'https://example.invalid/steal',
            '//example.invalid/steal',
            $scheme.'://'.$host.'.example.invalid/steal',
            $scheme.'://'.$host.'@example.invalid/steal',
            $differentScheme.'://'.$host.'/wrong-scheme',
            '/\\example.invalid/steal',
            '/%2f%2fexample.invalid/steal',
            '/%252f%252fexample.invalid/steal',
            '/%5cexample.invalid/steal',
            '/safe/../admin',
            "/safe\r\nLocation: https://example.invalid",
            '/safe%0d%0aLocation:%20https://example.invalid',
            'components/tray',
        ] as $candidate) {
            $this->assertNull(SameOriginRedirect::sanitize($candidate), $candidate);
        }
    }

    public function test_non_default_or_mismatched_ports_are_rejected(): void
    {
        $origin = parse_url(url('/'));
        $scheme = $origin['scheme'];
        $host = $origin['host'];
        $currentPort = $origin['port'] ?? ($scheme === 'https' ? 443 : 80);
        $wrongPort = $currentPort === 65534 ? 65533 : 65534;

        $this->assertNull(
            SameOriginRedirect::sanitize($scheme.'://'.$host.':'.$wrongPort.'/components/tray')
        );
    }

    public function test_shared_form_back_redirect_rejects_an_external_session_url(): void
    {
        Session::put('back_url', 'https://example.invalid/steal');
        $request = Request::create('/hardware/1', 'PUT', [
            'redirect_option' => 'back',
        ]);

        $response = Helper::getRedirectOption($request, 1, 'Assets');

        $this->assertSame(route('home'), $response->getTargetUrl());
        $this->assertFalse(Session::has('back_url'));
    }

    public function test_shared_form_back_redirect_keeps_an_exact_same_origin_url(): void
    {
        $safeUrl = route('hardware.index');
        Session::put('back_url', $safeUrl);
        $request = Request::create('/hardware/1', 'PUT', [
            'redirect_option' => 'back',
        ]);

        $response = Helper::getRedirectOption($request, 1, 'Assets');

        $this->assertSame($safeUrl, $response->getTargetUrl());
    }
}
