<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SystemBackup;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SystemBackupTest extends MockeryTestCase
{
    public function test_it_propagates_the_backup_runner_failure_code(): void
    {
        $command = Mockery::mock(SystemBackup::class)->makePartial();
        $command->shouldReceive('option')
            ->once()
            ->with('filename')
            ->andReturnNull();
        $command->shouldReceive('call')
            ->once()
            ->with('backup:run')
            ->andReturn(37);

        $this->assertSame(37, $command->handle());
    }

    public function test_it_normalizes_the_filename_and_propagates_the_runner_code(): void
    {
        $command = Mockery::mock(SystemBackup::class)->makePartial();
        $command->shouldReceive('option')
            ->twice()
            ->with('filename')
            ->andReturn('pre-restore');
        $command->shouldReceive('call')
            ->once()
            ->with('backup:run', ['--filename' => 'pre-restore.zip'])
            ->andReturn(12);

        $this->assertSame(12, $command->handle());
    }
}
