<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attribute_definitions', 'component_aggregation_mode')) {
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->string('component_aggregation_mode')
                    ->default('sum')
                    ->after('component_spec_display_mode');
            });
        }

        DB::table('attribute_definitions')
            ->where('key', 'ram_speed_mhz')
            ->update(['component_aggregation_mode' => 'distinct']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('attribute_definitions', 'component_aggregation_mode')) {
            Schema::table('attribute_definitions', function (Blueprint $table): void {
                $table->dropColumn('component_aggregation_mode');
            });
        }
    }
};
