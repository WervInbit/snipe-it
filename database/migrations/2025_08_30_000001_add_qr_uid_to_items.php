<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'assets', 'accessories', 'consumables', 'components'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'qr_uid')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->uuid('qr_uid')->nullable()->unique()->after('id');
                });
            }
        }

        // Backfill asset qr_uid best-effort
        if (Schema::hasTable('assets')) {
            DB::table('assets')->whereNull('qr_uid')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('assets')->where('id', $row->id)->update(['qr_uid' => (string) Str::uuid()]);
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'assets', 'accessories', 'consumables', 'components'
        ];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'qr_uid')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('qr_uid');
                });
            }
        }
    }
};

