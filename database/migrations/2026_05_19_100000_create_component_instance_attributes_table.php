<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_instance_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_instance_id')->constrained('component_instances')->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->text('raw_value')->nullable();
            $table->foreignId('attribute_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $table->boolean('resolves_to_spec')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['component_instance_id', 'attribute_definition_id'], 'component_instance_attr_unique');
            $table->index(['attribute_definition_id', 'sort_order'], 'component_instance_attr_definition_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_instance_attributes');
    }
};
