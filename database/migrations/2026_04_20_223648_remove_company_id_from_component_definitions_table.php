<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('component_definitions', 'company_id')) {
            Schema::table('component_definitions', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('component_definitions', 'company_id')) {
            Schema::table('component_definitions', function (Blueprint $table) {
                $table->unsignedInteger('company_id')->nullable()->after('manufacturer_id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }
    }
};
