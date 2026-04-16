<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('test_types', 'display_order')) {
            Schema::table('test_types', function (Blueprint $table) {
                $table->unsignedInteger('display_order')->default(0)->after('slug');
                $table->index(['display_order', 'id'], 'test_types_display_order_idx');
            });
        }

        $orderedIds = DB::table('test_types')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($orderedIds as $position => $id) {
            DB::table('test_types')
                ->where('id', $id)
                ->update(['display_order' => $position]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('test_types', 'display_order')) {
            Schema::table('test_types', function (Blueprint $table) {
                $table->dropIndex('test_types_display_order_idx');
                $table->dropColumn('display_order');
            });
        }
    }
};

