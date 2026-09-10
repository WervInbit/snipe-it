<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifier_write_locks', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('state')->default(false);
        });
        DB::table('identifier_write_locks')->insert(['id' => 1, 'state' => false]);
        Schema::table('component_instances', function (Blueprint $table) {
            $table->dropUnique('component_instances_component_tag_unique');
            $table->index('component_tag');
        });
    }

    public function down(): void
    {
        if (
            DB::table('component_instances')->select('component_tag')
                ->groupBy('component_tag')->havingRaw('COUNT(*) > 1')->exists()
        ) {
            throw new RuntimeException('Cannot restore unique component tags while confirmed duplicates exist.');
        }
        Schema::table('component_instances', function (Blueprint $table) {
            $table->dropIndex('component_instances_component_tag_index');
            $table->unique('component_tag');
        });
        Schema::dropIfExists('identifier_write_locks');
    }
};
