<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_runs') && !Schema::hasColumn('workflow_runs', 'readiness_context_hash')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                $table->string('readiness_context_hash', 64)->nullable();
            });
        }

        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'tests_completed_ok')) {
            DB::table('assets')->update(['tests_completed_ok' => false]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_runs') && Schema::hasColumn('workflow_runs', 'readiness_context_hash')) {
            Schema::table('workflow_runs', function (Blueprint $table): void {
                $table->dropColumn('readiness_context_hash');
            });
        }
    }
};
