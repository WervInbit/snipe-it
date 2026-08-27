<?php

namespace Tests\Unit\Rules;

use App\Rules\ExternalUrl;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExternalUrlTest extends TestCase
{
    public static function rejectedUrlProvider(): array
    {
        return [
            'ftp scheme' => ['ftp://example.com/'],
            'irc scheme' => ['irc://example.com/'],
            'javascript scheme' => ['javascript:alert(1)'],
            'file scheme' => ['file:///etc/passwd'],
            'credentials in URL' => ['https://user:secret@93.184.216.34/hook'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'IPv4 loopback' => ['http://127.0.0.1/'],
            'IPv6 loopback' => ['http://[::1]/'],
            'localhost' => ['http://localhost/'],
            'RFC1918 10' => ['http://10.0.0.1/'],
            'RFC1918 172' => ['http://172.16.5.5/'],
            'RFC1918 192' => ['http://192.168.1.1/'],
            'IPv4 link local' => ['http://169.254.1.1/'],
            'IPv6 link local' => ['http://[fe80::1]/'],
            'IPv4 unspecified' => ['http://0.0.0.0/'],
            'IPv6 unique local' => ['http://[fd12:3456::1]/'],
            'IPv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'IPv4-mapped private address' => ['http://[::ffff:10.0.0.1]/'],
            'missing scheme' => ['example.com'],
            'missing host' => ['http:///'],
        ];
    }

    #[DataProvider('rejectedUrlProvider')]
    public function testItRejectsNonPublicOrMalformedUrls(string $url): void
    {
        $this->assertFalse($this->passes($url), 'Expected URL to be rejected: '.$url);
    }

    public function testItAcceptsPublicIpLiterals(): void
    {
        $this->assertTrue($this->passes('https://93.184.216.34/webhook'));
        $this->assertTrue($this->passes('http://[2606:2800:220:1:248:1893:25c8:1946]/'));
    }

    public function testInternalTargetEscapeHatchIsExplicitAndSchemeRestricted(): void
    {
        config(['app.webhook_allow_internal_targets' => true]);

        $this->assertTrue($this->passes('http://127.0.0.1/hook'));
        $this->assertTrue($this->passes('https://10.0.0.1/hook'));
        $this->assertFalse($this->passes('ftp://10.0.0.1/hook'));
        $this->assertFalse($this->passes('https://user:secret@10.0.0.1/hook'));
    }

    public function testRejectedMessageResolvesTheAttributePlaceholder(): void
    {
        $validator = Validator::make(
            ['webhook_endpoint' => 'http://127.0.0.1/internal'],
            ['webhook_endpoint' => [new ExternalUrl]],
        );

        $this->assertTrue($validator->fails());
        $message = $validator->errors()->first('webhook_endpoint');
        $this->assertSame(
            trans('validation.external_url', ['attribute' => 'webhook endpoint']),
            $message
        );
        $this->assertStringNotContainsString(':attribute', $message);
    }

    private function passes(string $url): bool
    {
        return Validator::make(
            ['url' => $url],
            ['url' => [new ExternalUrl]],
        )->passes();
    }
}
