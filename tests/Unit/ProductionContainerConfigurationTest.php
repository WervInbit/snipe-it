<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductionContainerConfigurationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__.'/../..');
    }

    public function test_production_compose_uses_immutable_services_and_external_secrets(): void
    {
        $compose = file_get_contents($this->basePath.'/docker-compose.production.yml');

        $this->assertMatchesRegularExpression('/^  (app|web|queue|scheduler):$/m', $compose);
        $this->assertSame(4, preg_match_all('/^  (app|web|queue|scheduler):$/m', $compose));
        $this->assertStringContainsString('profiles:', $compose);
        $this->assertStringContainsString('- production', $compose);
        $this->assertStringContainsString(
            'image: ${SNIPEIT_APP_IMAGE:?Set the application image repository}@${SNIPEIT_APP_IMAGE_DIGEST:?Set the immutable application image sha256 digest}',
            $compose,
        );
        $this->assertStringContainsString(
            'image: ${SNIPEIT_WEB_IMAGE:?Set the web image repository}@${SNIPEIT_WEB_IMAGE_DIGEST:?Set the immutable web image sha256 digest}',
            $compose,
        );
        $this->assertStringNotContainsString('SNIPEIT_IMAGE_TAG', $compose);
        $this->assertStringNotContainsString('  build:', $compose);
        $this->assertStringContainsString('read_only: true', $compose);
        $this->assertStringContainsString('restart: unless-stopped', $compose);
        $this->assertStringContainsString('healthcheck:', $compose);
        $this->assertStringContainsString('APP_KEY_FILE: /run/secrets/app_key', $compose);
        $this->assertStringContainsString('ALLOW_BACKUP_RESTORE: "false"', $compose);
        $this->assertStringContainsString('ALLOW_WEB_SETUP: "false"', $compose);
        $this->assertStringContainsString('DB_PASSWORD_FILE: /run/secrets/db_password', $compose);
        $this->assertStringContainsString('MAIL_ENABLED: "false"', $compose);
        $this->assertStringContainsString('MAIL_MAILER: array', $compose);
        $this->assertStringContainsString('MAIL_TLS_VERIFY_PEER: "true"', $compose);
        $this->assertStringContainsString('LDAP_INTEGRATION_ENABLED: "false"', $compose);
        $this->assertStringContainsString('AGENT_API_TOKEN_FILE: /run/secrets/agent_api_token', $compose);
        $this->assertStringContainsString('AGENT_ALLOWED_IPS: ${AGENT_ALLOWED_IPS:-}', $compose);
        $this->assertStringContainsString('DB_DUMP_PATH:', $compose);
        $this->assertStringContainsString('APP_MAINTENANCE_DRIVER: cache', $compose);
        $this->assertStringContainsString('APP_MAINTENANCE_STORE: redis', $compose);
        $this->assertStringContainsString('PASSPORT_PRIVATE_KEY_FILE: /run/secrets/passport_private_key', $compose);
        $this->assertStringContainsString('/var/www/html/storage/app/backup-temp:', $compose);
        $this->assertStringContainsString('production_public_uploads:', $compose);
        $this->assertStringContainsString('production_private_uploads:', $compose);
        $this->assertStringContainsString('production_backups:', $compose);
        $this->assertDoesNotMatchRegularExpression('/^  db:$/m', $compose);
        $this->assertStringNotContainsString('./:/var/www/html', $compose);
        $this->assertStringNotContainsString('MYSQL_PASSWORD:', $compose);
        $this->assertStringNotContainsString('MYSQL_ROOT_PASSWORD:', $compose);
        $this->assertStringNotContainsString('MAIL_PASSWORD:', $compose);
        $this->assertStringNotContainsString('MAIL_PASSWORD_FILE:', $compose);
        $this->assertStringNotContainsString('MAIL_HOST:', $compose);
        $this->assertStringNotContainsString('AGENT_API_TOKEN:', $compose);
    }

    public function test_production_environment_template_requires_separate_image_digests(): void
    {
        $example = file_get_contents($this->basePath.'/docker/production.env.example');

        $this->assertMatchesRegularExpression(
            '/^SNIPEIT_APP_IMAGE=registry\.example\.invalid\/inbit\/snipeit-app$/m',
            $example,
        );
        $this->assertMatchesRegularExpression(
            '/^SNIPEIT_APP_IMAGE_DIGEST=sha256:0{64}$/m',
            $example,
        );
        $this->assertMatchesRegularExpression(
            '/^SNIPEIT_WEB_IMAGE=registry\.example\.invalid\/inbit\/snipeit-web$/m',
            $example,
        );
        $this->assertMatchesRegularExpression(
            '/^SNIPEIT_WEB_IMAGE_DIGEST=sha256:0{64}$/m',
            $example,
        );
        $this->assertStringNotContainsString('SNIPEIT_IMAGE_TAG', $example);
        $this->assertMatchesRegularExpression('/^MAIL_ENABLED=false$/m', $example);
        $this->assertMatchesRegularExpression('/^LDAP_INTEGRATION_ENABLED=false$/m', $example);
        $this->assertDoesNotMatchRegularExpression('/^MAIL_HOST=/m', $example);
        $this->assertDoesNotMatchRegularExpression('/^MAIL_PASSWORD_FILE=/m', $example);
        $this->assertMatchesRegularExpression(
            '/^AGENT_API_TOKEN_FILE=\/srv\/snipeit\/secrets\/agent_api_token$/m',
            $example,
        );
    }

    public function test_production_entrypoint_validates_enabled_mail_without_requiring_it_when_disabled(): void
    {
        $entrypoint = file_get_contents($this->basePath.'/docker/production/entrypoint.sh');

        $this->assertStringContainsString('case "${MAIL_ENABLED:-false}"', $entrypoint);
        $this->assertStringContainsString('MAIL_MAILER must be smtp when MAIL_ENABLED is true.', $entrypoint);
        $this->assertStringContainsString('require_env MAIL_HOST', $entrypoint);
        $this->assertStringContainsString('require_true MAIL_TLS_VERIFY_PEER', $entrypoint);
        $this->assertStringContainsString('case "${LDAP_INTEGRATION_ENABLED:-false}"', $entrypoint);
    }

    public function test_production_disables_the_non_atomic_uploaded_backup_restore(): void
    {
        $appConfig = file_get_contents($this->basePath.'/config/app.php');
        $envExample = file_get_contents($this->basePath.'/.env.example');
        $runbook = file_get_contents($this->basePath.'/docs/production-deployment.md');

        $this->assertStringContainsString(
            "'allow_backup_restore' => env('ALLOW_BACKUP_RESTORE', false)",
            $appConfig,
        );
        $this->assertMatchesRegularExpression('/^ALLOW_BACKUP_RESTORE=false$/m', $envExample);
        $this->assertStringContainsString(
            'The inherited uploaded-backup web restore is destructive and non-atomic.',
            $runbook,
        );
        $this->assertStringContainsString('Do not enable it for the V1', $runbook);
    }

    public function test_production_disables_browser_setup_and_documents_cli_bootstrap(): void
    {
        $appConfig = file_get_contents($this->basePath.'/config/app.php');
        $envExample = file_get_contents($this->basePath.'/.env.example');
        $runbook = file_get_contents($this->basePath.'/docs/production-deployment.md');

        $this->assertStringContainsString(
            "'allow_web_setup' => env('ALLOW_WEB_SETUP', false)",
            $appConfig,
        );
        $this->assertMatchesRegularExpression('/^ALLOW_WEB_SETUP=false$/m', $envExample);
        $this->assertStringContainsString('ALLOW_WEB_SETUP=false', $runbook);
        $this->assertMatchesRegularExpression(
            '/db:seed\s+\\\\?\s*--class=ProductionFoundationSeeder --force/',
            $runbook,
        );
        $this->assertStringContainsString('snipeit:create-admin --bootstrap', $runbook);
        $this->assertStringContainsString(
            'Do not expose the web service until both commands succeed.',
            $runbook,
        );

        $localCompose = file_get_contents($this->basePath.'/docker-compose.localhost.yml');
        $this->assertStringContainsString('ALLOW_WEB_SETUP=true', $localCompose);
    }

    public function test_production_maintenance_mode_uses_the_shared_redis_store(): void
    {
        $appConfig = file_get_contents($this->basePath.'/config/app.php');
        $maintenanceMiddleware = file_get_contents(
            $this->basePath.'/app/Http/Middleware/PreventRequestsDuringMaintenance.php'
        );
        $httpKernel = file_get_contents($this->basePath.'/app/Http/Kernel.php');

        $this->assertStringContainsString(
            "'driver' => env('APP_MAINTENANCE_DRIVER', 'file')",
            $appConfig,
        );
        $this->assertStringContainsString(
            "'store' => env('APP_MAINTENANCE_STORE', 'database')",
            $appConfig,
        );
        $this->assertStringContainsString("'health'", $maintenanceMiddleware);
        $this->assertStringContainsString(
            '\\App\\Http\\Middleware\\PreventRequestsDuringMaintenance::class',
            $httpKernel,
        );
        $this->assertStringNotContainsString(
            '\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance::class',
            $httpKernel,
        );
    }

    public function test_production_runbook_quiesces_writers_during_backup_and_migration(): void
    {
        $runbook = file_get_contents($this->basePath.'/docs/production-deployment.md');

        $maintenanceDown = strpos($runbook, 'exec app php artisan down --retry=60');
        $stopWriters = strpos($runbook, 'stop --timeout 180 queue scheduler');
        $backup = strpos($runbook, 'run --rm app php artisan snipeit:backup');
        $migration = strpos($runbook, 'run --rm app php artisan migrate --force');
        $permissionSeed = strpos($runbook, '--class=ProductionPermissionGroupSeeder --force');
        $maintenanceUp = strpos($runbook, 'exec app php artisan up');
        $startWriters = strpos($runbook, 'up -d --no-build queue scheduler');

        $this->assertNotFalse($maintenanceDown);
        $this->assertNotFalse($stopWriters);
        $this->assertNotFalse($backup);
        $this->assertNotFalse($migration);
        $this->assertNotFalse($permissionSeed);
        $this->assertNotFalse($maintenanceUp);
        $this->assertNotFalse($startWriters);
        $this->assertTrue(
            $maintenanceDown < $stopWriters
            && $stopWriters < $backup
            && $backup < $migration
            && $migration < $permissionSeed
            && $permissionSeed < $maintenanceUp
            && $maintenanceUp < $startWriters,
            'The runbook must stop writers before backup/migration, merge the required role floor, and restart them only after maintenance mode is disabled.',
        );
    }

    public function test_production_image_builds_dependencies_and_assets_before_runtime(): void
    {
        $dockerfile = file_get_contents($this->basePath.'/docker/production/Dockerfile');

        $compatPackage = strpos(
            $dockerfile,
            'COPY packages/brace-expansion-compat/ packages/brace-expansion-compat/'
        );
        $bootstrapPatch = strpos(
            $dockerfile,
            'COPY scripts/patch-bootstrap3-security.cjs scripts/patch-bootstrap3-security.cjs'
        );
        $npmInstall = strpos($dockerfile, 'npm ci --no-audit --no-fund');

        $this->assertNotFalse($compatPackage);
        $this->assertNotFalse($bootstrapPatch);
        $this->assertNotFalse($npmInstall);
        $this->assertTrue($compatPackage < $npmInstall);
        $this->assertTrue($bootstrapPatch < $npmInstall);
        $this->assertStringContainsString('npm ci --no-audit --no-fund', $dockerfile);
        $this->assertStringContainsString('npm run production', $dockerfile);
        $this->assertStringContainsString('composer install', $dockerfile);
        $this->assertStringContainsString('--no-dev', $dockerfile);
        $this->assertStringContainsString('apt-get purge -y --auto-remove', $dockerfile);
        $this->assertStringContainsString("'*-dev'", $dockerfile);
        $this->assertStringContainsString('gcc-12', $dockerfile);
        $this->assertStringContainsString('FROM php-base AS app', $dockerfile);
        $this->assertStringContainsString('FROM nginxinc/nginx-unprivileged:', $dockerfile);
        $this->assertStringContainsString('COPY --from=application-build', $dockerfile);
        $this->assertStringNotContainsString('postgresql-client', $dockerfile);
        $this->assertStringNotContainsString('pdo_pgsql', $dockerfile);
    }

    public function test_production_build_inputs_are_pinned_to_immutable_image_indexes(): void
    {
        $dockerfile = file_get_contents($this->basePath.'/docker/production/Dockerfile');

        foreach ([
            '# syntax=docker/dockerfile:1.7@sha256:a57df69d0ea827fb7266491f2813635de6f17269be881f696fbfdf2d83dda33e',
            'node:20-bookworm-slim@sha256:2cf067cfed83d5ea958367df9f966191a942351a2df77d6f0193e162b5febfc0',
            'php:8.2-fpm-bookworm@sha256:5623a1f394cfc9ec9710efc975db6d746618f1c3e047649232d5432a0b2f942c',
            'mlocati/php-extension-installer:2@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138',
            'composer:2.8@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c',
            'nginxinc/nginx-unprivileged:1.30-alpine@sha256:44e36330f74d4f3a1d4e222acca9e23b401fb87811a7597024502bb759c4dd49',
        ] as $pinnedReference) {
            $this->assertStringContainsString($pinnedReference, $dockerfile);
        }

        $this->assertDoesNotMatchRegularExpression(
            '/^(?:FROM\s+|COPY --from=)(?!php-base\b|application-build\b|frontend\b)[^@\s]+(?:\s|$)/m',
            $dockerfile,
            'Every external production build image must include an immutable sha256 digest.',
        );
    }

    public function test_docker_build_context_excludes_runtime_cache_and_session_artifacts(): void
    {
        $dockerignore = file_get_contents($this->basePath.'/.dockerignore');

        foreach ([
            'bootstrap/cache/**',
            'storage/framework/cache/**',
            'storage/framework/sessions/**',
            'storage/framework/views/**',
            'storage/framework/testing/**',
            '.phpunit.cache',
            'tests/Browser/console/**',
            'tests/Browser/screenshots/**',
            'test-results',
            'tmp',
            'output',
            'codexlog',
            '*.code-workspace',
            '/hw-inventory.*',
            'scripts/**',
            '!scripts/patch-bootstrap3-security.cjs',
        ] as $runtimePath) {
            $this->assertMatchesRegularExpression(
                '/^'.preg_quote($runtimePath, '/').'$/m',
                $dockerignore,
                $runtimePath.' must not enter the production Docker build context.',
            );
        }
    }

    public function test_production_entrypoint_fails_closed_without_mutating_the_release(): void
    {
        $entrypoint = file_get_contents($this->basePath.'/docker/production/entrypoint.sh');

        $this->assertStringContainsString('file_env APP_KEY true', $entrypoint);
        $this->assertStringContainsString('file_env AGENT_API_TOKEN false', $entrypoint);
        $this->assertStringContainsString('materialize_passport_key', $entrypoint);
        $this->assertStringContainsString('Passport private/public keys are invalid or do not form a pair.', $entrypoint);
        $this->assertStringContainsString(
            'APP_TRUSTED_PROXIES must contain only comma-separated literal IP addresses or non-zero CIDRs',
            $entrypoint,
        );
        $this->assertStringContainsString('inet_pton($address)', $entrypoint);
        $this->assertStringContainsString('(int) $parts[1] < 1', $entrypoint);
        $this->assertStringContainsString(
            'DB_CONNECTION must be mysql for the declared V1 MariaDB/MySQL support matrix.',
            $entrypoint,
        );
        $this->assertStringContainsString('if [ "${1:-}" = "php-fpm" ]; then', $entrypoint);
        $this->assertStringContainsString('exec "$@"', $entrypoint);
        $this->assertStringContainsString('exec gosu www-data "$@"', $entrypoint);
        $this->assertStringContainsString('user = www-data', file_get_contents(
            $this->basePath.'/docker/production/php-fpm.conf'
        ));
        $this->assertStringNotContainsString('composer install', $entrypoint);
        $this->assertStringNotContainsString('artisan migrate', $entrypoint);
        $this->assertStringNotContainsString('passport:keys', $entrypoint);
        $this->assertStringNotContainsString('key:generate', $entrypoint);
    }

    public function test_passport_keys_are_not_generated_during_application_boot(): void
    {
        $provider = file_get_contents($this->basePath.'/app/Providers/AppServiceProvider.php');
        $dashboard = file_get_contents($this->basePath.'/app/Http/Controllers/DashboardController.php');

        $this->assertStringNotContainsString('passport:keys', $provider);
        $this->assertStringNotContainsString('Passport keys generated automatically', $provider);
        $this->assertStringNotContainsString("Artisan::call('passport:install'", $dashboard);
        $this->assertStringNotContainsString("Artisan::call('migrate'", $dashboard);
        $this->assertStringContainsString("app()->environment('production')", $dashboard);
        $this->assertStringContainsString('abort(503', $dashboard);
    }

    public function test_production_nginx_blocks_script_execution_in_uploads(): void
    {
        $nginx = file_get_contents($this->basePath.'/docker/production/nginx.conf');

        $this->assertStringContainsString('location ^~ /uploads/test_images/', $nginx);
        $this->assertStringContainsString('^/uploads/.*\\.(?:php[0-9]?|phtml|phar|cgi|pl|py|sh)', $nginx);
        $this->assertStringContainsString('location ~ \\.php$', $nginx);
        $this->assertStringContainsString('return 404;', $nginx);
    }
}
