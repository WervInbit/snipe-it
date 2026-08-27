<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BackupFilesystemConfigurationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = dirname(__DIR__, 2);
    }

    public function test_backup_disk_uses_dedicated_driver_with_local_defaults(): void
    {
        $filesystems = file_get_contents($this->basePath.'/config/filesystems.php');

        $this->assertIsString($filesystems);
        $this->assertStringContainsString(
            "'driver' => env('BACKUP_FILESYSTEM_DRIVER', 'local')",
            $filesystems
        );
        $this->assertStringContainsString(
            "'root' => env('BACKUP_FILESYSTEM_ROOT', storage_path('app'))",
            $filesystems
        );
        $this->assertStringNotContainsString(
            "'driver' => env('PRIVATE_FILESYSTEM_DISK', 'local'),\n            'root' => storage_path('app')",
            str_replace("\r\n", "\n", $filesystems)
        );
    }

    public function test_backup_disk_has_private_s3_configuration(): void
    {
        $filesystems = file_get_contents($this->basePath.'/config/filesystems.php');

        $this->assertIsString($filesystems);

        foreach ([
            "'key' => env('PRIVATE_AWS_ACCESS_KEY_ID')",
            "'secret' => env('PRIVATE_AWS_SECRET_ACCESS_KEY')",
            "'region' => env('PRIVATE_AWS_DEFAULT_REGION')",
            "'bucket' => env('PRIVATE_AWS_BUCKET')",
            "'visibility' => 'private'",
        ] as $expectedConfiguration) {
            $this->assertStringContainsString($expectedConfiguration, $filesystems);
        }
    }

    public function test_example_environment_documents_backup_driver_and_root(): void
    {
        $environment = file_get_contents($this->basePath.'/.env.example');

        $this->assertIsString($environment);
        $this->assertStringContainsString('BACKUP_FILESYSTEM_DRIVER=local', $environment);
        $this->assertStringContainsString('#BACKUP_FILESYSTEM_ROOT=', $environment);
    }
}
