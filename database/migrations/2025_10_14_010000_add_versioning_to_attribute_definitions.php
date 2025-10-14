<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('attribute_definitions', 'key')) {
                $table->dropUnique('attribute_definitions_key_unique');
            }

            $table->unsignedInteger('version')->default(1)->after('id');
            $table->unsignedBigInteger('supersedes_attribute_id')->nullable()->after('version');
            $table->timestamp('deprecated_at')->nullable()->after('allow_asset_override');
            $table->timestamp('hidden_at')->nullable()->after('deprecated_at');

            $table->foreign('supersedes_attribute_id')
                ->references('id')
                ->on('attribute_definitions')
                ->nullOnDelete();

            $table->unique(['key', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table) {
            $table->dropForeign(['supersedes_attribute_id']);
            $table->dropColumn([
                'version',
                'supersedes_attribute_id',
                'deprecated_at',
                'hidden_at',
            ]);

            $table->unique('key');
        });
    }
};
