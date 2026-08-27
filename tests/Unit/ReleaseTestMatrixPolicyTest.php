<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ReleaseTestMatrixPolicyTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__ . '/../..');
    }

    public function testDatabaseWorkflowsFailOnIncompleteAndSkippedTests(): void
    {
        foreach (['tests-mysql.yml', 'tests-sqlite.yml', 'tests-postgres.yml'] as $workflowName) {
            $workflow = file_get_contents($this->basePath . '/.github/workflows/' . $workflowName);

            $this->assertStringContainsString(
                'php artisan test --env=testing --fail-on-incomplete --fail-on-skipped',
                $workflow,
                $workflowName . ' must not silently accept incomplete or skipped tests.',
            );
        }
    }

    public function testTestSuiteHasNoRuntimeIncompleteMarkers(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath . '/tests')
        );
        $violations = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (
                !$file->isFile()
                || $file->getExtension() !== 'php'
                || $file->getRealPath() === __FILE__
            ) {
                continue;
            }

            $contents = file_get_contents($file->getRealPath());
            if (
                str_contains($contents, 'markTestIncomplete(')
                || str_contains($contents, 'markIncompleteIf')
            ) {
                $violations[] = str_replace($this->basePath . '/', '', $file->getRealPath());
            }
        }

        $this->assertSame(
            [],
            $violations,
            'Database-specific tests must be executable in their supported CI job, not hidden as incomplete.',
        );
    }

    public function testMariaDbWorkflowTargetsOnlyTheGuardedDisposableDatabase(): void
    {
        $workflow = file_get_contents($this->basePath . '/.github/workflows/tests-mysql.yml');

        $this->assertStringContainsString('DB_DATABASE: snipeit_test', $workflow);
        $this->assertStringContainsString('SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE: "1"', $workflow);
        $this->assertStringContainsString(
            'mariadb:11.4.7@sha256:39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06',
            $workflow,
        );
        $this->assertStringContainsString(
            "throw_unless(app()->environment('testing') && config('database.default') === 'mysql'",
            $workflow,
        );
        $this->assertStringNotContainsString('DB_DATABASE: snipeit_prod_work', $workflow);
    }

    public function testCompatibilityDatabaseWorkflowUsesAnImmutableDisposableTarget(): void
    {
        $workflow = file_get_contents($this->basePath . '/.github/workflows/tests-postgres.yml');

        $this->assertStringContainsString(
            'postgres:16.10@sha256:21f6013073bc6b92830a2129570e2f5ec42a6c734b5a985a41e83aa58f54c3c1',
            $workflow,
        );
        $this->assertStringContainsString('DB_DATABASE: snipeit_test', $workflow);
        $this->assertStringContainsString(
            "throw_unless(app()->environment('testing') && config('database.default') === 'pgsql'",
            $workflow,
        );
    }

    public function testV1ImagesRequireTheStrictGuardedApplicationSuite(): void
    {
        $workflow = file_get_contents($this->basePath . '/.github/workflows/v1-quality-gate.yml');

        $this->assertStringContainsString('sqlite-tests:', $workflow);
        $this->assertStringContainsString('mariadb-tests:', $workflow);
        $this->assertStringContainsString('DB_DATABASE: ":memory:"', $workflow);
        $this->assertStringContainsString('DB_DATABASE: snipeit_test', $workflow);
        $this->assertStringContainsString('SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE: "1"', $workflow);
        $this->assertStringContainsString(
            'php artisan test --env=testing --fail-on-incomplete --fail-on-skipped',
            $workflow,
        );
        $this->assertStringContainsString('scanners: vuln,secret,misconfig', $workflow);
        $this->assertStringContainsString('source-license-report.json', $workflow);
        $this->assertStringContainsString('app-image-license-report.json', $workflow);
        $this->assertStringContainsString('web-image-license-report.json', $workflow);
        $this->assertMatchesRegularExpression(
            '/production-images:.*?needs:\s*-\s*quality\s*-\s*sqlite-tests\s*-\s*mariadb-tests/s',
            $workflow,
            'Production images must not build unless source quality and both supported database suites pass.',
        );
    }

    public function testEnvironmentTemplatesUseTheQueueKeyReadByLaravel(): void
    {
        foreach ([
            '.env.example',
            '.env.dusk.example',
            'docker/docker.env',
            'docker/docker-secrets.env',
            'phpunit.xml',
        ] as $relativePath) {
            $contents = file_get_contents($this->basePath . '/' . $relativePath);

            $this->assertStringContainsString('QUEUE_CONNECTION', $contents, $relativePath);
            $this->assertStringNotContainsString('QUEUE_DRIVER', $contents, $relativePath);
        }
    }

    public function testDefaultPhpUnitRunDoesNotExecuteTheNamedApiSliceTwice(): void
    {
        $configuration = file_get_contents($this->basePath . '/phpunit.xml');

        $this->assertSame(
            1,
            preg_match('/<testsuite name="Feature">(.*?)<\/testsuite>/s', $configuration, $featureMatch),
        );
        $this->assertSame(
            1,
            preg_match('/<testsuite name="API">(.*?)<\/testsuite>/s', $configuration, $apiMatch),
        );
        $this->assertGreaterThan(
            0,
            preg_match_all('/<(?:file|directory)(?:\s+[^>]*)?>(.*?)<\/(?:file|directory)>/', $apiMatch[1], $apiEntries),
        );

        foreach ($apiEntries[1] as $apiEntry) {
            $this->assertStringContainsString(
                '<exclude>'.$apiEntry.'</exclude>',
                $featureMatch[1],
                $apiEntry.' must be excluded from Feature because the API suite runs it separately.',
            );
        }
    }
}
