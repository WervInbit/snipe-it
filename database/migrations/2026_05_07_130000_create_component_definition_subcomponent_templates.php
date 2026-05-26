<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('component_definition_subcomponent_templates')) {
            return;
        }

        Schema::create('component_definition_subcomponent_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_component_definition_id');
            $table->unsignedBigInteger('child_component_definition_id')->nullable();
            $table->string('expected_name');
            $table->unsignedInteger('expected_qty')->default(1);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['parent_component_definition_id', 'sort_order'], 'cd_subcomponent_templates_parent_sort_idx');
            $table->index('child_component_definition_id', 'cd_subcomponent_templates_child_idx');
            $table->foreign('parent_component_definition_id', 'cd_subcomponent_templates_parent_fk')
                ->references('id')
                ->on('component_definitions')
                ->cascadeOnDelete();
            $table->foreign('child_component_definition_id', 'cd_subcomponent_templates_child_fk')
                ->references('id')
                ->on('component_definitions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_definition_subcomponent_templates');
    }
};
