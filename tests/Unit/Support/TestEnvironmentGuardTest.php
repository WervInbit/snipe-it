<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestEnvironmentGuard;

class TestEnvironmentGuardTest extends TestCase
{
    /**
     * @var array<string,array{process:string|false,env_exists:bool,env:mixed,server_exists:bool,server:mixed}>
     */
    private array $originalEnvironment = [];

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__ . '/../../..');

        foreach ([
            'SNIPEIT_PHPUNIT_GUARD',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE',
            'SNIPEIT_DUSK_GUARD',
            'APP_ENV',
            'APP_CONFIG_CACHE',
            'DB_CONNECTION',
            'DB_DATABASE',
        ] as $key) {
            $this->originalEnvironment[$key] = [
                'process' => getenv($key),
                'env_exists' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
            ];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $key => $original) {
            if ($original['process'] === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $original['process']);
            }

            if ($original['env_exists']) {
                $_ENV[$key] = $original['env'];
            } else {
                unset($_ENV[$key]);
            }

            if ($original['server_exists']) {
                $_SERVER[$key] = $original['server'];
            } else {
                unset($_SERVER[$key]);
            }
        }

        parent::tearDown();
    }

    public function test_phpunit_bootstrap_forces_the_guard_and_testing_environment(): void
    {
        $this->assertSame('1', getenv('SNIPEIT_PHPUNIT_GUARD'));
        $this->assertSame('testing', getenv('APP_ENV'));

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_guard_rejects_the_inherited_local_mysql_environment(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'local',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'snipeit_prod_work',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('effective process APP_ENV is not testing');

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_guard_rejects_mysql_without_explicit_external_database_approval(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'snipeit_test',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '0',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unapproved database');

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_guard_rejects_a_missing_phpunit_marker(): void
    {
        $this->unsetRuntimeEnvironment('SNIPEIT_PHPUNIT_GUARD');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('without the SNIPEIT_PHPUNIT_GUARD marker');

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_guard_rejects_external_database_with_a_non_test_name(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'snipeit_prod_work',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '1',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unapproved database');

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_guard_accepts_explicitly_approved_external_test_database(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'snipeit_test',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '1',
        ]);

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);

        $this->addToAssertionCount(1);
    }

    public function test_guard_accepts_explicitly_approved_postgres_test_database(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'snipeit_test',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '1',
        ]);

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);

        $this->addToAssertionCount(1);
    }

    public function test_guard_accepts_the_in_memory_sqlite_environment(): void
    {
        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '0',
        ]);

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);

        $this->addToAssertionCount(1);
    }

    public function test_guard_rejects_every_file_backed_or_nonstandard_sqlite_target(): void
    {
        $targets = [
            'conventional absolute file' => $this->basePath . '/database/database.sqlite',
            'conventional relative file' => 'database/database.sqlite',
            'canonical traversal spelling' => $this->basePath . '/database/../database/database.sqlite',
            'dedicated Dusk file' => $this->basePath . '/database/dusk.sqlite',
            'arbitrary PHPUnit file' => $this->basePath . '/database/phpunit.sqlite',
            'sqlite file URI' => 'file:' . $this->basePath . '/database/database.sqlite',
            'shared memory URI' => 'file::memory:?cache=shared',
            'memory target with parameters' => ':memory:?cache=shared',
        ];

        foreach ($targets as $description => $database) {
            $this->setRuntimeEnvironment([
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
                'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '0',
            ]);

            try {
                TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
                $this->fail("The guard accepted the {$description} target: {$database}");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('allows only in-memory sqlite', $exception->getMessage());
            }
        }
    }

    public function test_guard_rejects_a_symbolic_link_to_a_persistent_sqlite_database(): void
    {
        $temporaryDirectory = sys_get_temp_dir() . '/snipeit-phpunit-guard-' . bin2hex(random_bytes(6));
        $link = $temporaryDirectory . '/database.sqlite';

        mkdir($temporaryDirectory, 0775, true);

        if (!@symlink($this->basePath . '/database/database.sqlite', $link)) {
            rmdir($temporaryDirectory);
            $this->markTestSkipped('Symbolic links are not available in this test environment.');
        }

        $this->setRuntimeEnvironment([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $link,
            'SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE' => '0',
        ]);

        try {
            TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
            $this->fail('The guard accepted a symbolic link to a persistent sqlite database.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('allows only in-memory sqlite', $exception->getMessage());
        } finally {
            unlink($link);
            rmdir($temporaryDirectory);
        }
    }

    public function test_booted_application_guard_rejects_a_canonical_persistent_sqlite_path(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'snipeit-phpunit-guard-');
        $database = realpath($temporaryFile);
        $config = new \Illuminate\Config\Repository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => $database,
                    ],
                ],
            ],
        ]);
        $app = new class ($this->basePath, $config) implements \ArrayAccess {
            public function __construct(
                private readonly string $basePath,
                private readonly \Illuminate\Config\Repository $config
            ) {
            }

            public function environment(...$environments): string|bool
            {
                return $environments === [] ? 'testing' : in_array('testing', $environments, true);
            }

            public function basePath(): string
            {
                return $this->basePath;
            }

            public function offsetExists(mixed $offset): bool
            {
                return $offset === 'config';
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->config;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                throw new \LogicException('The fake application is read-only.');
            }

            public function offsetUnset(mixed $offset): void
            {
                throw new \LogicException('The fake application is read-only.');
            }
        };

        try {
            TestEnvironmentGuard::assertBootedPhpUnitApplication($app);
            $this->fail('The booted application guard accepted a canonical persistent sqlite path.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('allows only in-memory sqlite', $exception->getMessage());
        } finally {
            unlink($temporaryFile);
        }
    }

    public function test_prepare_synchronizes_conflicting_environment_representations(): void
    {
        putenv('APP_ENV=local');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_DATABASE'] = 'snipeit_prod_work';
        $_SERVER['APP_ENV'] = 'production';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_DATABASE'] = 'snipeit_prod_work';

        TestEnvironmentGuard::preparePhpUnitProcess($this->basePath);

        foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $expected) {
            $this->assertSame($expected, getenv($key));
            $this->assertSame($expected, $_ENV[$key]);
            $this->assertSame($expected, $_SERVER[$key]);
        }
    }

    public function test_guard_rejects_a_configuration_cache_before_laravel_boots(): void
    {
        $this->setRuntimeEnvironment([
            'APP_CONFIG_CACHE' => $this->basePath . '/phpunit.xml',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration is cached');

        TestEnvironmentGuard::assertPhpUnitProcessIsSafe($this->basePath);
    }

    public function test_dusk_guard_accepts_only_the_dedicated_sqlite_file(): void
    {
        $this->setRuntimeEnvironment([
            'SNIPEIT_DUSK_GUARD' => '1',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->basePath . '/database/dusk.sqlite',
        ]);

        TestEnvironmentGuard::prepareDuskProcess($this->basePath);

        $this->assertSame('testing', $_SERVER['APP_ENV']);
        $this->assertSame('sqlite', $_SERVER['DB_CONNECTION']);
        $this->assertSame(
            str_replace('\\', '/', $this->basePath . '/database/dusk.sqlite'),
            $_SERVER['DB_DATABASE']
        );
    }

    public function test_dusk_guard_creates_the_dedicated_sqlite_file_for_a_clean_base_path(): void
    {
        $cleanBasePath = sys_get_temp_dir() . '/snipeit-dusk-guard-' . bin2hex(random_bytes(6));
        $database = $cleanBasePath . '/database/dusk.sqlite';
        mkdir($cleanBasePath, 0775, true);
        $this->setRuntimeEnvironment([
            'SNIPEIT_DUSK_GUARD' => '1',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'database/dusk.sqlite',
        ]);

        try {
            TestEnvironmentGuard::prepareDuskProcess($cleanBasePath);

            $this->assertFileExists($database);
            $this->assertSame(0, filesize($database));
            $this->assertSame(str_replace('\\', '/', $database), $_SERVER['DB_DATABASE']);

            $connection = new \PDO('sqlite:' . $_SERVER['DB_DATABASE']);
            $this->assertSame([], $connection->query(
                "SELECT name FROM sqlite_master WHERE type = 'table'"
            )->fetchAll(\PDO::FETCH_COLUMN));
        } finally {
            if (is_file($database)) {
                unlink($database);
            }
            if (is_dir($cleanBasePath . '/database')) {
                rmdir($cleanBasePath . '/database');
            }
            if (is_dir($cleanBasePath)) {
                rmdir($cleanBasePath);
            }
        }
    }

    public function test_dusk_guard_rejects_a_linked_database_directory(): void
    {
        $cleanBasePath = sys_get_temp_dir() . '/snipeit-dusk-guard-' . bin2hex(random_bytes(6));
        $linkedDirectory = sys_get_temp_dir() . '/snipeit-dusk-target-' . bin2hex(random_bytes(6));
        mkdir($cleanBasePath, 0775, true);
        mkdir($linkedDirectory, 0775, true);

        if (!@symlink($linkedDirectory, $cleanBasePath . '/database')) {
            rmdir($linkedDirectory);
            rmdir($cleanBasePath);
            $this->markTestSkipped('Symbolic links are not available in this test environment.');
        }

        $this->setRuntimeEnvironment([
            'SNIPEIT_DUSK_GUARD' => '1',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'database/dusk.sqlite',
        ]);

        try {
            TestEnvironmentGuard::prepareDuskProcess($cleanBasePath);
            $this->fail('The Dusk guard accepted a linked database directory.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('database directory is a symbolic link', $exception->getMessage());
        } finally {
            unlink($cleanBasePath . '/database');
            rmdir($linkedDirectory);
            rmdir($cleanBasePath);
        }
    }

    public function test_dusk_guard_rejects_mysql_before_migrate_fresh_can_run(): void
    {
        $this->setRuntimeEnvironment([
            'SNIPEIT_DUSK_GUARD' => '1',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'snipeit_prod_work',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not targeting the dedicated sqlite database');

        TestEnvironmentGuard::prepareDuskProcess($this->basePath);
    }

    /**
     * @param array<string,string> $values
     */
    private function setRuntimeEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function unsetRuntimeEnvironment(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
