<?php

namespace Tests;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\AssertsAgainstSlackNotifications;
use Tests\Support\AssertHasActionLogs;
use Tests\Support\CanSkipTests;
use Tests\Support\CustomTestMacros;
use Tests\Support\InteractsWithAuthentication;
use Tests\Support\InitializesSettings;

abstract class TestCase extends BaseTestCase
{
    use AssertsAgainstSlackNotifications;
    use CanSkipTests;
    use CreatesApplication;
    use CustomTestMacros;
    use InteractsWithAuthentication;
    use InitializesSettings;
    use LazilyRefreshDatabase;
    use AssertHasActionLogs;

    private array $globallyDisabledMiddleware = [
        SecurityHeaders::class,
    ];

    protected function setUp(): void
    {
        $this->guardAgainstMissingEnv();
        $this->guardAgainstUnsafeTestingConfig();

        parent::setUp();

        $this->registerCustomMacros();

        $this->withoutMiddleware($this->globallyDisabledMiddleware);

        $this->initializeSettings();
    }

    private function guardAgainstMissingEnv(): void
    {
        if (!file_exists(realpath(__DIR__ . '/../') . '/.env.testing')) {
            throw new RuntimeException(
                '.env.testing file does not exist. Aborting to avoid wiping your local database.'
            );
        }
    }

    private function guardAgainstUnsafeTestingConfig(): void
    {
        $basePath = realpath(__DIR__ . '/../');
        $configCachePath = $basePath . '/bootstrap/cache/config.php';

        if (file_exists($configCachePath)) {
            throw new RuntimeException(
                'Refusing to run tests while bootstrap/cache/config.php exists. ' .
                'Cached local config can override PHPUnit testing DB settings and hit the dev database. ' .
                'Run `php artisan optimize:clear` in the app container first.'
            );
        }

        $testingEnv = $this->readEnvironmentFile($basePath . '/.env.testing');
        $dbConnection = $testingEnv['DB_CONNECTION'] ?? null;
        if ($dbConnection !== 'sqlite') {
            throw new RuntimeException(
                'Refusing to run tests because .env.testing DB_CONNECTION is not sqlite. Current DB_CONNECTION=' .
                ($dbConnection ?: 'undefined') . '.'
            );
        }

        $dbDatabase = $testingEnv['DB_DATABASE'] ?? null;
        $allowedSqliteTargets = [
            ':memory:',
            '/var/www/html/database/database.sqlite',
            $basePath . '/database/database.sqlite',
        ];

        if (!in_array($dbDatabase, $allowedSqliteTargets, true)) {
            throw new RuntimeException(
                'Refusing to run tests because DB_DATABASE does not match the approved sqlite test targets. ' .
                'Current DB_DATABASE=' . ($dbDatabase ?: 'undefined') . '.'
            );
        }
    }

    private function readEnvironmentFile(string $path): array
    {
        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $values;
    }

}
