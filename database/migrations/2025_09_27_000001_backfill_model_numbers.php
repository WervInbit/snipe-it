<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $models = DB::table('models')->select('id', 'model_number')->get();

            foreach ($models as $model) {
                $code = $model->model_number ?: 'UNSPECIFIED-' . $model->id;

                $numberId = DB::table('model_numbers')->insertGetId([
                    'model_id' => $model->id,
                    'code' => $code,
                    'label' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('models')
                    ->where('id', $model->id)
                    ->update(['primary_model_number_id' => $numberId]);

                DB::table('assets')
                    ->where('model_id', $model->id)
                    ->update(['model_number_id' => $numberId]);

                DB::table('model_number_attributes')
                    ->where('model_id', $model->id)
                    ->update(['model_number_id' => $numberId]);
            }
        });

        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
            $table->dropUnique('model_number_attr_model_definition_unique');
            $table->dropColumn('model_id');
            $table->unique(['model_number_id', 'attribute_definition_id'], 'model_number_attr_definition_unique');
        });
    }

    public function down(): void
    {
        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->unsignedInteger('model_id')->nullable()->after('model_number_id');
            $table->foreign('model_id')->references('id')->on('models')->cascadeOnDelete();
            $table->unique(['model_id', 'attribute_definition_id'], 'model_number_attr_model_definition_unique');
            $table->dropUnique('model_number_attr_definition_unique');
        });

        DB::transaction(function () {
            $numbers = DB::table('model_numbers')->select('id', 'model_id', 'code')->get();

            foreach ($numbers as $number) {
                DB::table('model_number_attributes')
                    ->where('model_number_id', $number->id)
                    ->update(['model_id' => $number->model_id]);

                DB::table('assets')
                    ->where('model_number_id', $number->id)
                    ->update(['model_number_id' => null]);

                DB::table('models')
                    ->where('primary_model_number_id', $number->id)
                    ->update(['primary_model_number_id' => null, 'model_number' => $number->code]);
            }
        });
    }
};
