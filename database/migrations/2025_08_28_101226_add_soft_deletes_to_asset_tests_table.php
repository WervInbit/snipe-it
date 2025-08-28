<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Adds a nullable deleted_at column used by Eloquent SoftDeletes
    public function up(): void
    {
        Schema::table('asset_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_tests', 'deleted_at')) {
                $table->softDeletes(); // creates nullable timestamp 'deleted_at'
            }
        });
    }

    // Rollback: drop the column if it exists
    public function down(): void
    {
        Schema::table('asset_tests', function (Blueprint $table) {
            if (Schema::hasColumn('asset_tests', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
