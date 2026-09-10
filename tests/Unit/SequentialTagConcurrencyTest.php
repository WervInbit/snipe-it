<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SequentialTagConcurrencyTest extends TestCase
{
    public function testParallelProcessesReserveDistinctSequentialTags(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'snipeit-tag-concurrency-');
        try {
            $this->exerciseWorkers(['driver' => 'sqlite', 'database' => $database, 'busy_timeout' => 10000]);
        } finally {
            unlink($database);
        }
    }

    public function testParallelMariaDbProcessesReserveDistinctSequentialTags(): void
    {
        if (getenv('DB_CONNECTION') !== 'mysql') {
            $this->markTestSkipped('MariaDB concurrency runs in the isolated external database gate.');
        }
        $this->assertSame('1', getenv('SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE'));
        $this->assertSame('snipeit_test', getenv('DB_DATABASE'));
        $this->exerciseWorkers([
            'driver' => 'mysql', 'host' => getenv('DB_HOST'), 'database' => 'snipeit_test',
            'username' => getenv('DB_USERNAME'), 'password' => getenv('DB_PASSWORD'),
            'prefix' => 'tag_concurrency_' . bin2hex(random_bytes(4)) . '_',
        ]);
    }

    private function exerciseWorkers(array $config): void
    {
        $processes = [];
        $prefix = $config['prefix'] ?? '';
        $pdo = $config['driver'] === 'sqlite' ? new PDO('sqlite:' . $config['database'])
            : new PDO('mysql:host=' . $config['host'] . ';dbname=snipeit_test', $config['username'], $config['password']);
        try {
            $pdo->exec("CREATE TABLE {$prefix}identifier_sequences (prefix VARCHAR(16) PRIMARY KEY, next_value INTEGER NOT NULL)");
            $pdo->exec("INSERT INTO {$prefix}identifier_sequences VALUES ('INBIT-', 1)");
            $pdo->exec("CREATE TABLE {$prefix}assets (asset_tag VARCHAR(255))");
            $pdo->exec("CREATE TABLE {$prefix}component_instances (component_tag VARCHAR(255))");

            // Exercise the actual allocator in independent processes against one disposable database.
            $worker = <<<'PHP'
require $argv[1];
$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection(json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR));
$container = $capsule->getContainer();
$container->instance('db', $capsule->getDatabaseManager());
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$generator = new App\Services\SequentialTagGenerator();
$tags = [];
for ($i = 0; $i < 25; $i++) {
    $tags[] = $generator->generate('INBIT-');
}
echo json_encode($tags, JSON_THROW_ON_ERROR);
PHP;

            for ($i = 0; $i < 4; $i++) {
                $process = new Process([
                    PHP_BINARY, '-r', $worker, dirname(__DIR__, 2) . '/vendor/autoload.php', json_encode($config, JSON_THROW_ON_ERROR),
                ]);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }

            $tags = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $tags = array_merge($tags, json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR));
            }

            sort($tags);
            $this->assertCount(100, array_unique($tags));
            $this->assertSame('INBIT-AA0001', $tags[0]);
            $this->assertSame('INBIT-AA0100', $tags[99]);
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
            foreach (['identifier_sequences', 'assets', 'component_instances'] as $table) {
                $pdo->exec("DROP TABLE IF EXISTS {$prefix}{$table}");
            }
        }
    }

    public function testConcurrentManualWritesCannotBypassDuplicateConfirmation(): void
    {
        if (getenv('DB_CONNECTION') !== 'mysql') {
            $this->markTestSkipped('Manual-write race requires the migrated disposable MariaDB gate.');
        }
        $this->assertSame('1', getenv('SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE'));
        $this->assertSame('snipeit_test', getenv('DB_DATABASE'));
        $pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=snipeit_test', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
        $tag = 'RACE-' . strtoupper(bin2hex(random_bytes(8)));
        $processes = [];
        $worker = <<<'PHP'
require $argv[1] . '/vendor/autoload.php';
$app = require $argv[1] . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
while (microtime(true) < (float) $argv[3]) { usleep(10000); }
$asset = new App\Models\Asset(['asset_tag' => $argv[2]]);
echo json_encode(['saved' => $asset->save(), 'errors' => $asset->getErrors()->toArray()], JSON_THROW_ON_ERROR);
PHP;
        try {
            $start = (string) (microtime(true) + 3);
            for ($i = 0; $i < 4; $i++) {
                $process = new Process([PHP_BINARY, '-r', $worker, dirname(__DIR__, 2), $tag, $start]);
                $process->setTimeout(45);
                $process->start();
                $processes[] = $process;
            }
            $saved = 0;
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                if ($result['saved']) {
                    $saved++;
                } else {
                    $this->assertArrayHasKey('asset_tag', $result['errors']);
                }
            }
            $this->assertSame(1, $saved);
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
            $logs = $pdo->prepare('DELETE FROM action_logs WHERE item_type = ? AND item_id IN (SELECT id FROM assets WHERE asset_tag = ?)');
            $logs->execute([\App\Models\Asset::class, $tag]);
            $assets = $pdo->prepare('DELETE FROM assets WHERE asset_tag = ?');
            $assets->execute([$tag]);
        }
    }
}
