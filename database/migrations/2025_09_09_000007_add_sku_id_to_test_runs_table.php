<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            $table->unsignedInteger('sku_id')->nullable()->after('asset_id');
            $table->foreign('sku_id')->references('id')->on('skus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            $table->dropForeign(['sku_id']);
            $table->dropColumn('sku_id');
        });
    }
};
