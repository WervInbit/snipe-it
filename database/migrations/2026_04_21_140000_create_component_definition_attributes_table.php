<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_definition_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_definition_id')->constrained('component_definitions')->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->text('raw_value')->nullable();
            $table->foreignId('attribute_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['component_definition_id', 'attribute_definition_id'], 'component_def_attr_unique');
            $table->index(['attribute_definition_id', 'sort_order'], 'component_def_attr_definition_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_definition_attributes');
    }
};
