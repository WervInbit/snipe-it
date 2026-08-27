<?php

namespace Tests\Unit\Security;

use App\Listeners\RejectUnsafeMailAddresses;
use App\Validation\SecureValidator;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Mailables\Address as MailableAddress;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MailAddressSecurityTest extends TestCase
{
    public function test_default_email_rule_rejects_line_break_injection(): void
    {
        $validator = Validator::make([
            'email' => "\"sender\r\nBcc: victim@example.com\"@example.com",
        ], [
            'email' => ['required', 'email'],
        ]);

        $this->assertInstanceOf(SecureValidator::class, $validator);
        $this->assertTrue($validator->fails());
    }

    public function test_patched_framework_layers_reject_line_break_injection_without_application_listener(): void
    {
        $frameworkValidator = new class
        {
            use ValidatesAttributes;
        };

        $this->assertFalse($frameworkValidator->validateEmail(
            'email',
            "\"sender\r\nBcc: victim@example.com\"@example.com",
            []
        ));

        try {
            new MailableAddress("\"sender\r\nBcc: victim@example.com\"@example.com");
            $this->fail('The framework mailable address accepted a line break.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Email addresses may not contain line break characters.',
                $exception->getMessage()
            );
        }

        try {
            (new Message(new Email()))->to($this->forgedUnsafeAddress("\r\n"));
            $this->fail('The framework message builder accepted a forged address with a line break.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Email addresses may not contain line break characters.',
                $exception->getMessage()
            );
        }
    }

    public function test_outbound_mail_listener_rejects_every_address_path_and_line_break_style(): void
    {
        foreach (["\r", "\n", "\r\n"] as $lineBreak) {
            foreach (['from', 'sender', 'returnPath', 'to', 'cc', 'bcc', 'replyTo'] as $addressPath) {
                $email = new Email();
                $email->{$addressPath}($this->forgedUnsafeAddress($lineBreak));

                try {
                    app(RejectUnsafeMailAddresses::class)->handle(new MessageSending($email));
                    $this->fail("Unsafe {$addressPath} address using a line break was accepted.");
                } catch (InvalidArgumentException $exception) {
                    $this->assertSame(
                        'Email addresses may not contain line break characters.',
                        $exception->getMessage()
                    );
                }
            }
        }
    }

    public function test_outbound_mail_listener_accepts_normal_addresses(): void
    {
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->cc('audit@example.com');

        app(RejectUnsafeMailAddresses::class)->handle(new MessageSending($email));

        $this->assertSame('recipient@example.com', $email->getTo()[0]->getAddress());
    }

    public function test_application_mailer_dispatches_the_security_listener_before_transport(): void
    {
        $mailer = app('mailer');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $mailer->setSymfonyTransport(new NullTransport());
        $unsafeAddress = $this->forgedUnsafeAddress("\r\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email addresses may not contain line break characters.');

        $mailer->raw('Security listener integration test.', function (Message $message) use ($unsafeAddress): void {
            $message->from('sender@example.com');
            $message->getSymfonyMessage()->to($unsafeAddress);
        });
    }

    public function test_local_disks_cannot_enable_or_generate_temporary_urls(): void
    {
        foreach (config('filesystems.disks') as $name => $config) {
            if (($config['driver'] ?? null) !== 'local') {
                continue;
            }

            $this->assertFalse((bool) ($config['serve'] ?? false), "Disk {$name} enables local serving.");
            $disk = Storage::disk($name);
            $this->assertFalse($disk->providesTemporaryUrls(), "Disk {$name} provides temporary URLs.");

            try {
                $disk->temporaryUrl('security-probe.txt', now()->addMinute());
                $this->fail("Disk {$name} generated a temporary URL.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('does not support', $exception->getMessage());
            }
        }

        $storageRoutes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => (string) $route->getName())
            ->filter(fn (string $name) => str_starts_with($name, 'storage.'));

        $this->assertSame([], $storageRoutes->values()->all());
    }

    public function test_patched_local_temporary_url_keeps_reserved_delimiters_inside_the_signed_path(): void
    {
        Route::get('/security-storage/{path}', fn () => 'unused')
            ->where('path', '.*')
            ->name('storage.security-test');
        app('router')->getRoutes()->refreshNameLookups();
        app('url')->setRoutes(app('router')->getRoutes());

        $adapter = new LocalFilesystemAdapter(
            Mockery::mock(FilesystemOperator::class),
            Mockery::mock(FlysystemAdapter::class),
            []
        );
        $adapter
            ->diskName('security-test')
            ->shouldServeSignedUrls(true, fn () => app('url'));

        $url = $adapter->temporaryUrl(
            'nested/file.txt?pad=x#fragment',
            now()->addMinute()
        );
        $parts = parse_url($url);

        $this->assertSame(
            '/security-storage/nested/file.txt%3Fpad%3Dx%23fragment',
            $parts['path'] ?? null
        );
        $this->assertStringNotContainsString('pad=x', $parts['query'] ?? '');
        $this->assertStringContainsString('expires=', $parts['query'] ?? '');
        $this->assertStringContainsString('signature=', $parts['query'] ?? '');
    }

    private function forgedUnsafeAddress(string $lineBreak): Address
    {
        $reflection = new ReflectionClass(Address::class);
        $unsafeAddress = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('address')->setValue(
            $unsafeAddress,
            "\"sender{$lineBreak}Bcc: victim@example.com\"@example.com"
        );
        $reflection->getProperty('name')->setValue($unsafeAddress, '');

        return $unsafeAddress;
    }
}
