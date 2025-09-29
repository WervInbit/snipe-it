<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->string('label');
            $table->string('datatype');
            $table->string('unit')->nullable();
            $table->boolean('required_for_category')->default(false);
            $table->boolean('needs_test')->default(false);
            $table->boolean('allow_custom_values')->default(false);
            $table->boolean('allow_asset_override')->default(false);
            $table->json('constraints')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attribute_definition_category', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_definition_id');
            $table->unsignedInteger('category_id');
            $table->timestamps();

            $table->primary(['attribute_definition_id', 'category_id']);
            $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attribute_definition_id');
            $table->string('value');
            $table->string('label');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->cascadeOnDelete();
            $table->unique(['attribute_definition_id', 'value']);
        });

        Schema::create('model_number_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('model_id');
            $table->unsignedBigInteger('attribute_definition_id');
            $table->text('value');
            $table->text('raw_value')->nullable();
            $table->unsignedBigInteger('attribute_option_id')->nullable();
            $table->timestamps();

            $table->unique(['model_id', 'attribute_definition_id'], 'model_number_attr_model_definition_unique');
            $table->foreign('model_id')->references('id')->on('models')->cascadeOnDelete();
            $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->cascadeOnDelete();
            $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->nullOnDelete();
        });

        Schema::create('asset_attribute_overrides', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('asset_id');
            $table->unsignedBigInteger('attribute_definition_id');
            $table->text('value');
            $table->text('raw_value')->nullable();
            $table->unsignedBigInteger('attribute_option_id')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'attribute_definition_id'], 'asset_override_asset_definition_unique');
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('attribute_definition_id')->references('id')->on('attribute_definitions')->cascadeOnDelete();
            $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_attribute_overrides');
        Schema::dropIfExists('model_number_attributes');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_definition_category');
        Schema::dropIfExists('attribute_definitions');
    }
};
