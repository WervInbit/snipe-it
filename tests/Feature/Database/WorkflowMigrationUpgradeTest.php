<?php

namespace Tests\Feature\Database;

use Database\Migrations\Support\WorkflowMigrationUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class WorkflowMigrationUpgradeTest extends TestCase
{
    private const CONNECTION = 'workflow_migration_upgrade_test';

    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        require_once database_path('migrations/support/WorkflowMigrationUpgrade.php');

        $this->originalDefaultConnection = config('database.default');
        config()->set('database.connections.' . self::CONNECTION, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('database.default', self::CONNECTION);
        DB::purge(self::CONNECTION);
        DB::connection(self::CONNECTION)->statement('PRAGMA foreign_keys = ON');

        $this->createPrerequisiteSchema();
        (new WorkflowMigrationUpgrade())->createLegacyTestTables();
        $this->createAssetImagesTable();
    }

    protected function tearDown(): void
    {
        config()->set('database.default', $this->originalDefaultConnection);
        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_clean_install_runs_in_explicit_phases_and_keeps_legacy_tables(): void
    {
        $this->schemaMigration()->up();

        $this->assertTrue(Schema::hasTable('workflow_items'));
        $this->assertSame(0, DB::table('workflow_profiles')->count());
        $this->assertTrue(Schema::hasTable('test_types'));

        $this->copyMigration()->up();

        $this->assertDatabaseHas('workflow_profiles', [
            'slug' => 'standard-diagnostics',
            'is_default' => true,
        ], self::CONNECTION);
        $this->assertDatabaseHas('workflow_migration_checkpoints', [
            'phase' => WorkflowMigrationUpgrade::LEGACY_COPY_PHASE,
            'value' => 'present',
        ], self::CONNECTION);

        $this->cutoverMigration()->up();

        $this->assertSame('workflow_result_photos', $this->sourcePhotoForeignKeyTarget());
        $this->assertForeignKeysEnabled();
        $this->assertTrue(Schema::hasTable('test_result_photos'));
    }

    public function test_populated_legacy_data_is_copied_with_ids_and_mappings_preserved(): void
    {
        $this->seedLegacyRows();
        $this->schemaMigration()->up();
        $this->copyMigration()->up();
        $this->cutoverMigration()->up();

        $this->assertDatabaseHas('workflow_items', [
            'id' => 101,
            'name' => 'Display',
            'slug' => 'display',
        ], self::CONNECTION);
        $this->assertDatabaseHas('category_workflow_item', [
            'workflow_item_id' => 101,
            'category_id' => 31,
        ], self::CONNECTION);
        $this->assertDatabaseHas('workflow_runs', [
            'id' => 201,
            'asset_id' => 41,
            'model_number_id' => 51,
            'profile_slug_snapshot' => 'standard-diagnostics',
        ], self::CONNECTION);
        $this->assertDatabaseHas('workflow_results', [
            'id' => 301,
            'workflow_run_id' => 201,
            'workflow_item_id' => 101,
            'status' => 'pass',
        ], self::CONNECTION);
        $this->assertDatabaseHas('workflow_result_photos', [
            'id' => 401,
            'workflow_result_id' => 301,
            'path' => 'legacy/photo.jpg',
        ], self::CONNECTION);
        $this->assertDatabaseHas('workflow_audits', [
            'id' => 501,
            'auditable_id' => 301,
            'field' => 'status',
        ], self::CONNECTION);
        $this->assertDatabaseCount('test_results', 1, self::CONNECTION);
        $this->assertDatabaseCount('workflow_results', 1, self::CONNECTION);
        $this->assertSame('workflow_result_photos', $this->sourcePhotoForeignKeyTarget());
    }

    public function test_retry_reconciles_a_nonempty_partial_destination_without_duplicate_rows(): void
    {
        $this->seedLegacyRows();
        $this->seedSecondLegacyRun();
        $this->schemaMigration()->up();
        $copy = $this->copyMigration();
        $copy->up();

        DB::table('workflow_result_photos')->where('id', 402)->delete();
        DB::table('workflow_results')->where('id', 302)->delete();
        DB::table('workflow_runs')->where('id', 202)->delete();
        DB::table('workflow_migration_checkpoints')
            ->where('phase', WorkflowMigrationUpgrade::LEGACY_COPY_PHASE)
            ->delete();

        $copy->up();

        $this->assertDatabaseHas('workflow_runs', ['id' => 202], self::CONNECTION);
        $this->assertDatabaseHas('workflow_results', ['id' => 302], self::CONNECTION);
        $this->assertDatabaseHas('workflow_result_photos', ['id' => 402], self::CONNECTION);
        $this->assertDatabaseCount('workflow_runs', 2, self::CONNECTION);
        $this->assertDatabaseCount('workflow_results', 2, self::CONNECTION);
        $this->assertDatabaseCount('workflow_result_photos', 2, self::CONNECTION);
    }

    public function test_retry_fails_closed_when_an_existing_destination_mapping_differs(): void
    {
        $this->seedLegacyRows();
        $this->schemaMigration()->up();
        $copy = $this->copyMigration();
        $copy->up();

        DB::table('workflow_items')->where('id', 101)->update(['name' => 'Mismatched']);
        DB::table('workflow_migration_checkpoints')
            ->where('phase', WorkflowMigrationUpgrade::LEGACY_COPY_PHASE)
            ->delete();

        try {
            $copy->up();
            $this->fail('A mismatched destination row was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('mapping mismatch', $exception->getMessage());
        }

        $this->assertDatabaseHas('test_types', ['id' => 101, 'name' => 'Display'], self::CONNECTION);
        $this->assertDatabaseHas('workflow_items', ['id' => 101, 'name' => 'Mismatched'], self::CONNECTION);
        $this->assertDatabaseMissing('workflow_migration_checkpoints', [
            'phase' => WorkflowMigrationUpgrade::LEGACY_COPY_PHASE,
        ], self::CONNECTION);
        $this->assertForeignKeysEnabled();
    }

    public function test_rollback_restores_verified_legacy_rows_and_the_original_foreign_key(): void
    {
        $this->seedLegacyRows();
        $schema = $this->schemaMigration();
        $copy = $this->copyMigration();
        $cutover = $this->cutoverMigration();

        $schema->up();
        $copy->up();
        $cutover->up();
        DB::table('workflow_items')->where('id', 101)->update(['name' => 'Display Retested']);

        $cutover->down();
        $copy->down();
        $schema->down();

        $this->assertFalse(Schema::hasTable('workflow_items'));
        $this->assertFalse(Schema::hasTable('workflow_migration_checkpoints'));
        $this->assertDatabaseHas('test_types', [
            'id' => 101,
            'name' => 'Display Retested',
        ], self::CONNECTION);
        $this->assertDatabaseHas('test_results', [
            'id' => 301,
            'test_run_id' => 201,
            'test_type_id' => 101,
        ], self::CONNECTION);
        $this->assertSame('test_result_photos', $this->sourcePhotoForeignKeyTarget());
        $this->assertForeignKeysEnabled();
    }

    public function test_schema_phase_is_retryable_after_partial_ddl_completion(): void
    {
        $schema = $this->schemaMigration();
        $schema->up();

        Schema::drop('workflow_audits');
        $schema->up();

        $this->assertTrue(Schema::hasTable('workflow_audits'));
        $this->assertTrue(Schema::hasTable('workflow_migration_checkpoints'));
    }

    public function test_already_cutover_install_without_legacy_tables_is_not_given_false_parity(): void
    {
        $this->seedLegacyRows();
        $this->schemaMigration()->up();
        $this->copyMigration()->up();
        $this->cutoverMigration()->up();

        foreach (
            [
            'category_test_type',
            'test_result_photos',
            'test_results',
            'test_runs',
            'test_types',
            'test_audits',
            ] as $table
        ) {
            Schema::drop($table);
        }
        DB::table('workflow_migration_checkpoints')->delete();

        $this->copyMigration()->up();
        $this->cutoverMigration()->up();

        $this->assertDatabaseHas('workflow_migration_checkpoints', [
            'phase' => WorkflowMigrationUpgrade::LEGACY_COPY_PHASE,
            'value' => 'absent',
        ], self::CONNECTION);
        $this->assertFalse(Schema::hasTable('test_types'));
        $this->assertDatabaseHas('workflow_results', ['id' => 301], self::CONNECTION);
        $this->assertSame('workflow_result_photos', $this->sourcePhotoForeignKeyTarget());
    }

    public function test_cutover_restores_foreign_key_enforcement_when_retargeting_throws(): void
    {
        $migration = $this->cutoverMigration();
        $method = new ReflectionMethod($migration, 'withForeignKeysDisabled');
        $method->setAccessible(true);

        try {
            $method->invoke($migration, function (): void {
                throw new RuntimeException('simulated retarget failure');
            });
            $this->fail('The simulated retarget failure did not escape the cutover.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated retarget failure', $exception->getMessage());
        }

        $this->assertForeignKeysEnabled();
    }

    private function createPrerequisiteSchema(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::create('assets', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::create('model_numbers', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
        });

        DB::table('attribute_definitions')->insert(['id' => 71]);
        DB::table('categories')->insert(['id' => 31]);
        DB::table('assets')->insert(['id' => 41]);
        DB::table('model_numbers')->insert(['id' => 51]);
        DB::table('users')->insert(['id' => 61]);
    }

    private function createAssetImagesTable(): void
    {
        Schema::create('asset_images', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('source_photo_id')->nullable();
            $table->foreign('source_photo_id')
                ->references('id')
                ->on('test_result_photos')
                ->nullOnDelete();
        });
    }

    private function seedLegacyRows(): void
    {
        $timestamp = '2026-05-20 10:00:00';

        DB::table('test_types')->insert([
            'id' => 101,
            'attribute_definition_id' => 71,
            'name' => 'Display',
            'slug' => 'display',
            'display_order' => 3,
            'tooltip' => 'Check display',
            'instructions' => 'Inspect the panel.',
            'category' => 'laptop',
            'is_required' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('category_test_type')->insert([
            'test_type_id' => 101,
            'category_id' => 31,
        ]);
        DB::table('test_runs')->insert([
            'id' => 201,
            'asset_id' => 41,
            'model_number_id' => 51,
            'user_id' => 61,
            'started_at' => $timestamp,
            'finished_at' => '2026-05-20 10:05:00',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('test_results')->insert([
            'id' => 301,
            'test_run_id' => 201,
            'test_type_id' => 101,
            'attribute_definition_id' => 71,
            'status' => 'pass',
            'expected_value' => 'working',
            'expected_raw_value' => '1',
            'note' => 'No defects',
            'photo_path' => 'legacy/photo.jpg',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('test_result_photos')->insert([
            'id' => 401,
            'test_result_id' => 301,
            'path' => 'legacy/photo.jpg',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('test_audits')->insert([
            'id' => 501,
            'auditable_type' => 'App\\Models\\TestResult',
            'auditable_id' => 301,
            'user_id' => 61,
            'created_by' => 61,
            'field' => 'status',
            'before' => 'fail',
            'after' => 'pass',
            'created_at' => $timestamp,
        ]);
        DB::table('asset_images')->insert([
            'id' => 601,
            'source_photo_id' => 401,
        ]);
    }

    private function seedSecondLegacyRun(): void
    {
        $timestamp = '2026-05-21 10:00:00';

        DB::table('test_runs')->insert([
            'id' => 202,
            'asset_id' => 41,
            'model_number_id' => 51,
            'user_id' => 61,
            'started_at' => $timestamp,
            'finished_at' => '2026-05-21 10:05:00',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('test_results')->insert([
            'id' => 302,
            'test_run_id' => 202,
            'test_type_id' => 101,
            'attribute_definition_id' => 71,
            'status' => 'fail',
            'expected_value' => 'working',
            'expected_raw_value' => '1',
            'note' => 'Dead pixels',
            'photo_path' => 'legacy/photo-2.jpg',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('test_result_photos')->insert([
            'id' => 402,
            'test_result_id' => 302,
            'path' => 'legacy/photo-2.jpg',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function schemaMigration(): Migration
    {
        return require database_path(
            'migrations/2026_05_26_120000_rename_tests_to_workflows_and_add_profiles.php'
        );
    }

    private function copyMigration(): Migration
    {
        return require database_path(
            'migrations/2026_05_26_120010_copy_legacy_tests_to_workflows.php'
        );
    }

    private function cutoverMigration(): Migration
    {
        return require database_path(
            'migrations/2026_05_26_120020_cutover_workflow_photo_foreign_key.php'
        );
    }

    private function sourcePhotoForeignKeyTarget(): ?string
    {
        $row = collect(DB::select("PRAGMA foreign_key_list('asset_images')"))
            ->first(fn (object $foreignKey): bool => $foreignKey->from === 'source_photo_id');

        return $row?->table;
    }

    private function assertForeignKeysEnabled(): void
    {
        $this->assertSame(1, (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys);
    }
}
