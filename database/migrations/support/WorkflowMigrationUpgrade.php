<?php

namespace Database\Migrations\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use stdClass;

final class WorkflowMigrationUpgrade
{
    public const LEGACY_COPY_PHASE = 'legacy_copy_verified';

    public const ROLLBACK_COPY_PHASE = 'rollback_copy_verified';

    public const CUTOVER_PREVIOUS_TARGET_PHASE = 'asset_image_fk_before_cutover';

    private const DEFAULT_PROFILE_SLUG = 'standard-diagnostics';

    private const DEFAULT_PROFILE_NAME = 'Standard Diagnostics';

    private const NO_FOREIGN_KEY = '__none__';

    /**
     * @var array<string,list<string>>
     */
    private const WORKFLOW_COLUMNS = [
        'workflow_profiles' => [
            'id', 'name', 'slug', 'description', 'is_active', 'is_default',
            'blocks_sale_readiness', 'display_order', 'created_at', 'updated_at',
        ],
        'workflow_items' => [
            'id', 'attribute_definition_id', 'name', 'slug', 'display_order',
            'tooltip', 'instructions', 'category', 'is_required', 'created_at', 'updated_at',
        ],
        'category_workflow_item' => ['workflow_item_id', 'category_id'],
        'category_workflow_profile' => ['workflow_profile_id', 'category_id'],
        'workflow_profile_items' => [
            'id', 'workflow_profile_id', 'workflow_item_id', 'sort_order',
            'is_required', 'result_label_mode', 'created_at', 'updated_at',
        ],
        'workflow_runs' => [
            'id', 'asset_id', 'model_number_id', 'workflow_profile_id',
            'profile_name_snapshot', 'profile_slug_snapshot', 'user_id',
            'started_at', 'finished_at', 'created_at', 'updated_at',
        ],
        'workflow_results' => [
            'id', 'workflow_run_id', 'workflow_item_id', 'workflow_profile_item_id',
            'attribute_definition_id', 'status', 'expected_value', 'expected_raw_value',
            'is_required', 'result_label_mode', 'sort_order', 'note', 'photo_path',
            'created_at', 'updated_at',
        ],
        'workflow_result_photos' => [
            'id', 'workflow_result_id', 'path', 'created_at', 'updated_at',
        ],
        'workflow_audits' => [
            'id', 'auditable_type', 'auditable_id', 'user_id', 'created_by',
            'field', 'before', 'after', 'created_at',
        ],
        'workflow_migration_checkpoints' => ['phase', 'value', 'verified_at'],
    ];

    /**
     * @var array<string,list<string>>
     */
    private const LEGACY_COLUMNS = [
        'test_types' => [
            'id', 'attribute_definition_id', 'name', 'slug', 'display_order',
            'tooltip', 'instructions', 'category', 'is_required', 'created_at', 'updated_at',
        ],
        'category_test_type' => ['test_type_id', 'category_id'],
        'test_runs' => [
            'id', 'asset_id', 'model_number_id', 'user_id', 'started_at',
            'finished_at', 'created_at', 'updated_at',
        ],
        'test_results' => [
            'id', 'test_run_id', 'test_type_id', 'attribute_definition_id',
            'status', 'expected_value', 'expected_raw_value', 'note',
            'photo_path', 'created_at', 'updated_at',
        ],
        'test_result_photos' => [
            'id', 'test_result_id', 'path', 'created_at', 'updated_at',
        ],
        'test_audits' => [
            'id', 'auditable_type', 'auditable_id', 'user_id',
            'field', 'before', 'after', 'created_at',
        ],
    ];

    public function ensureCheckpointTable(): void
    {
        if (Schema::hasTable('workflow_migration_checkpoints')) {
            return;
        }

        Schema::create('workflow_migration_checkpoints', function (Blueprint $table): void {
            $table->string('phase')->primary();
            $table->string('value')->nullable();
            $table->timestamp('verified_at');
        });
    }

    public function assertWorkflowSchema(): void
    {
        $this->assertTablesAndColumns(self::WORKFLOW_COLUMNS, 'workflow destination');
    }

