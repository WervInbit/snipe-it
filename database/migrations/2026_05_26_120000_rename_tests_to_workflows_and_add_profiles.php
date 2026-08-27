<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create only the destination schema in this phase.
     *
     * Legacy data is copied and verified by the immediately following migration,
     * and the optional asset-image foreign key is cut over in a third migration.
     * Legacy tables intentionally remain as a rollback-compatible copy until a
     * separately reviewed cleanup release removes them.
     */
    public function up(): void
    {
        $this->createWorkflowTables();
    }

    public function down(): void
    {
        $rollbackVerified = Schema::hasTable('workflow_migration_checkpoints')
            ? DB::table('workflow_migration_checkpoints')
                ->where('phase', 'rollback_copy_verified')
                ->value('value')
            : null;
        $hasWorkflowData = collect([
            'workflow_profiles',
            'workflow_items',
            'workflow_runs',
            'workflow_results',
            'workflow_result_photos',
            'workflow_audits',
        ])->contains(
            fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists()
        );

        if ($hasWorkflowData && $rollbackVerified !== 'present') {
            throw new RuntimeException(
                'Workflow schema rollback refused because no verified legacy rollback copy exists.'
            );
        }

        $foreignKeysDisabled = false;

        try {
            Schema::disableForeignKeyConstraints();
            $foreignKeysDisabled = true;
            $this->dropWorkflowTables();
        } finally {
            if ($foreignKeysDisabled) {
                Schema::enableForeignKeyConstraints();
            }
        }
    }

    private function createWorkflowTables(): void
    {
        if (!Schema::hasTable('workflow_profiles')) {
            Schema::create('workflow_profiles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('blocks_sale_readiness')->default(false);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workflow_items')) {
            Schema::create('workflow_items', function (Blueprint $table) {
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
                    ->references('id')
                    ->on('attribute_definitions')
                    ->nullOnDelete();
                $table->index(['display_order', 'id'], 'workflow_items_display_order_idx');
            });
        }

        if (!Schema::hasTable('category_workflow_item')) {
            Schema::create('category_workflow_item', function (Blueprint $table) {
                $table->unsignedInteger('workflow_item_id');
                $table->unsignedInteger('category_id');

                $table->primary(['workflow_item_id', 'category_id']);
                $table->index('category_id');
                $table->foreign('workflow_item_id')
                    ->references('id')
                    ->on('workflow_items')
                    ->cascadeOnDelete();
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('category_workflow_profile')) {
            Schema::create('category_workflow_profile', function (Blueprint $table) {
                $table->unsignedInteger('workflow_profile_id');
                $table->unsignedInteger('category_id');

                $table->primary(['workflow_profile_id', 'category_id']);
                $table->index('category_id');
                $table->foreign('workflow_profile_id')
                    ->references('id')
                    ->on('workflow_profiles')
                    ->cascadeOnDelete();
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('workflow_profile_items')) {
            Schema::create('workflow_profile_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('workflow_profile_id');
                $table->unsignedInteger('workflow_item_id');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->string('result_label_mode')->default('pass_fail');
                $table->timestamps();

                $table->foreign('workflow_profile_id')
                    ->references('id')
                    ->on('workflow_profiles')
                    ->cascadeOnDelete();
                $table->foreign('workflow_item_id')
                    ->references('id')
                    ->on('workflow_items')
                    ->cascadeOnDelete();
                $table->unique(['workflow_profile_id', 'workflow_item_id'], 'workflow_profile_item_unique');
                $table->index(['workflow_profile_id', 'sort_order'], 'workflow_profile_item_sort_idx');
            });
        }

        if (!Schema::hasTable('workflow_runs')) {
            Schema::create('workflow_runs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('asset_id');
                $table->unsignedBigInteger('model_number_id')->nullable();
                $table->unsignedInteger('workflow_profile_id')->nullable();
                $table->string('profile_name_snapshot')->nullable();
                $table->string('profile_slug_snapshot')->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
                $table->foreign('model_number_id')->references('id')->on('model_numbers')->nullOnDelete();
                $table->foreign('workflow_profile_id')->references('id')->on('workflow_profiles')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('workflow_results')) {
            Schema::create('workflow_results', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('workflow_run_id');
                $table->unsignedInteger('workflow_item_id');
                $table->unsignedInteger('workflow_profile_item_id')->nullable();
                $table->unsignedBigInteger('attribute_definition_id')->nullable();
                $table->enum('status', ['pass', 'fail', 'nvt']);
                $table->text('expected_value')->nullable();
                $table->text('expected_raw_value')->nullable();
                $table->boolean('is_required')->default(true);
                $table->string('result_label_mode')->default('pass_fail');
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('note')->nullable();
                $table->string('photo_path')->nullable();
                $table->timestamps();

                $table->foreign('workflow_run_id')->references('id')->on('workflow_runs')->cascadeOnDelete();
                $table->foreign('workflow_item_id')->references('id')->on('workflow_items')->cascadeOnDelete();
                $table->foreign('workflow_profile_item_id')
                    ->references('id')->on('workflow_profile_items')->nullOnDelete();
                $table->foreign('attribute_definition_id')
                    ->references('id')->on('attribute_definitions')->nullOnDelete();
                $table->index(['workflow_run_id', 'sort_order'], 'workflow_result_run_sort_idx');
            });
        }

        if (!Schema::hasTable('workflow_result_photos')) {
            Schema::create('workflow_result_photos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('workflow_result_id');
                $table->string('path');
                $table->timestamps();

                $table->foreign('workflow_result_id')
                    ->references('id')
                    ->on('workflow_results')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('workflow_audits')) {
            Schema::create('workflow_audits', function (Blueprint $table) {
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

        if (!Schema::hasTable('workflow_migration_checkpoints')) {
            Schema::create('workflow_migration_checkpoints', function (Blueprint $table) {
                $table->string('phase')->primary();
                $table->string('value')->nullable();
                $table->timestamp('verified_at');
            });
        }
    }

    private function dropWorkflowTables(): void
    {
        foreach (
            [
            'workflow_result_photos',
            'workflow_results',
            'workflow_runs',
            'workflow_profile_items',
            'category_workflow_profile',
            'category_workflow_item',
            'workflow_items',
            'workflow_profiles',
            'workflow_audits',
            'workflow_migration_checkpoints',
            ] as $table
        ) {
            Schema::dropIfExists($table);
        }
    }
};
