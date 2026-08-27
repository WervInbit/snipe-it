<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductionComposerPatchConfigurationTest extends TestCase
{
    private const PATCHES = [
        'patches/laravel-framework-crlf-email-backport.patch' => [
            'description' => 'Backport Laravel f336ba7 CRLF email validation to Laravel 11',
            'sha256' => '7e27478c12b2a711282fa0b1d87395a8550bab42d95e4b8c78ac6e596a6bc467',
        ],
        'patches/laravel-framework-local-temporary-url-backport.patch' => [
            'description' => 'Backport Laravel 12df688 local temporary URL path encoding to Laravel 11',
            'sha256' => 'e437bce6327481c0edd7627ad4240f3ae92e1d041aae8b6e6102cae04c69e682',
        ],
    ];

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__.'/../..');
    }

    public function test_laravel_security_backport_is_pinned_in_composer_and_patch_lock(): void
    {
        $composer = $this->readJson('composer.json');
        $patchLock = $this->readJson('patches.lock.json');

        $this->assertSame('~2.0', $composer['require']['cweagans/composer-patches']);
        $this->assertTrue($composer['config']['allow-plugins']['cweagans/composer-patches']);

        $configuredPatches = $this->indexPatches($composer['extra']['patches']['laravel/framework']);
        $lockedPatches = $this->indexPatches($patchLock['patches']['laravel/framework']);

        $this->assertEqualsCanonicalizing(array_keys(self::PATCHES), array_keys($configuredPatches));
        $this->assertEqualsCanonicalizing(array_keys(self::PATCHES), array_keys($lockedPatches));

        foreach (self::PATCHES as $path => $expected) {
            $configuredPatch = $configuredPatches[$path];
            $lockedPatch = $lockedPatches[$path];

            $this->assertSame($expected['description'], $configuredPatch['description']);
            $this->assertSame($expected['sha256'], $configuredPatch['sha256']);
            $this->assertSame(1, $configuredPatch['depth']);

            $this->assertSame('laravel/framework', $lockedPatch['package']);
            $this->assertSame($expected['description'], $lockedPatch['description']);
            $this->assertSame($expected['sha256'], $lockedPatch['sha256']);
            $this->assertSame(1, $lockedPatch['depth']);
        }
    }

    public function test_pinned_patch_checksum_and_framework_surface_are_exact(): void
    {
        foreach (self::PATCHES as $path => $expected) {
            $this->assertSame($expected['sha256'], hash_file('sha256', $this->basePath.'/'.$path));
        }

        $mailPatch = file_get_contents(
            $this->basePath.'/patches/laravel-framework-crlf-email-backport.patch',
        );
        $this->assertStringContainsString('src/Illuminate/Mail/Mailables/Address.php', $mailPatch);
        $this->assertStringContainsString('src/Illuminate/Mail/Message.php', $mailPatch);
        $this->assertStringContainsString('src/Illuminate/Validation/Concerns/ValidatesAttributes.php', $mailPatch);
        $this->assertStringContainsString('ensureAddressIsSafe', $mailPatch);
        $this->assertStringContainsString("preg_match('/[\\r\\n]/'", $mailPatch);

        $temporaryUrlPatch = file_get_contents(
            $this->basePath.'/patches/laravel-framework-local-temporary-url-backport.patch',
        );
        $this->assertStringContainsString('src/Illuminate/Filesystem/LocalFilesystemAdapter.php', $temporaryUrlPatch);
        $this->assertStringContainsString("strtr(rawurlencode(\$path), ['%2F' => '/'])", $temporaryUrlPatch);
    }

    public function test_production_build_installs_the_locked_patch_before_copying_application_source(): void
    {
        $dockerfile = file_get_contents($this->basePath.'/docker/production/Dockerfile');
        $applicationBuild = $this->stage($dockerfile, 'application-build', 'app');
        $runtime = $this->stage($dockerfile, 'app', 'web');
        $phpBase = $this->stage($dockerfile, 'php-base', 'application-build');

        $this->assertStringContainsString('apt-get install -y --no-install-recommends git', $applicationBuild);
        $this->assertStringContainsString('COPY composer.json composer.lock patches.lock.json ./', $applicationBuild);
        $this->assertStringContainsString('COPY patches/ patches/', $applicationBuild);
        $this->assertStringContainsString('composer install', $applicationBuild);
        $this->assertStringContainsString('--no-dev', $applicationBuild);

        $this->assertLessThan(
            strpos($applicationBuild, 'composer install'),
            strpos($applicationBuild, 'COPY patches/ patches/'),
        );
        $this->assertLessThan(
            strpos($applicationBuild, 'COPY . .'),
            strpos($applicationBuild, 'composer install'),
        );

        $this->assertStringNotContainsString('apt-get install -y --no-install-recommends git', $phpBase);
        $this->assertStringNotContainsString('apt-get install -y --no-install-recommends git', $runtime);
        $this->assertStringNotContainsString('/usr/bin/git', $runtime);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($this->basePath.'/'.$path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<int, array<string, mixed>>  $patches
     * @return array<string, array<string, mixed>>
     */
    private function indexPatches(array $patches): array
    {
        $indexed = [];

        foreach ($patches as $patch) {
            $indexed[$patch['url']] = $patch;
        }

        return $indexed;
    }

    private function stage(string $dockerfile, string $stage, string $nextStage): string
    {
        $pattern = sprintf(
            '/^FROM [^\r\n]+ AS %s\R(?<contents>.*?)^FROM [^\r\n]+ AS %s\R/ms',
            preg_quote($stage, '/'),
            preg_quote($nextStage, '/'),
        );

        $this->assertSame(1, preg_match($pattern, $dockerfile, $matches));

        return $matches['contents'];
    }
}
