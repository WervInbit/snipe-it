<?php

namespace Tests;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\Support\AssertsAgainstSlackNotifications;
use Tests\Support\AssertHasActionLogs;
use Tests\Support\CustomTestMacros;
use Tests\Support\InteractsWithAuthentication;
use Tests\Support\InitializesSettings;
use Tests\Support\TestEnvironmentGuard;

abstract class TestCase extends BaseTestCase
{
    use AssertsAgainstSlackNotifications;
    use CreatesApplication;
    use CustomTestMacros;
    use InteractsWithAuthentication;
    use InitializesSettings;
    use LazilyRefreshDatabase {
        beginDatabaseTransaction as private beginFrameworkDatabaseTransaction;
    }
    use AssertHasActionLogs;

    private array $globallyDisabledMiddleware = [
        SecurityHeaders::class,
    ];

    protected function setUp(): void
    {
        $this->guardAgainstMissingEnv();
        $this->guardAgainstUnsafeTestingConfig();

        parent::setUp();

        Cache::flush();
        $this->app->setLocale(config('app.locale'));

        $this->registerCustomMacros();

        $this->withoutMiddleware($this->globallyDisabledMiddleware);

        $this->initializeSettings();
    }

    /**
     * Keep the guarded disposable MariaDB suite recoverable when application
     * behavior intentionally alters the assets table for custom fields.
     *
     * MariaDB implicitly commits transactions around DDL. Laravel's default
     * teardown then attempts to roll back a transaction that no longer exists.
     * Detect that state, require a fresh schema before the next test, and avoid
     * disguising executable database coverage as incomplete tests.
     */
    public function beginDatabaseTransaction()
    {
        if (config('database.default') !== 'mysql') {
            $this->beginFrameworkDatabaseTransaction();

            return;
        }

        $database = $this->app->make('db');
        $connections = $this->connectionsToTransact();

        $this->app->instance(
            'db.transactions',
            $transactionsManager = new DatabaseTransactionsManager($connections)
        );

        foreach ($connections as $name) {
            $connection = $database->connection($name);
            $connection->setTransactionManager($transactionsManager);

            $dispatcher = $connection->getEventDispatcher();
            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();

                $pdo = $connection->getPdo();
                if ($pdo && $pdo->inTransaction()) {
                    $connection->rollBack();
                } else {
                    RefreshDatabaseState::$migrated = false;
                }

                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
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
        TestEnvironmentGuard::assertPhpUnitProcessIsSafe(realpath(__DIR__ . '/../'));
    }

    private function guardAgainstUnsafeBootedApplication($app): void
    {
        TestEnvironmentGuard::assertBootedPhpUnitApplication($app);
    }
}
