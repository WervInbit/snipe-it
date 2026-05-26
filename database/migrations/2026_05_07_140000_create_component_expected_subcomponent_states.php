<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('component_expected_subcomponent_states')) {
            return;
        }

        Schema::create('component_expected_subcomponent_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('component_instance_id');
            $table->unsignedBigInteger('component_definition_subcomponent_template_id');
            $table->unsignedInteger('removed_qty')->default(0);
            $table->unsignedInteger('materialized_qty')->default(0);
            $table->timestamps();

            $table->unique(
                ['component_instance_id', 'component_definition_subcomponent_template_id'],
                'component_expected_subcomponent_state_unique'
            );
            $table->foreign('component_instance_id', 'component_expected_subcomponent_state_component_fk')
                ->references('id')
                ->on('component_instances')
                ->cascadeOnDelete();
            $table->foreign('component_definition_subcomponent_template_id', 'component_expected_subcomponent_state_template_fk')
                ->references('id')
                ->on('component_definition_subcomponent_templates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_expected_subcomponent_states');
    }
};
