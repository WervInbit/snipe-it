<?php

namespace Tests\Support;

use RuntimeException;

final class TestEnvironmentGuard
{
    private const PHPUNIT_MARKER = 'SNIPEIT_PHPUNIT_GUARD';

    private const EXTERNAL_DATABASE_MARKER = 'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE';

    private const DUSK_MARKER = 'SNIPEIT_DUSK_GUARD';

    public static function preparePhpUnitProcess(string $basePath): void
    {
        if (self::environmentValue(self::PHPUNIT_MARKER) !== '1') {
            throw new RuntimeException('Refusing to bootstrap PHPUnit without the SNIPEIT_PHPUNIT_GUARD marker.');
        }

        self::synchronizeEnvironmentValue(self::PHPUNIT_MARKER, '1');
        self::assertConfigurationIsNotCached($basePath);
        self::synchronizeEnvironmentValue('APP_ENV', 'testing');
        self::assertPhpUnitDatabaseTarget($basePath);
    }

    public static function assertPhpUnitProcessIsSafe(string $basePath): void
    {
        if (self::environmentValue(self::PHPUNIT_MARKER) !== '1') {
            throw new RuntimeException('Refusing to run tests without the SNIPEIT_PHPUNIT_GUARD marker.');
        }

        self::assertConfigurationIsNotCached($basePath);

        if (self::environmentValue('APP_ENV') !== 'testing') {
            throw new RuntimeException(
                'Refusing to run tests because the effective process APP_ENV is not testing. Current APP_ENV=' .
                (self::environmentValue('APP_ENV') ?: 'undefined') . '.'
            );
        }

        self::assertPhpUnitDatabaseTarget($basePath);
    }

    public static function assertBootedPhpUnitApplication($app): void
    {
        $connection = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$connection}.driver");
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (!$app->environment('testing')) {
            throw new RuntimeException(
                'Refusing to initialize database-refreshing tests because the booted APP_ENV is not testing. ' .
                'Current APP_ENV=' . $app->environment() . '.'
            );
        }

