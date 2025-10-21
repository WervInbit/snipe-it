<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_types', function (Blueprint $table) {
            if (!Schema::hasColumn('test_types', 'attribute_definition_id')) {
                $table->unsignedBigInteger('attribute_definition_id')
                    ->nullable()
                    ->after('id');
                $table->foreign('attribute_definition_id')
                    ->references('id')
                    ->on('attribute_definitions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('test_types', 'instructions')) {
                $table->text('instructions')->nullable()->after('tooltip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_types', function (Blueprint $table) {
            if (Schema::hasColumn('test_types', 'attribute_definition_id')) {
                $table->dropForeign(['attribute_definition_id']);
                $table->dropColumn('attribute_definition_id');
            }

            if (Schema::hasColumn('test_types', 'instructions')) {
                $table->dropColumn('instructions');
            }
        });
    }
};
