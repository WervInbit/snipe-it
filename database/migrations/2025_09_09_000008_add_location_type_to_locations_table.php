<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('location_type', 20)->default('warehouse')->after('parent_id');
        });

        DB::table('locations')->whereNotNull('parent_id')->update(['location_type' => 'shelf']);
        DB::statement("UPDATE locations SET location_type = 'bin' WHERE parent_id IN (SELECT id FROM locations WHERE parent_id IS NOT NULL)");
    }

    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('location_type');
        });
    }
};
