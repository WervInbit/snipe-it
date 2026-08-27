<?php

use Database\Migrations\Support\WorkflowMigrationUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/support/WorkflowMigrationUpgrade.php';

return new class extends Migration
{
    private const CUTOVER_PHASE = 'asset_image_fk_cutover_verified';

    public function up(): void
    {
        $upgrade = new WorkflowMigrationUpgrade();
        $upgrade->ensureCheckpointTable();
        $upgrade->assertWorkflowSchema();
        $legacyState = $upgrade->checkpointValue(WorkflowMigrationUpgrade::LEGACY_COPY_PHASE);

        if (!in_array($legacyState, ['present', 'absent'], true)) {
            throw new RuntimeException(
                'Workflow cutover refused because the data-copy checkpoint is missing or invalid.'
            );
        }

        if ($legacyState === 'present') {
            $upgrade->assertLegacyToWorkflowParity();
        }

        if ($upgrade->checkpointValue(WorkflowMigrationUpgrade::CUTOVER_PREVIOUS_TARGET_PHASE) === null) {
            $upgrade->putCheckpoint(
                WorkflowMigrationUpgrade::CUTOVER_PREVIOUS_TARGET_PHASE,
                $upgrade->currentAssetImagePhotoForeignKeyTarget()
            );
        }

        $this->withForeignKeysDisabled(function () use ($upgrade): void {
            $upgrade->retargetAssetImagePhotoForeignKey('workflow_result_photos');
        });

        $upgrade->putCheckpoint(self::CUTOVER_PHASE, 'workflow_result_photos');
    }

    public function down(): void
    {
        $upgrade = new WorkflowMigrationUpgrade();
        $previousTarget = $upgrade->checkpointValue(
            WorkflowMigrationUpgrade::CUTOVER_PREVIOUS_TARGET_PHASE
        );

        if ($previousTarget === null) {
            throw new RuntimeException(
                'Workflow cutover rollback refused because the previous foreign-key target is unknown.'
            );
        }

        $this->withForeignKeysDisabled(function () use ($upgrade, $previousTarget): void {
            $upgrade->restoreAssetImagePhotoForeignKey($previousTarget);
        });

        $upgrade->deleteCheckpoint(self::CUTOVER_PHASE);
        $upgrade->deleteCheckpoint(WorkflowMigrationUpgrade::CUTOVER_PREVIOUS_TARGET_PHASE);
    }

    private function withForeignKeysDisabled(callable $callback): void
    {
        $foreignKeysDisabled = false;

        try {
            Schema::disableForeignKeyConstraints();
            $foreignKeysDisabled = true;
            $callback();
        } finally {
            if ($foreignKeysDisabled) {
                Schema::enableForeignKeyConstraints();
            }
        }
    }
};
