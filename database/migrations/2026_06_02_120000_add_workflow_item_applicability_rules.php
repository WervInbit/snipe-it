<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_items') && !Schema::hasColumn('workflow_items', 'applies_to_all')) {
            Schema::table('workflow_items', function (Blueprint $table): void {
                $table->boolean('applies_to_all')->default(false)->after('category');
            });
        }

        if (!Schema::hasTable('component_category_workflow_item')) {
            Schema::create('component_category_workflow_item', function (Blueprint $table): void {
                $table->unsignedInteger('workflow_item_id');
                $table->unsignedInteger('category_id');

                $table->primary(['workflow_item_id', 'category_id'], 'component_category_workflow_item_pk');
                $table->index('category_id', 'component_category_workflow_item_category_idx');
                $table->foreign('workflow_item_id', 'component_category_workflow_item_item_fk')
                    ->references('id')
                    ->on('workflow_items')
                    ->cascadeOnDelete();
                $table->foreign('category_id', 'component_category_workflow_item_category_fk')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('component_definition_workflow_item')) {
            Schema::create('component_definition_workflow_item', function (Blueprint $table): void {
                $table->unsignedInteger('workflow_item_id');
                $table->unsignedBigInteger('component_definition_id');

                $table->primary(['workflow_item_id', 'component_definition_id'], 'component_definition_workflow_item_pk');
                $table->index('component_definition_id', 'component_definition_workflow_item_definition_idx');
                $table->foreign('workflow_item_id', 'component_definition_workflow_item_item_fk')
                    ->references('id')
                    ->on('workflow_items')
                    ->cascadeOnDelete();
                $table->foreign('component_definition_id', 'component_definition_workflow_item_definition_fk')
                    ->references('id')
                    ->on('component_definitions')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('component_definition_workflow_item');
        Schema::dropIfExists('component_category_workflow_item');

        if (Schema::hasTable('workflow_items') && Schema::hasColumn('workflow_items', 'applies_to_all')) {
            Schema::table('workflow_items', function (Blueprint $table): void {
                $table->dropColumn('applies_to_all');
            });
        }
    }
};
