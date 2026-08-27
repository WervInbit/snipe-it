<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FailedJobsSchemaTest extends TestCase
{
    public function test_database_failed_job_driver_has_its_required_schema(): void
    {
        $this->assertSame('database-uuids', config('queue.failed.driver'));
        $this->assertSame('failed_jobs', config('queue.failed.table'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasColumns('failed_jobs', [
            'id',
            'uuid',
            'connection',
            'queue',
            'payload',
            'exception',
            'failed_at',
        ]));
    }
}
