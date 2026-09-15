<?php

namespace Tests\Feature\Database;

use App\Models\TestRun;
use App\Models\WorkflowProfile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowExecutionGuardMigrationTest extends TestCase
{
    public function test_execution_guard_migration_can_roll_back_and_reapply(): void
    {
        $this->assertGuardSchemaExists();
        $profile = WorkflowProfile::factory()->create(['display_order' => 17]);
        $run = TestRun::factory()->create([
            'workflow_profile_id' => $profile->id,
            'profile_display_order_snapshot' => null,
        ]);

        $migration = require database_path(
            'migrations/2026_09_15_120000_add_workflow_execution_guards.php'
        );

        $migration->down();

        $this->assertFalse(Schema::hasTable('workflow_profile_dependencies'));
        $this->assertFalse(Schema::hasColumn('workflow_profiles', 'repeat_policy'));
        $this->assertFalse(Schema::hasColumn('workflow_runs', 'prerequisite_snapshot'));
        $this->assertFalse(Schema::hasColumn('workflow_runs', 'profile_display_order_snapshot'));
        $this->assertFalse(Schema::hasColumn('workflow_runs', 'guard_override_by'));

        $migration->up();

        $this->assertGuardSchemaExists();
        $this->assertSame(17, $run->fresh()->profile_display_order_snapshot);
    }

    public function test_execution_level_and_status_guard_audit_migration_can_roll_back_and_reapply(): void
    {
        $this->assertTrue(Schema::hasColumn('workflow_profiles', 'execution_level'));
        $this->assertTrue(Schema::hasColumns('asset_status_events', [
            'guard_confirmation_hash',
            'guard_override_reason',
            'guard_override_details',
        ]));

        $migration = require database_path(
            'migrations/2026_09_15_130000_add_workflow_execution_levels_and_status_guard_audits.php'
        );
        $migration->down();

        $this->assertFalse(Schema::hasColumn('workflow_profiles', 'execution_level'));
        $this->assertFalse(Schema::hasColumn('asset_status_events', 'guard_confirmation_hash'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('workflow_profiles', 'execution_level'));
        $this->assertTrue(Schema::hasColumn('asset_status_events', 'guard_override_details'));
    }

    private function assertGuardSchemaExists(): void
    {
        $this->assertTrue(Schema::hasTable('workflow_profile_dependencies'));
        $this->assertTrue(Schema::hasColumns('workflow_profiles', [
            'repeat_policy',
        ]));
        $this->assertTrue(Schema::hasColumns('workflow_runs', [
            'prerequisite_snapshot',
            'profile_display_order_snapshot',
            'guard_override_by',
            'guard_override_reason',
            'guard_override_at',
            'guard_override_details',
        ]));
    }
}