    /**
     * @return 'absent'|'present'
     */
    public function legacySourceState(): string
    {
        $existing = collect(array_keys(self::LEGACY_COLUMNS))
            ->filter(fn (string $table): bool => Schema::hasTable($table))
            ->values();

        if ($existing->isEmpty()) {
            return 'absent';
        }

        if ($existing->count() !== count(self::LEGACY_COLUMNS)) {
            $missing = array_values(array_diff(array_keys(self::LEGACY_COLUMNS), $existing->all()));

            throw new RuntimeException(
                'Workflow migration found an incomplete legacy schema; missing tables: '
                . implode(', ', $missing) . '.'
            );
        }

        $this->assertTablesAndColumns(self::LEGACY_COLUMNS, 'legacy test');

        return 'present';
    }

    public function copyLegacyToWorkflows(): int
    {
        if ($this->legacySourceState() !== 'present') {
            throw new RuntimeException('Cannot copy workflow data because the complete legacy schema is absent.');
        }

        $profileId = $this->ensureDefaultProfile();

        $this->reconcileById(
            'test_types',
            'workflow_items',
            fn (stdClass $row): array => $this->legacyItemToWorkflow($row)
        );
        $this->reconcilePivot(
            'category_test_type',
            'category_workflow_item',
            fn (stdClass $row): array => [
                'workflow_item_id' => $row->test_type_id,
                'category_id' => $row->category_id,
            ],
            ['workflow_item_id', 'category_id']
        );

        $profileItems = $this->ensureDefaultProfileItems($profileId);

        $this->reconcileById(
            'test_runs',
            'workflow_runs',
            fn (stdClass $row): array => $this->legacyRunToWorkflow($row, $profileId)
        );
        $this->reconcileById(
            'test_results',
            'workflow_results',
            fn (stdClass $row): array => $this->legacyResultToWorkflow($row, $profileItems)
        );
        $this->reconcileById(
            'test_result_photos',
            'workflow_result_photos',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'workflow_result_id' => $row->test_result_id,
                'path' => $row->path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]
        );
        $this->reconcileById(
            'test_audits',
            'workflow_audits',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'auditable_type' => $row->auditable_type,
                'auditable_id' => $row->auditable_id,
                'user_id' => $row->user_id ?? null,
                'created_by' => $row->created_by ?? null,
                'field' => $row->field,
                'before' => $row->before,
                'after' => $row->after,
                'created_at' => $row->created_at,
            ]
        );

        $this->assertLegacyToWorkflowParity($profileId);

        return $profileId;
    }

    public function assertLegacyToWorkflowParity(?int $profileId = null): void
    {
        if ($this->legacySourceState() !== 'present') {
            throw new RuntimeException('Cannot verify workflow parity because the legacy schema is absent.');
        }

        $profileId ??= $this->defaultProfileId();
        $this->assertDefaultProfile($profileId);
        $profileItems = $this->assertDefaultProfileItems($profileId);

        $this->assertMappedParity(
            'test_types',
            'workflow_items',
            fn (stdClass $row): array => $this->legacyItemToWorkflow($row)
        );
        $this->assertPivotParity(
            'category_test_type',
            'category_workflow_item',
            fn (stdClass $row): array => [
                'workflow_item_id' => $row->test_type_id,
                'category_id' => $row->category_id,
            ],
            ['workflow_item_id', 'category_id']
        );
        $this->assertMappedParity(
            'test_runs',
            'workflow_runs',
            fn (stdClass $row): array => $this->legacyRunToWorkflow($row, $profileId)
        );
        $this->assertMappedParity(
            'test_results',
            'workflow_results',
            fn (stdClass $row): array => $this->legacyResultToWorkflow($row, $profileItems)
        );
        $this->assertMappedParity(
            'test_result_photos',
            'workflow_result_photos',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'workflow_result_id' => $row->test_result_id,
                'path' => $row->path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]
        );
        $this->assertMappedParity(
            'test_audits',
            'workflow_audits',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'auditable_type' => $row->auditable_type,
                'auditable_id' => $row->auditable_id,
                'user_id' => $row->user_id ?? null,
                'created_by' => $row->created_by ?? null,
                'field' => $row->field,
                'before' => $row->before,
                'after' => $row->after,
                'created_at' => $row->created_at,
            ]
        );
    }

    public function createLegacyTestTables(): void
    {
        if (!Schema::hasTable('test_types')) {
            Schema::create('test_types', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedBigInteger('attribute_definition_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('display_order')->default(0);
                $table->string('tooltip')->nullable();
                $table->text('instructions')->nullable();
                $table->string('category')->nullable();
                $table->boolean('is_required')->default(true);
                $table->timestamps();
                $table->foreign('attribute_definition_id')
                    ->references('id')->on('attribute_definitions')->nullOnDelete();
                $table->index(['display_order', 'id'], 'test_types_display_order_idx');
            });
        }

        if (!Schema::hasTable('category_test_type')) {
            Schema::create('category_test_type', function (Blueprint $table): void {
                $table->unsignedInteger('test_type_id');
                $table->unsignedInteger('category_id');
                $table->primary(['test_type_id', 'category_id']);
                $table->index('category_id');
                $table->foreign('test_type_id')->references('id')->on('test_types')->cascadeOnDelete();
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('test_runs')) {
            Schema::create('test_runs', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('asset_id');
                $table->unsignedBigInteger('model_number_id')->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
                $table->foreign('model_number_id')->references('id')->on('model_numbers')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('test_results')) {
            Schema::create('test_results', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('test_run_id');
                $table->unsignedInteger('test_type_id');
                $table->unsignedBigInteger('attribute_definition_id')->nullable();
                $table->enum('status', ['pass', 'fail', 'nvt']);
                $table->text('expected_value')->nullable();
                $table->text('expected_raw_value')->nullable();
                $table->text('note')->nullable();
                $table->string('photo_path')->nullable();
                $table->timestamps();
                $table->foreign('test_run_id')->references('id')->on('test_runs')->cascadeOnDelete();
                $table->foreign('test_type_id')->references('id')->on('test_types')->cascadeOnDelete();
                $table->foreign('attribute_definition_id')
                    ->references('id')->on('attribute_definitions')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('test_result_photos')) {
            Schema::create('test_result_photos', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('test_result_id');
                $table->string('path');
                $table->timestamps();
                $table->foreign('test_result_id')->references('id')->on('test_results')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('test_audits')) {
            Schema::create('test_audits', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('auditable_type');
                $table->unsignedInteger('auditable_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->string('field');
                $table->text('before')->nullable();
                $table->text('after')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        if (!Schema::hasColumn('test_audits', 'created_by')) {
            Schema::table('test_audits', function (Blueprint $table): void {
                $table->unsignedInteger('created_by')->nullable();
            });
        }

        $this->assertTablesAndColumns(self::LEGACY_COLUMNS, 'legacy test');
    }

    public function copyWorkflowsToLegacy(): void
    {
        $this->assertWorkflowRepresentableByLegacySchema();
        $this->assertLegacyDestinationsContainNoExtraRows();

        $profileId = $this->defaultProfileId();

        $this->writeMappedRows(
            'workflow_items',
            'test_types',
            fn (stdClass $row): array => $this->workflowItemToLegacy($row)
        );
        $this->writeMappedPivot(
            'category_workflow_item',
            'category_test_type',
            fn (stdClass $row): array => [
                'test_type_id' => $row->workflow_item_id,
                'category_id' => $row->category_id,
            ],
            ['test_type_id', 'category_id']
        );
        $this->writeMappedRows(
            'workflow_runs',
            'test_runs',
            fn (stdClass $row): array => $this->workflowRunToLegacy($row)
        );
        $this->writeMappedRows(
            'workflow_results',
            'test_results',
            fn (stdClass $row): array => $this->workflowResultToLegacy($row, $profileId)
        );
        $this->writeMappedRows(
            'workflow_result_photos',
            'test_result_photos',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'test_result_id' => $row->workflow_result_id,
                'path' => $row->path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]
        );
        $this->writeMappedRows(
            'workflow_audits',
            'test_audits',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'auditable_type' => $row->auditable_type,
                'auditable_id' => $row->auditable_id,
                'user_id' => $row->user_id,
                'created_by' => $row->created_by,
                'field' => $row->field,
                'before' => $row->before,
                'after' => $row->after,
                'created_at' => $row->created_at,
            ]
        );

        $this->assertWorkflowToLegacyParity();
    }

    public function assertWorkflowToLegacyParity(): void
    {
        $this->assertWorkflowRepresentableByLegacySchema();
        $profileId = $this->defaultProfileId();

        $this->assertMappedParity(
            'workflow_items',
            'test_types',
            fn (stdClass $row): array => $this->workflowItemToLegacy($row)
        );
        $this->assertPivotParity(
            'category_workflow_item',
            'category_test_type',
            fn (stdClass $row): array => [
                'test_type_id' => $row->workflow_item_id,
                'category_id' => $row->category_id,
            ],
            ['test_type_id', 'category_id']
        );
        $this->assertMappedParity(
            'workflow_runs',
            'test_runs',
            fn (stdClass $row): array => $this->workflowRunToLegacy($row)
        );
        $this->assertMappedParity(
            'workflow_results',
            'test_results',
            fn (stdClass $row): array => $this->workflowResultToLegacy($row, $profileId)
        );
        $this->assertMappedParity(
            'workflow_result_photos',
            'test_result_photos',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'test_result_id' => $row->workflow_result_id,
                'path' => $row->path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]
        );
        $this->assertMappedParity(
            'workflow_audits',
            'test_audits',
            fn (stdClass $row): array => [
                'id' => $row->id,
                'auditable_type' => $row->auditable_type,
                'auditable_id' => $row->auditable_id,
                'user_id' => $row->user_id,
                'created_by' => $row->created_by,
                'field' => $row->field,
                'before' => $row->before,
                'after' => $row->after,
                'created_at' => $row->created_at,
            ]
        );
    }

    public function putCheckpoint(string $phase, ?string $value): void
    {
        $this->ensureCheckpointTable();

        DB::table('workflow_migration_checkpoints')->updateOrInsert(
            ['phase' => $phase],
            ['value' => $value, 'verified_at' => now()]
        );
    }

    public function checkpointValue(string $phase): ?string
    {
        if (!Schema::hasTable('workflow_migration_checkpoints')) {
            return null;
        }

        return DB::table('workflow_migration_checkpoints')
            ->where('phase', $phase)
            ->value('value');
    }

    public function deleteCheckpoint(string $phase): void
    {
        if (Schema::hasTable('workflow_migration_checkpoints')) {
            DB::table('workflow_migration_checkpoints')->where('phase', $phase)->delete();
        }
    }

    public function currentAssetImagePhotoForeignKeyTarget(): string
    {
        $foreignKeys = $this->assetImagePhotoForeignKeys();

        if ($foreignKeys->count() > 1) {
            throw new RuntimeException(
                'asset_images.source_photo_id has multiple foreign keys; cutover cannot be verified safely.'
            );
        }

        return $foreignKeys->first()->target_table ?? self::NO_FOREIGN_KEY;
    }

    public function retargetAssetImagePhotoForeignKey(string $targetTable): void
    {
        if (
            !Schema::hasTable('asset_images')
            || !Schema::hasColumn('asset_images', 'source_photo_id')
        ) {
            return;
        }

        if (!Schema::hasTable($targetTable)) {
            throw new RuntimeException(
                "Cannot retarget asset_images.source_photo_id because {$targetTable} does not exist."
            );
        }

        $foreignKeys = $this->assetImagePhotoForeignKeys();

        if ($foreignKeys->count() > 1) {
            throw new RuntimeException(
                'asset_images.source_photo_id has multiple foreign keys; cutover cannot proceed safely.'
            );
        }

        $current = $foreignKeys->first();

        if (($current->target_table ?? null) === $targetTable) {
            return;
        }

        if ($current) {
            Schema::table('asset_images', function (Blueprint $table) use ($current): void {
                if ($current->constraint_name) {
                    $table->dropForeign($current->constraint_name);
                } else {
                    $table->dropForeign(['source_photo_id']);
                }
            });
        }

        Schema::table('asset_images', function (Blueprint $table) use ($targetTable): void {
            $table->foreign('source_photo_id')
                ->references('id')
                ->on($targetTable)
                ->nullOnDelete();
        });

        $actualTarget = $this->currentAssetImagePhotoForeignKeyTarget();

        if ($actualTarget !== $targetTable) {
            throw new RuntimeException(
                "asset_images.source_photo_id foreign-key verification failed: expected {$targetTable}, "
                . "found {$actualTarget}."
            );
        }
    }

    public function restoreAssetImagePhotoForeignKey(string $target): void
    {
        if ($target === self::NO_FOREIGN_KEY) {
            $foreignKeys = $this->assetImagePhotoForeignKeys();

            if ($foreignKeys->count() > 1) {
                throw new RuntimeException(
                    'asset_images.source_photo_id has multiple foreign keys; rollback cannot proceed safely.'
                );
            }

            $current = $foreignKeys->first();

            if ($current) {
                Schema::table('asset_images', function (Blueprint $table) use ($current): void {
                    if ($current->constraint_name) {
                        $table->dropForeign($current->constraint_name);
                    } else {
                        $table->dropForeign(['source_photo_id']);
                    }
                });
            }

            if ($this->currentAssetImagePhotoForeignKeyTarget() !== self::NO_FOREIGN_KEY) {
                throw new RuntimeException(
                    'Could not restore the absence of asset_images.source_photo_id foreign key.'
                );
            }

            return;
        }

        $this->retargetAssetImagePhotoForeignKey($target);
    }

    private function ensureDefaultProfile(): int
    {
        $profileId = DB::table('workflow_profiles')
            ->where('slug', self::DEFAULT_PROFILE_SLUG)
            ->value('id');

        if (!$profileId) {
            $profileId = DB::table('workflow_profiles')->insertGetId([
                'name' => self::DEFAULT_PROFILE_NAME,
                'slug' => self::DEFAULT_PROFILE_SLUG,
                'description' => 'Default migrated workflow profile for the previous standard test run.',
                'is_active' => true,
                'is_default' => true,
                'blocks_sale_readiness' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertDefaultProfile((int) $profileId);

        if (DB::table('workflow_profiles')->where('id', '<>', $profileId)->exists()) {
            throw new RuntimeException(
                'Workflow migration found destination-only profiles before cutover and will not overwrite them.'
            );
        }

        return (int) $profileId;
    }

    private function defaultProfileId(): int
    {
        $profileId = DB::table('workflow_profiles')
            ->where('slug', self::DEFAULT_PROFILE_SLUG)
            ->value('id');

        if (!$profileId) {
            throw new RuntimeException('The migrated Standard Diagnostics workflow profile is missing.');
        }

        return (int) $profileId;
    }

    private function assertDefaultProfile(int $profileId): void
    {
        $profile = DB::table('workflow_profiles')->where('id', $profileId)->first();

        if (!$profile) {
            throw new RuntimeException("Workflow profile {$profileId} is missing.");
        }

        $this->assertRowMatches('workflow_profiles', $profile, [
            'id' => $profileId,
            'name' => self::DEFAULT_PROFILE_NAME,
            'slug' => self::DEFAULT_PROFILE_SLUG,
            'description' => 'Default migrated workflow profile for the previous standard test run.',
            'is_active' => true,
            'is_default' => true,
            'blocks_sale_readiness' => true,
            'display_order' => 0,
        ]);
    }

    /**
     * @return Collection<int,stdClass>
     */
    private function ensureDefaultProfileItems(int $profileId): Collection
    {
        $items = DB::table('workflow_items')->orderBy('display_order')->orderBy('id')->get();

        foreach ($items as $index => $item) {
            $desired = [
                'workflow_profile_id' => $profileId,
                'workflow_item_id' => $item->id,
                'sort_order' => $index,
                'is_required' => $item->is_required ?? true,
                'result_label_mode' => 'pass_fail',
            ];
            $existing = DB::table('workflow_profile_items')
                ->where('workflow_profile_id', $profileId)
                ->where('workflow_item_id', $item->id)
                ->first();

            if ($existing) {
                $this->assertRowMatches('workflow_profile_items', $existing, $desired);
                continue;
            }

            DB::table('workflow_profile_items')->insert($desired + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->assertDefaultProfileItems($profileId);
    }

    /**
     * @return Collection<int,stdClass>
     */
    private function assertDefaultProfileItems(int $profileId): Collection
    {
        $items = DB::table('workflow_items')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->values();
        $profileItems = DB::table('workflow_profile_items')
            ->where('workflow_profile_id', $profileId)
            ->get()
            ->keyBy('workflow_item_id');

        if ($profileItems->count() !== $items->count()) {
            throw new RuntimeException(
                'Standard Diagnostics profile-item parity failed: expected '
                . $items->count() . ', found ' . $profileItems->count() . '.'
            );
        }

        foreach ($items as $index => $item) {
            $profileItem = $profileItems->get($item->id);

            if (!$profileItem) {
                throw new RuntimeException(
                    "Standard Diagnostics is missing workflow item {$item->id}."
                );
            }

            $this->assertRowMatches('workflow_profile_items', $profileItem, [
                'workflow_profile_id' => $profileId,
                'workflow_item_id' => $item->id,
                'sort_order' => $index,
                'is_required' => $item->is_required ?? true,
                'result_label_mode' => 'pass_fail',
            ]);
        }

        return $profileItems;
    }

    private function reconcileById(string $sourceTable, string $destinationTable, callable $mapper): void
    {
        foreach (DB::table($sourceTable)->orderBy('id')->get() as $source) {
            $desired = $mapper($source);
            $existing = DB::table($destinationTable)->where('id', $desired['id'])->first();

            if ($existing) {
                $this->assertRowMatches($destinationTable, $existing, $desired);
                continue;
            }

            DB::table($destinationTable)->insert($desired);
        }

        $this->assertIdSetParity($sourceTable, $destinationTable);
    }

    private function reconcilePivot(
        string $sourceTable,
        string $destinationTable,
        callable $mapper,
        array $keyColumns
    ): void {
        foreach (DB::table($sourceTable)->get() as $source) {
            $desired = $mapper($source);
            $query = DB::table($destinationTable);

            foreach ($keyColumns as $column) {
                $query->where($column, $desired[$column]);
            }

            $existing = $query->first();

            if ($existing) {
                $this->assertRowMatches($destinationTable, $existing, $desired);
                continue;
            }

            DB::table($destinationTable)->insert($desired);
        }

        $this->assertPivotParity($sourceTable, $destinationTable, $mapper, $keyColumns);
    }

    private function assertMappedParity(string $sourceTable, string $destinationTable, callable $mapper): void
    {
        $this->assertIdSetParity($sourceTable, $destinationTable);

        foreach (DB::table($sourceTable)->orderBy('id')->get() as $source) {
            $desired = $mapper($source);
            $destination = DB::table($destinationTable)->where('id', $desired['id'])->first();

            if (!$destination) {
                throw new RuntimeException(
                    "{$destinationTable} is missing ID {$desired['id']} copied from {$sourceTable}."
                );
            }

            $this->assertRowMatches($destinationTable, $destination, $desired);
        }
    }

    private function assertPivotParity(
        string $sourceTable,
        string $destinationTable,
        callable $mapper,
        array $keyColumns
    ): void {
        $expected = DB::table($sourceTable)
            ->get()
            ->map(fn (stdClass $row): string => $this->compositeKey($mapper($row), $keyColumns))
            ->sort()
            ->values()
            ->all();
        $actual = DB::table($destinationTable)
            ->get()
            ->map(fn (stdClass $row): string => $this->compositeKey((array) $row, $keyColumns))
            ->sort()
            ->values()
            ->all();

        if ($expected !== $actual) {
            throw new RuntimeException(
                "{$sourceTable} to {$destinationTable} mapping parity failed."
            );
        }
    }

    private function writeMappedRows(string $sourceTable, string $destinationTable, callable $mapper): void
    {
        foreach (DB::table($sourceTable)->orderBy('id')->get() as $source) {
            $desired = $mapper($source);

            DB::table($destinationTable)->updateOrInsert(
                ['id' => $desired['id']],
                $desired
            );
        }
    }

    private function writeMappedPivot(
        string $sourceTable,
        string $destinationTable,
        callable $mapper,
        array $keyColumns
    ): void {
        foreach (DB::table($sourceTable)->get() as $source) {
            $desired = $mapper($source);
            $key = [];

            foreach ($keyColumns as $column) {
                $key[$column] = $desired[$column];
            }

            DB::table($destinationTable)->updateOrInsert($key, $desired);
        }
    }

    private function assertLegacyDestinationsContainNoExtraRows(): void
    {
        foreach (
            [
                ['workflow_items', 'test_types'],
                ['workflow_runs', 'test_runs'],
                ['workflow_results', 'test_results'],
                ['workflow_result_photos', 'test_result_photos'],
                ['workflow_audits', 'test_audits'],
            ] as [$source, $destination]
        ) {
            $sourceIds = DB::table($source)->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $destinationIds = DB::table($destination)->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $extra = array_diff($destinationIds, $sourceIds);

            if ($extra !== []) {
                throw new RuntimeException(
                    "{$destination} contains IDs absent from {$source}; rollback would be ambiguous: "
                    . implode(', ', $extra) . '.'
                );
            }
        }

        $source = DB::table('category_workflow_item')
            ->get()
            ->map(fn (stdClass $row): string => $row->workflow_item_id . ':' . $row->category_id)
            ->all();
        $destination = DB::table('category_test_type')
            ->get()
            ->map(fn (stdClass $row): string => $row->test_type_id . ':' . $row->category_id)
            ->all();
        $extra = array_diff($destination, $source);

        if ($extra !== []) {
            throw new RuntimeException(
                'category_test_type contains relationships absent from category_workflow_item; '
                . 'rollback would be ambiguous.'
            );
        }
    }

    private function assertWorkflowRepresentableByLegacySchema(): void
    {
        $profileId = $this->defaultProfileId();
        $this->assertDefaultProfile($profileId);

        if (DB::table('workflow_profiles')->where('id', '<>', $profileId)->exists()) {
            throw new RuntimeException(
                'Workflow rollback refused because additional workflow profiles cannot be represented by legacy tables.'
            );
        }

        if (DB::table('category_workflow_profile')->exists()) {
            throw new RuntimeException(
                'Workflow rollback refused because category/profile assignments cannot be represented by legacy tables.'
            );
        }

        $profileItems = $this->assertDefaultProfileItems($profileId);

        foreach (DB::table('workflow_runs')->get() as $run) {
            $this->assertRowMatches('workflow_runs', $run, [
                'workflow_profile_id' => $profileId,
                'profile_name_snapshot' => self::DEFAULT_PROFILE_NAME,
                'profile_slug_snapshot' => self::DEFAULT_PROFILE_SLUG,
            ]);
        }

        foreach (DB::table('workflow_results')->get() as $result) {
            $profileItem = $profileItems->get($result->workflow_item_id);

            if (!$profileItem) {
                throw new RuntimeException(
                    "Workflow result {$result->id} has no Standard Diagnostics profile item."
                );
            }

            $this->assertRowMatches('workflow_results', $result, [
                'workflow_profile_item_id' => $profileItem->id,
                'is_required' => $profileItem->is_required,
                'result_label_mode' => $profileItem->result_label_mode,
                'sort_order' => $profileItem->sort_order,
            ]);
        }
    }

    private function legacyItemToWorkflow(stdClass $row): array
    {
        return [
            'id' => $row->id,
            'attribute_definition_id' => $row->attribute_definition_id ?? null,
            'name' => $row->name,
            'slug' => $row->slug,
            'display_order' => $row->display_order ?? 0,
            'tooltip' => $row->tooltip ?? null,
            'instructions' => $row->instructions ?? null,
            'category' => $row->category ?? null,
            'is_required' => $row->is_required ?? true,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function legacyRunToWorkflow(stdClass $row, int $profileId): array
    {
        return [
            'id' => $row->id,
            'asset_id' => $row->asset_id,
            'model_number_id' => $row->model_number_id ?? null,
            'workflow_profile_id' => $profileId,
            'profile_name_snapshot' => self::DEFAULT_PROFILE_NAME,
            'profile_slug_snapshot' => self::DEFAULT_PROFILE_SLUG,
            'user_id' => $row->user_id,
            'started_at' => $row->started_at,
            'finished_at' => $row->finished_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    /**
     * @param Collection<int,stdClass> $profileItems
     */
    private function legacyResultToWorkflow(stdClass $row, Collection $profileItems): array
    {
        $item = DB::table('workflow_items')->where('id', $row->test_type_id)->first();
        $profileItem = $profileItems->get($row->test_type_id);

        if (!$item || !$profileItem) {
            throw new RuntimeException(
                "Legacy test result {$row->id} references unmapped test type {$row->test_type_id}."
            );
        }

        return [
            'id' => $row->id,
            'workflow_run_id' => $row->test_run_id,
            'workflow_item_id' => $row->test_type_id,
            'workflow_profile_item_id' => $profileItem->id,
            'attribute_definition_id' => $row->attribute_definition_id ?? null,
            'status' => $row->status,
            'expected_value' => $row->expected_value ?? null,
            'expected_raw_value' => $row->expected_raw_value ?? null,
            'is_required' => $profileItem->is_required ?? $item->is_required ?? true,
            'result_label_mode' => $profileItem->result_label_mode ?? 'pass_fail',
            'sort_order' => $profileItem->sort_order ?? $item->display_order ?? 0,
            'note' => $row->note,
            'photo_path' => $row->photo_path,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function workflowItemToLegacy(stdClass $row): array
    {
        return [
            'id' => $row->id,
            'attribute_definition_id' => $row->attribute_definition_id,
            'name' => $row->name,
            'slug' => $row->slug,
            'display_order' => $row->display_order,
            'tooltip' => $row->tooltip,
            'instructions' => $row->instructions,
            'category' => $row->category,
            'is_required' => $row->is_required,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function workflowRunToLegacy(stdClass $row): array
    {
        return [
            'id' => $row->id,
            'asset_id' => $row->asset_id,
            'model_number_id' => $row->model_number_id,
            'user_id' => $row->user_id,
            'started_at' => $row->started_at,
            'finished_at' => $row->finished_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function workflowResultToLegacy(stdClass $row, int $profileId): array
    {
        if (
            (int) DB::table('workflow_runs')
                ->where('id', $row->workflow_run_id)
                ->value('workflow_profile_id') !== $profileId
        ) {
            throw new RuntimeException(
                "Workflow result {$row->id} belongs to a run outside Standard Diagnostics."
            );
        }

        return [
            'id' => $row->id,
            'test_run_id' => $row->workflow_run_id,
            'test_type_id' => $row->workflow_item_id,
            'attribute_definition_id' => $row->attribute_definition_id,
            'status' => $row->status,
            'expected_value' => $row->expected_value,
            'expected_raw_value' => $row->expected_raw_value,
            'note' => $row->note,
            'photo_path' => $row->photo_path,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    /**
     * @param array<string,list<string>> $tables
     */
    private function assertTablesAndColumns(array $tables, string $label): void
    {
        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("The {$label} table {$table} is missing.");
            }

            $missing = array_values(array_filter(
                $columns,
                fn (string $column): bool => !Schema::hasColumn($table, $column)
            ));

            if ($missing !== []) {
                throw new RuntimeException(
                    "The {$label} table {$table} is missing columns: " . implode(', ', $missing) . '.'
                );
            }
        }
    }

    private function assertIdSetParity(string $sourceTable, string $destinationTable): void
    {
        $sourceIds = DB::table($sourceTable)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();
        $destinationIds = DB::table($destinationTable)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        if ($sourceIds !== $destinationIds) {
            throw new RuntimeException(
                "{$sourceTable} to {$destinationTable} ID parity failed; "
                . 'the migration will not continue or remove legacy data.'
            );
        }
    }

    private function assertRowMatches(string $table, stdClass $row, array $expected): void
    {
        foreach ($expected as $column => $value) {
            $actual = $row->{$column} ?? null;

            if (!$this->valuesMatch($actual, $value)) {
                throw new RuntimeException(
                    "{$table} mapping mismatch at ID "
                    . ($row->id ?? '(composite)') . ", column {$column}: expected "
                    . $this->displayValue($value) . ', found ' . $this->displayValue($actual) . '.'
                );
            }
        }
    }

    private function valuesMatch(mixed $actual, mixed $expected): bool
    {
        if ($actual === null || $expected === null) {
            return $actual === $expected;
        }

        return (string) $actual === (string) $expected;
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . (string) $value . "'";
    }

    private function compositeKey(array $row, array $columns): string
    {
        return implode(':', array_map(
            fn (string $column): string => (string) $row[$column],
            $columns
        ));
    }

    /**
     * @return Collection<int,object{constraint_name:?string,target_table:string}>
     */
    private function assetImagePhotoForeignKeys(): Collection
    {
        if (
            !Schema::hasTable('asset_images')
            || !Schema::hasColumn('asset_images', 'source_photo_id')
        ) {
            return collect();
        }

        return match (DB::getDriverName()) {
            'sqlite' => collect(DB::select("PRAGMA foreign_key_list('asset_images')"))
                ->filter(fn (stdClass $row): bool => $row->from === 'source_photo_id')
                ->map(fn (stdClass $row): stdClass => (object) [
                    'constraint_name' => null,
                    'target_table' => $row->table,
                ])
                ->values(),
            'mysql', 'mariadb' => collect(DB::select(
                <<<'SQL'
SELECT CONSTRAINT_NAME AS constraint_name, REFERENCED_TABLE_NAME AS target_table
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'asset_images'
  AND COLUMN_NAME = 'source_photo_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL
SQL
            )),
            'pgsql' => collect(DB::select(
                <<<'SQL'
SELECT tc.constraint_name, ccu.table_name AS target_table
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
  ON tc.constraint_catalog = kcu.constraint_catalog
 AND tc.constraint_schema = kcu.constraint_schema
 AND tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage ccu
  ON tc.constraint_catalog = ccu.constraint_catalog
 AND tc.constraint_schema = ccu.constraint_schema
 AND tc.constraint_name = ccu.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_schema = current_schema()
  AND tc.table_name = 'asset_images'
  AND kcu.column_name = 'source_photo_id'
SQL
            )),
            default => throw new RuntimeException(
                'Workflow photo foreign-key verification is not implemented for database driver '
                . DB::getDriverName() . '.'
            ),
        };
    }
}
