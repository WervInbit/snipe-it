<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_types', function (Blueprint $table) {
            if (!Schema::hasColumn('test_types', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_types', function (Blueprint $table) {
            if (Schema::hasColumn('test_types', 'is_required')) {
                $table->dropColumn('is_required');
            }
        });
    }
};
