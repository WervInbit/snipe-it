<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReleaseDistributionBoundaryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__.'/../..');
    }

    public function testLegacyInstallAndUpgradeEntrypointsFailClosed(): void
    {
        foreach (['install.sh', 'snipeit.sh'] as $script) {
            $contents = file_get_contents($this->basePath.'/'.$script);

            $this->assertStringContainsString('Unsupported installer', $contents, $script);
            $this->assertStringContainsString('exit 1', $contents, $script);
            $this->assertStringNotContainsString('raw.githubusercontent.com/grokability', $contents, $script);
        }

        $upgrade = file_get_contents($this->basePath.'/upgrade.php');
        $this->assertStringContainsString('Unsupported upgrader', $upgrade);
        $this->assertStringContainsString('exit(1)', $upgrade);
        $this->assertStringNotContainsString('git pull', $upgrade);
        $this->assertStringNotContainsString('raw.githubusercontent.com/grokability', $upgrade);

        $vagrant = file_get_contents($this->basePath.'/Vagrantfile');
        $this->assertStringContainsString('Unsupported Vagrant profile', $vagrant);
        $this->assertStringNotContainsString('raw.githubusercontent.com/grokability', $vagrant);

        $legacyDockerfile = file_get_contents($this->basePath.'/Dockerfile.fpm-alpine');
        $this->assertStringContainsString('Unsupported Dockerfile', $legacyDockerfile);
        $this->assertStringContainsString('exit 1', $legacyDockerfile);
        $this->assertStringNotContainsString('github.com/grokability/snipe-it/archive', $legacyDockerfile);
    }

    public function testUnsupportedUpstreamPublishingAndOneClickDeployMetadataIsAbsent(): void
    {
        foreach ([
            'app.json',
            '.github/workflows/docker-alpine.yml',
            '.github/workflows/docker-ubuntu.yml',
            '.github/workflows/dockerhub-description.yml',
            '.github/CODEOWNERS',
            '.github/config.yml',
            '.github/FUNDING.yml',
        ] as $unsupportedPath) {
            $this->assertFileDoesNotExist($this->basePath.'/'.$unsupportedPath);
        }
    }

    public function testUpgradeMetadataAndDockerDocumentationNameTheForkBoundary(): void
    {
        $requirements = json_decode(
            file_get_contents($this->basePath.'/.upgrade_requirements.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Inbit Device Refurbishment Platform', $requirements['product']);
        $this->assertFalse($requirements['legacy_upgrade_supported']);
        $this->assertSame('docs/production-deployment.md', $requirements['upgrade_runbook']);

        $dockerReadme = file_get_contents($this->basePath.'/docker/README.md');
        $this->assertStringContainsString(
            'docker-compose.production.yml',
            $dockerReadme,
        );
        $this->assertStringNotContainsString('snipe-it.readme.io', $dockerReadme);
    }

    public function testSetupAndNotificationFallbacksUseTheForkIdentity(): void
    {
        $fallbackFiles = [
            'resources/views/layouts/setup.blade.php',
            'resources/views/layouts/basic.blade.php',
            'resources/views/setup/user.blade.php',
            'resources/views/vendor/mail/html/message.blade.php',
            'resources/views/vendor/mail/markdown/message.blade.php',
            'resources/views/vendor/notifications/email.blade.php',
            'resources/views/notifications/Test.blade.php',
        ];

        foreach ($fallbackFiles as $fallbackFile) {
            $contents = file_get_contents($this->basePath.'/'.$fallbackFile);

            $this->assertStringContainsString(
                "config('app.name')",
                $contents,
                $fallbackFile,
            );
        }

        $appConfig = file_get_contents($this->basePath.'/config/app.php');
        $this->assertStringContainsString(
            "'name' => env('SITE_NAME', 'Inbit Device Refurbishment')",
            $appConfig,
        );

        $contributors = file_get_contents($this->basePath.'/CONTRIBUTORS.md');
        $this->assertStringStartsWith('# Upstream contributor history', $contributors);
    }
}
