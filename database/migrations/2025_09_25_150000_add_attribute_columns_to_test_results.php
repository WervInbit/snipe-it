<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_definition_id')->nullable()->after('test_type_id');
            $table->text('expected_value')->nullable()->after('status');
            $table->text('expected_raw_value')->nullable()->after('expected_value');

            $table->foreign('attribute_definition_id')
                ->references('id')
                ->on('attribute_definitions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropForeign(['attribute_definition_id']);
            $table->dropColumn(['attribute_definition_id', 'expected_value', 'expected_raw_value']);
        });
    }
};
