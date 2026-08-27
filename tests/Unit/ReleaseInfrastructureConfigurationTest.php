<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReleaseInfrastructureConfigurationTest extends TestCase
{
    public function test_local_app_image_includes_the_ldap_extension_used_by_supported_authentication(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $dockerfile = file_get_contents($basePath . '/docker/app/Dockerfile');

        $this->assertMatchesRegularExpression(
            '/^RUN install-php-extensions .*\bldap\b/m',
            $dockerfile,
        );
    }

    public function test_local_app_image_copies_postinstall_inputs_before_npm_install(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $dockerfile = file_get_contents($basePath . '/docker/app/Dockerfile');

        $compatPackage = strpos(
            $dockerfile,
            'COPY packages/brace-expansion-compat/ packages/brace-expansion-compat/'
        );
        $bootstrapPatch = strpos(
            $dockerfile,
            'COPY scripts/patch-bootstrap3-security.cjs scripts/patch-bootstrap3-security.cjs'
        );
        $npmInstall = strpos($dockerfile, 'RUN npm ci || npm install');

        $this->assertNotFalse($compatPackage);
        $this->assertNotFalse($bootstrapPatch);
        $this->assertNotFalse($npmInstall);
        $this->assertTrue($compatPackage < $npmInstall);
        $this->assertTrue($bootstrapPatch < $npmInstall);
    }

    public function test_root_dockerfile_recreates_the_ignored_backup_parent_before_linking_it(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $dockerfile = file_get_contents($basePath . '/Dockerfile');
        $dockerignore = file_get_contents($basePath . '/.dockerignore');

        $mkdirPosition = strpos($dockerfile, 'mkdir -p "/var/www/html/storage/app"');
        $removePosition = strpos($dockerfile, 'rm -rf "/var/www/html/storage/app/backups"');
        $linkPosition = strpos(
            $dockerfile,
            'ln -fs "/var/lib/snipeit/dumps" "/var/www/html/storage/app/backups"'
        );

        $this->assertStringContainsString('storage/app/**', $dockerignore);
        $this->assertNotFalse($mkdirPosition);
        $this->assertNotFalse($removePosition);
        $this->assertNotFalse($linkPosition);
        $this->assertLessThan($removePosition, $mkdirPosition);
        $this->assertLessThan($linkPosition, $removePosition);
    }

    public function test_docker_build_context_recursively_excludes_secrets_databases_and_runtime_state(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $rules = array_values(array_filter(
            array_map('trim', file($basePath . '/.dockerignore', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)),
            static fn (string $rule): bool => ! str_starts_with($rule, '#'),
        ));
        $imageVerifier = file_get_contents($basePath . '/scripts/ci/verify-production-image.sh');

        $sensitiveFilePatterns = [
            '*.db',
            '*.db-*',
            '*.db3',
            '*.s3db',
            '*.rdb',
            '*.mdb',
            '*.accdb',
            '*.key',
            '*.pem',
            '*.crt',
            '*.cer',
            '*.der',
            '*.csr',
            '*.p12',
            '*.pfx',
            '*.ppk',
            '*.jks',
            '*.keystore',
        ];

        foreach ($sensitiveFilePatterns as $pattern) {
            $this->assertContains($pattern, $rules);
            $this->assertContains('**/'.$pattern, $rules);
            $this->assertStringContainsString("-name '{$pattern}'", $imageVerifier);
        }

        foreach (['id_rsa', 'id_dsa', 'id_ecdsa', 'id_ed25519'] as $privateKeyName) {
            $this->assertContains($privateKeyName, $rules);
            $this->assertContains('**/'.$privateKeyName, $rules);
            $this->assertStringContainsString("-name '{$privateKeyName}'", $imageVerifier);
        }

        foreach ([
            'bootstrap/cache/**',
            '!bootstrap/cache/.gitignore',
            'storage/framework/cache/**',
            '!storage/framework/cache/.gitignore',
            'storage/framework/sessions/**',
            '!storage/framework/sessions/.gitignore',
            'storage/framework/views/**',
            '!storage/framework/views/.gitignore',
            'storage/framework/testing/**',
            '.phpunit.cache',
            'tests/Browser/console/**',
            'tests/Browser/screenshots/**',
        ] as $runtimeRule) {
            $this->assertContains($runtimeRule, $rules);
        }

        $sensitiveExceptions = array_values(array_filter(
            $rules,
            static fn (string $rule): bool => str_starts_with($rule, '!')
                && preg_match('/\.(?:key|pem|crt|cer|der|csr|p12|pfx|ppk|jks|keystore)$/i', $rule) === 1,
        ));

        $this->assertSame([], $sensitiveExceptions);
    }

    public function test_dusk_example_uses_the_portable_dedicated_sqlite_target(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $example = file_get_contents($basePath . '/.env.dusk.example');

        $this->assertMatchesRegularExpression('/^APP_ENV=testing$/m', $example);
        $this->assertMatchesRegularExpression('/^SNIPEIT_DUSK_GUARD=1$/m', $example);
        $this->assertMatchesRegularExpression('/^ALLOW_WEB_SETUP=false$/m', $example);
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=sqlite$/m', $example);
        $this->assertMatchesRegularExpression('/^DB_DATABASE=database\/dusk\.sqlite$/m', $example);
    }

    public function test_v1_quality_gate_builds_and_scans_releasable_images(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $workflow = file_get_contents($basePath . '/.github/workflows/v1-quality-gate.yml');
        $trivyIgnore = file_get_contents($basePath . '/.trivyignore.yaml');
        $imageVerifier = file_get_contents($basePath . '/scripts/ci/verify-production-image.sh');
        $dockerignore = file_get_contents($basePath . '/.dockerignore');

        $this->assertStringContainsString('composer patches-doctor', $workflow);
        $this->assertStringContainsString('composer audit --locked --abandoned=fail', $workflow);
        $this->assertStringContainsString('npm audit --omit=dev --audit-level=high', $workflow);
        $this->assertStringContainsString("git ls-files -- '.env' '.env.*'", $workflow);
        $this->assertStringNotContainsString('vendor/bin/phpstan analyse', $workflow);
        $this->assertStringContainsString('git diff --exit-code -- public', $workflow);
        $this->assertStringContainsString(
            'mariadb:11.4.7@sha256:39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06',
            $workflow,
        );
        $this->assertStringContainsString('--target app', $workflow);
        $this->assertStringContainsString('--target web', $workflow);
        $this->assertStringContainsString('scripts/ci/verify-production-image.sh', $workflow);
        $this->assertStringContainsString('format: cyclonedx', $workflow);
        $this->assertStringContainsString('severity: HIGH,CRITICAL', $workflow);
        $this->assertStringContainsString('trivyignores: .trivyignore.yaml', $workflow);
        $this->assertStringContainsString('output: app-image-security-report.json', $workflow);
        $this->assertStringContainsString('output: web-image-security-report.json', $workflow);
        $this->assertGreaterThanOrEqual(2, substr_count($workflow, 'ignore-unfixed: true'));

        foreach ([
            'CVE-2026-48019',
            'GHSA-5vg9-5847-vvmq',
            'GHSA-crmm-hgp2-wgrp',
        ] as $advisory) {
            $this->assertStringContainsString('- id: '.$advisory, $trivyIgnore);
        }

        $this->assertStringContainsString('command -v git', $imageVerifier);
        $this->assertStringContainsString('vendor/laravelcollective', $imageVerifier);
        $this->assertStringContainsString('Email addresses may not contain line break characters.', $imageVerifier);
        $this->assertStringContainsString('strtr(rawurlencode(', $imageVerifier);
        $this->assertStringContainsString('storage/private_uploads', $imageVerifier);
        $this->assertStringContainsString('storage/oauth-private.key', $imageVerifier);
        $this->assertStringContainsString('storage/oauth-public.key', $imageVerifier);

        $this->assertStringContainsString('storage/private_uploads/**', $dockerignore);
        $this->assertStringContainsString('.trivyignore.yaml', $dockerignore);
    }

    public function test_browser_ci_uses_only_the_dedicated_guarded_sqlite_profile(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $workflow = file_get_contents($basePath . '/.github/workflows/tests-browser.yml');
        $example = file_get_contents($basePath . '/.env.dusk.example');
        $duskBase = file_get_contents($basePath . '/tests/DuskTestCase.php');

        $this->assertStringContainsString('DB_CONNECTION: sqlite', $workflow);
        $this->assertStringContainsString('DB_DATABASE: database/dusk.sqlite', $workflow);
        $this->assertStringContainsString('SNIPEIT_DUSK_GUARD: "1"', $workflow);
        $this->assertStringContainsString('npm ci --no-audit --no-fund', $workflow);
        $this->assertStringContainsString('npm run prod', $workflow);
        $this->assertStringContainsString('php artisan dusk --env=example', $workflow);
        $this->assertStringContainsString('http://127.0.0.1:8000/health', $workflow);
        $this->assertStringNotContainsString('mysql', strtolower($workflow));
        $this->assertStringNotContainsString('pgsql', strtolower($workflow));

        $this->assertMatchesRegularExpression('/^APP_ENV=testing$/m', $example);
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=sqlite$/m', $example);
        $this->assertMatchesRegularExpression('/^DB_DATABASE=database\/dusk\.sqlite$/m', $example);
        $this->assertMatchesRegularExpression('/^APP_FORCE_TLS=false$/m', $example);
        $this->assertMatchesRegularExpression('/^ALLOW_WEB_SETUP=false$/m', $example);
        $this->assertMatchesRegularExpression('/^SECURE_COOKIES=false$/m', $example);

        $this->assertStringContainsString(
            "protected string \$baseUrl = 'http://127.0.0.1:8000';",
            $duskBase,
        );
        $this->assertStringNotContainsString('dev.snipe.inbit', $duskBase);
    }
}
