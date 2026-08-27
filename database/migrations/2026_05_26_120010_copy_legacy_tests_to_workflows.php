<?php

use Database\Migrations\Support\WorkflowMigrationUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/support/WorkflowMigrationUpgrade.php';

return new class extends Migration
{
    public function up(): void
    {
        $upgrade = new WorkflowMigrationUpgrade();
        $upgrade->ensureCheckpointTable();
        $upgrade->assertWorkflowSchema();
        $legacyState = $upgrade->legacySourceState();

        DB::transaction(function () use ($upgrade, $legacyState): void {
            if ($legacyState === 'present') {
                $upgrade->copyLegacyToWorkflows();
            }

            $upgrade->putCheckpoint(WorkflowMigrationUpgrade::LEGACY_COPY_PHASE, $legacyState);
        });
    }

    public function down(): void
    {
        $upgrade = new WorkflowMigrationUpgrade();
        $legacyState = $upgrade->checkpointValue(WorkflowMigrationUpgrade::LEGACY_COPY_PHASE);

        if ($legacyState === null) {
            throw new RuntimeException(
                'Workflow copy rollback refused because the verified source-state checkpoint is missing.'
            );
        }

        if ($legacyState === 'absent') {
            $upgrade->deleteCheckpoint(WorkflowMigrationUpgrade::LEGACY_COPY_PHASE);

            return;
        }

        if ($legacyState !== 'present') {
            throw new RuntimeException(
                "Workflow copy rollback found an invalid source-state checkpoint: {$legacyState}."
            );
        }

        $upgrade->createLegacyTestTables();

        DB::transaction(function () use ($upgrade): void {
            $upgrade->copyWorkflowsToLegacy();
            $upgrade->putCheckpoint(WorkflowMigrationUpgrade::ROLLBACK_COPY_PHASE, 'present');
            $upgrade->deleteCheckpoint(WorkflowMigrationUpgrade::LEGACY_COPY_PHASE);
        });
    }
};
