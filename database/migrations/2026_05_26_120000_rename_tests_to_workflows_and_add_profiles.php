<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->createWorkflowTables();
        $defaultProfileId = $this->copyExistingTestsToWorkflows();
        $this->backfillDefaultProfile($defaultProfileId);
        $this->retargetAssetImagePhotoForeignKey('workflow_result_photos');
        $this->dropLegacyTestTables();

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->createLegacyTestTables();
        $this->copyWorkflowsToLegacyTests();
        $this->retargetAssetImagePhotoForeignKey('test_result_photos');
        $this->dropWorkflowTables();

        Schema::enableForeignKeyConstraints();
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
                $table->foreign('workflow_profile_item_id')->references('id')->on('workflow_profile_items')->nullOnDelete();
                $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->nullOnDelete();
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
    }

    private function copyExistingTestsToWorkflows(): ?int
    {
        if (Schema::hasTable('test_types') && DB::table('workflow_items')->count() === 0) {
            foreach (DB::table('test_types')->orderBy('id')->get() as $row) {
                DB::table('workflow_items')->insert([
                    'id' => $row->id,
                    'attribute_definition_id' => $row->attribute_definition_id ?? null,
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'display_order' => $row->display_order ?? 0,
                    'tooltip' => $row->tooltip ?? null,
                    'instructions' => $row->instructions ?? null,
                    'category' => $row->category ?? null,
                    'is_required' => $row->is_required ?? true,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('category_test_type') && DB::table('category_workflow_item')->count() === 0) {
            foreach (DB::table('category_test_type')->get() as $row) {
                DB::table('category_workflow_item')->insert([
                    'workflow_item_id' => $row->test_type_id,
                    'category_id' => $row->category_id,
                ]);
            }
        }

        $defaultProfileId = DB::table('workflow_profiles')
            ->where('slug', 'standard-diagnostics')
            ->value('id');

        if (!$defaultProfileId) {
            $defaultProfileId = DB::table('workflow_profiles')->insertGetId([
                'name' => 'Standard Diagnostics',
                'slug' => 'standard-diagnostics',
                'description' => 'Default migrated workflow profile for the previous standard test run.',
                'is_active' => true,
                'is_default' => true,
                'blocks_sale_readiness' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('workflow_profile_items')->where('workflow_profile_id', $defaultProfileId)->count() === 0) {
            foreach (DB::table('workflow_items')->orderBy('display_order')->orderBy('id')->get() as $index => $item) {
                DB::table('workflow_profile_items')->insert([
                    'workflow_profile_id' => $defaultProfileId,
                    'workflow_item_id' => $item->id,
                    'sort_order' => $index,
                    'is_required' => $item->is_required ?? true,
                    'result_label_mode' => 'pass_fail',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $profileItemsByItem = DB::table('workflow_profile_items')
            ->where('workflow_profile_id', $defaultProfileId)
            ->get()
            ->keyBy('workflow_item_id');

        if (Schema::hasTable('test_runs') && DB::table('workflow_runs')->count() === 0) {
            foreach (DB::table('test_runs')->orderBy('id')->get() as $row) {
                DB::table('workflow_runs')->insert([
                    'id' => $row->id,
                    'asset_id' => $row->asset_id,
                    'model_number_id' => $row->model_number_id ?? null,
                    'workflow_profile_id' => $defaultProfileId,
                    'profile_name_snapshot' => 'Standard Diagnostics',
                    'profile_slug_snapshot' => 'standard-diagnostics',
                    'user_id' => $row->user_id,
                    'started_at' => $row->started_at,
                    'finished_at' => $row->finished_at,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('test_results') && DB::table('workflow_results')->count() === 0) {
            $items = DB::table('workflow_items')->get()->keyBy('id');

            foreach (DB::table('test_results')->orderBy('id')->get() as $row) {
                $item = $items->get($row->test_type_id);
                $profileItem = $profileItemsByItem->get($row->test_type_id);

                DB::table('workflow_results')->insert([
                    'id' => $row->id,
                    'workflow_run_id' => $row->test_run_id,
                    'workflow_item_id' => $row->test_type_id,
                    'workflow_profile_item_id' => $profileItem?->id,
                    'attribute_definition_id' => $row->attribute_definition_id ?? null,
                    'status' => $row->status,
                    'expected_value' => $row->expected_value ?? null,
                    'expected_raw_value' => $row->expected_raw_value ?? null,
                    'is_required' => $profileItem?->is_required ?? $item?->is_required ?? true,
                    'result_label_mode' => $profileItem?->result_label_mode ?? 'pass_fail',
                    'sort_order' => $profileItem?->sort_order ?? $item?->display_order ?? 0,
                    'note' => $row->note,
                    'photo_path' => $row->photo_path,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('test_result_photos') && DB::table('workflow_result_photos')->count() === 0) {
            foreach (DB::table('test_result_photos')->orderBy('id')->get() as $row) {
                DB::table('workflow_result_photos')->insert([
                    'id' => $row->id,
                    'workflow_result_id' => $row->test_result_id,
                    'path' => $row->path,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('test_audits') && DB::table('workflow_audits')->count() === 0) {
            foreach (DB::table('test_audits')->orderBy('id')->get() as $row) {
                DB::table('workflow_audits')->insert([
                    'id' => $row->id,
                    'auditable_type' => $row->auditable_type,
                    'auditable_id' => $row->auditable_id,
                    'user_id' => $row->user_id ?? null,
                    'created_by' => $row->created_by ?? null,
                    'field' => $row->field,
                    'before' => $row->before,
                    'after' => $row->after,
                    'created_at' => $row->created_at ?? now(),
                ]);
            }
        }

        return $defaultProfileId;
    }

    private function backfillDefaultProfile(?int $defaultProfileId): void
    {
        if (!$defaultProfileId) {
            return;
        }

        DB::table('workflow_profiles')
            ->where('id', '<>', $defaultProfileId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function retargetAssetImagePhotoForeignKey(string $targetTable): void
    {
        if (!Schema::hasTable('asset_images')
            || !Schema::hasColumn('asset_images', 'source_photo_id')
            || !Schema::hasTable($targetTable)) {
            return;
        }

        try {
            Schema::table('asset_images', function (Blueprint $table) {
                $table->dropForeign(['source_photo_id']);
            });
        } catch (\Throwable) {
            // Existing installs may not have the optional photo-source foreign key.
        }

        try {
            Schema::table('asset_images', function (Blueprint $table) use ($targetTable) {
                $table->foreign('source_photo_id')
                    ->references('id')
                    ->on($targetTable)
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // If a matching constraint already exists, keep it.
        }
    }

    private function dropLegacyTestTables(): void
    {
        foreach ([
            'category_test_type',
            'test_result_photos',
            'test_results',
            'test_runs',
            'test_types',
            'test_audits',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createLegacyTestTables(): void
    {
        if (!Schema::hasTable('test_types')) {
            Schema::create('test_types', function (Blueprint $table) {
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

                $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->nullOnDelete();
                $table->index(['display_order', 'id'], 'test_types_display_order_idx');
            });
        }

        if (!Schema::hasTable('category_test_type')) {
            Schema::create('category_test_type', function (Blueprint $table) {
                $table->unsignedInteger('test_type_id');
                $table->unsignedInteger('category_id');
                $table->primary(['test_type_id', 'category_id']);
                $table->index('category_id');
                $table->foreign('test_type_id')->references('id')->on('test_types')->cascadeOnDelete();
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('test_runs')) {
            Schema::create('test_runs', function (Blueprint $table) {
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
            Schema::create('test_results', function (Blueprint $table) {
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
                $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('test_result_photos')) {
            Schema::create('test_result_photos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('test_result_id');
                $table->string('path');
                $table->timestamps();
                $table->foreign('test_result_id')->references('id')->on('test_results')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('test_audits')) {
            Schema::create('test_audits', function (Blueprint $table) {
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
    }

    private function copyWorkflowsToLegacyTests(): void
    {
        if (Schema::hasTable('workflow_items')) {
            foreach (DB::table('workflow_items')->orderBy('id')->get() as $row) {
                DB::table('test_types')->insert([
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
                ]);
            }
        }

        if (Schema::hasTable('category_workflow_item')) {
            foreach (DB::table('category_workflow_item')->get() as $row) {
                DB::table('category_test_type')->insert([
                    'test_type_id' => $row->workflow_item_id,
                    'category_id' => $row->category_id,
                ]);
            }
        }

        if (Schema::hasTable('workflow_runs')) {
            foreach (DB::table('workflow_runs')->orderBy('id')->get() as $row) {
                DB::table('test_runs')->insert([
                    'id' => $row->id,
                    'asset_id' => $row->asset_id,
                    'model_number_id' => $row->model_number_id,
                    'user_id' => $row->user_id,
                    'started_at' => $row->started_at,
                    'finished_at' => $row->finished_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('workflow_results')) {
            foreach (DB::table('workflow_results')->orderBy('id')->get() as $row) {
                DB::table('test_results')->insert([
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
                ]);
            }
        }

        if (Schema::hasTable('workflow_result_photos')) {
            foreach (DB::table('workflow_result_photos')->orderBy('id')->get() as $row) {
                DB::table('test_result_photos')->insert([
                    'id' => $row->id,
                    'test_result_id' => $row->workflow_result_id,
                    'path' => $row->path,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('workflow_audits')) {
            foreach (DB::table('workflow_audits')->orderBy('id')->get() as $row) {
                DB::table('test_audits')->insert([
                    'id' => $row->id,
                    'auditable_type' => $row->auditable_type,
                    'auditable_id' => $row->auditable_id,
                    'user_id' => $row->user_id,
                    'created_by' => $row->created_by,
                    'field' => $row->field,
                    'before' => $row->before,
                    'after' => $row->after,
                    'created_at' => $row->created_at,
                ]);
            }
        }
    }

    private function dropWorkflowTables(): void
    {
        foreach ([
            'workflow_result_photos',
            'workflow_results',
            'workflow_runs',
            'workflow_profile_items',
            'category_workflow_profile',
            'category_workflow_item',
            'workflow_items',
            'workflow_profiles',
            'workflow_audits',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
