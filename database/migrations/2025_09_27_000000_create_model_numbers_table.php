<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('model_id');
            $table->string('code');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('models')->cascadeOnDelete();
            $table->unique(['model_id', 'code']);
        });

        Schema::table('models', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_model_number_id')->nullable()->after('model_number');
            $table->foreign('primary_model_number_id')->references('id')->on('model_numbers')->nullOnDelete();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('model_number_id')->nullable()->after('model_id');
            $table->foreign('model_number_id')->references('id')->on('model_numbers')->nullOnDelete();
        });

        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('model_number_id')->nullable()->after('model_id');
            $table->foreign('model_number_id')->references('id')->on('model_numbers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->dropForeign(['model_number_id']);
            $table->dropColumn('model_number_id');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['model_number_id']);
            $table->dropColumn('model_number_id');
        });

        Schema::table('models', function (Blueprint $table) {
            $table->dropForeign(['primary_model_number_id']);
            $table->dropColumn('primary_model_number_id');
        });

        Schema::dropIfExists('model_numbers');
    }
};