        self::assertDatabaseTarget(
            connection: $connection,
            driver: $driver,
            database: $database,
            basePath: $app->basePath(),
            allowExternal: self::externalDatabaseIsExplicitlyAllowed()
        );
    }

    public static function prepareDuskProcess(string $basePath): void
    {
        if (self::environmentValue(self::DUSK_MARKER) !== '1') {
            throw new RuntimeException('Refusing to bootstrap Dusk without the SNIPEIT_DUSK_GUARD marker.');
        }

        self::synchronizeEnvironmentValue(self::DUSK_MARKER, '1');
        self::assertConfigurationIsNotCached($basePath);
        self::synchronizeEnvironmentValue('APP_ENV', 'testing');

        $connection = self::environmentValue('DB_CONNECTION');
        $configuredDatabase = self::environmentValue('DB_DATABASE');
        $database = self::resolveDuskSqliteTarget($basePath, $configuredDatabase);

        if ($connection !== 'sqlite' || $database === null) {
            throw new RuntimeException(
                'Refusing to run Dusk because it is not targeting the dedicated sqlite database. ' .
                'DB_CONNECTION=' . ($connection ?: 'undefined') .
                ', DB_DATABASE=' . ($configuredDatabase ?: 'undefined') . '.'
            );
        }

        self::ensureDuskSqliteDatabase($basePath, $database);
        self::synchronizeEnvironmentValue('DB_CONNECTION', $connection);
        self::synchronizeEnvironmentValue('DB_DATABASE', $database);
    }

    public static function assertBootedDuskApplication($app): void
    {
        $connection = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$connection}.driver");
        $database = $app['config']->get("database.connections.{$connection}.database");
        $resolvedDatabase = self::resolveDuskSqliteTarget($app->basePath(), $database);

        if (
            !$app->environment('testing')
            || $connection !== 'sqlite'
            || $driver !== 'sqlite'
            || $resolvedDatabase === null
        ) {
            throw new RuntimeException(
                'Refusing to initialize Dusk because the booted application is not using its dedicated sqlite ' .
                'database. APP_ENV=' . $app->environment() .
                ', DB_CONNECTION=' . ($connection ?: 'undefined') .
                ', DB_DRIVER=' . ($driver ?: 'undefined') .
                ', DB_DATABASE=' . ($database ?: 'undefined') . '.'
            );
        }

        self::assertDuskSqliteDatabaseIsSafe($app->basePath(), $resolvedDatabase);
    }

    private static function assertPhpUnitDatabaseTarget(string $basePath): void
    {
        $connection = self::environmentValue('DB_CONNECTION');
        $database = self::environmentValue('DB_DATABASE');

        self::assertDatabaseTarget(
            connection: $connection,
            driver: $connection,
            database: $database,
            basePath: $basePath,
            allowExternal: self::externalDatabaseIsExplicitlyAllowed()
        );

        self::synchronizeEnvironmentValue('DB_CONNECTION', $connection);
        self::synchronizeEnvironmentValue('DB_DATABASE', $database);
    }

    private static function assertDatabaseTarget(
        ?string $connection,
        ?string $driver,
        ?string $database,
        string $basePath,
        bool $allowExternal
    ): void {
        if ($connection === 'sqlite') {
            if ($driver !== 'sqlite' || $database !== ':memory:') {
                throw new RuntimeException(
                    'Refusing to run tests because local PHPUnit allows only in-memory sqlite. ' .
                    'DB_DRIVER=' . ($driver ?: 'undefined') .
                    ', DB_DATABASE=' . ($database ?: 'undefined') . '.'
                );
            }

            return;
        }

        if (
            $allowExternal
            && in_array($connection, ['mysql', 'pgsql'], true)
            && $driver === $connection
            && $database === 'snipeit_test'
        ) {
            return;
        }

        throw new RuntimeException(
            'Refusing to run database-refreshing tests against an unapproved database. ' .
            'DB_CONNECTION=' . ($connection ?: 'undefined') .
            ', DB_DRIVER=' . ($driver ?: 'undefined') .
            ', DB_DATABASE=' . ($database ?: 'undefined') .
            '. Use sqlite, or explicitly opt into the disposable snipeit_test CI database.'
        );
    }

    private static function externalDatabaseIsExplicitlyAllowed(): bool
    {
        return self::environmentValue(self::EXTERNAL_DATABASE_MARKER) === '1';
    }

    private static function assertConfigurationIsNotCached(string $basePath): void
    {
        $cachePaths = [$basePath . '/bootstrap/cache/config.php'];
        $customCachePath = self::environmentValue('APP_CONFIG_CACHE');

        if ($customCachePath) {
            $cachePaths[] = $customCachePath;
        }

        foreach (array_unique($cachePaths) as $cachePath) {
            if (is_file($cachePath)) {
                throw new RuntimeException(
                    'Refusing to run tests while Laravel configuration is cached at ' . $cachePath .
                    '. Run `php artisan optimize:clear --env=testing` first.'
                );
            }
        }
    }

    private static function resolveDuskSqliteTarget(string $basePath, ?string $database): ?string
    {
        $canonicalBasePath = realpath($basePath);

        if ($canonicalBasePath === false) {
            return null;
        }

        $expectedDatabase = self::normalizePath($canonicalBasePath . '/database/dusk.sqlite');
        $configuredDatabase = self::normalizePath($database);

        if (
            $configuredDatabase === 'database/dusk.sqlite'
            || self::pathsAreEqual($configuredDatabase, $expectedDatabase)
        ) {
            return $expectedDatabase;
        }

        return null;
    }

    private static function ensureDuskSqliteDatabase(string $basePath, string $database): void
    {
        $canonicalBasePath = realpath($basePath);

        if ($canonicalBasePath === false) {
            throw new RuntimeException('Unable to resolve the Dusk application base path at ' . $basePath . '.');
        }

        $directory = self::normalizePath($canonicalBasePath . '/database');

        if (is_link($directory)) {
            throw new RuntimeException('Refusing to run Dusk because its database directory is a symbolic link.');
        }

        if (!is_dir($directory) && !@mkdir($directory, 0775) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the Dusk sqlite database directory at ' . $directory . '.');
        }

        $canonicalDirectory = realpath($directory);
        if (
            $canonicalDirectory === false
            || !self::pathsAreEqual($canonicalDirectory, $directory)
            || !self::pathsAreEqual($database, $directory . '/dusk.sqlite')
        ) {
            throw new RuntimeException('Refusing to run Dusk because its sqlite database path is not canonical.');
        }

        if (is_link($database)) {
            throw new RuntimeException('Refusing to run Dusk because its dedicated sqlite database is a symbolic link.');
        }

        if (file_exists($database) && !is_file($database)) {
            throw new RuntimeException('The dedicated Dusk sqlite target is not a regular file: ' . $database . '.');
        }

        if (!file_exists($database)) {
            $handle = @fopen($database, 'x');

            if ($handle === false) {
                throw new RuntimeException('Unable to create the dedicated Dusk sqlite database at ' . $database . '.');
            }

            fclose($handle);
        }

        self::assertDuskSqliteDatabaseIsSafe($basePath, $database);
    }

    private static function assertDuskSqliteDatabaseIsSafe(string $basePath, string $database): void
    {
        $canonicalBasePath = realpath($basePath);
        $directory = dirname($database);

        if (
            $canonicalBasePath === false
            || is_link($directory)
            || is_link($database)
            || !is_file($database)
            || !self::pathsAreEqual(realpath($directory) ?: null, $canonicalBasePath . '/database')
            || !self::pathsAreEqual(realpath($database) ?: null, $database)
        ) {
            throw new RuntimeException(
                'Refusing to run Dusk because its dedicated sqlite database is missing, non-canonical, or linked.'
            );
        }
    }

    private static function environmentValue(string $key): ?string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        return trim((string) $value, " \t\n\r\0\x0B\"'");
    }

    private static function synchronizeEnvironmentValue(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private static function normalizePath(?string $path): ?string
    {
        return $path === null ? null : str_replace('\\', '/', $path);
    }

    private static function pathsAreEqual(?string $left, ?string $right): bool
    {
        $left = self::normalizePath($left);
        $right = self::normalizePath($right);

        if ($left === null || $right === null) {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return strtolower($left) === strtolower($right);
        }

        return $left === $right;
    }
}
