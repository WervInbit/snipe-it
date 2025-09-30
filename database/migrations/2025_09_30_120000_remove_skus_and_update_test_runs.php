<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('test_runs', 'sku_id')) {
            Schema::table('test_runs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['sku_id']);
                } catch (\Throwable $e) {
                    // Ignore missing foreign key constraints.
                }

                $table->dropColumn('sku_id');
            });
        }

        if (!Schema::hasColumn('test_runs', 'model_number_id')) {
            Schema::table('test_runs', function (Blueprint $table) {
                $table->unsignedBigInteger('model_number_id')->nullable()->after('asset_id');
                $table->foreign('model_number_id')->references('id')->on('model_numbers')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('assets', 'sku_id')) {
            Schema::table('assets', function (Blueprint $table) {
                try {
                    $table->dropForeign(['sku_id']);
                } catch (\Throwable $e) {
                    // Ignore missing foreign key constraints.
                }

                $table->dropColumn('sku_id');
            });
        }

        if (Schema::hasTable('skus')) {
            Schema::dropIfExists('skus');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('test_runs', 'model_number_id')) {
            Schema::table('test_runs', function (Blueprint $table) {
                $table->dropForeign(['model_number_id']);
                $table->dropColumn('model_number_id');
            });
        }

        if (!Schema::hasTable('skus')) {
            Schema::create('skus', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('model_id');
                $table->string('name');
                $table->timestamps();

                $table->foreign('model_id')->references('id')->on('models')->cascadeOnDelete();
                $table->unique(['model_id', 'name']);
            });
        }

        if (!Schema::hasColumn('assets', 'sku_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->unsignedBigInteger('sku_id')->nullable()->after('model_id');
                $table->foreign('sku_id')->references('id')->on('skus')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('test_runs', 'sku_id')) {
            Schema::table('test_runs', function (Blueprint $table) {
                $table->unsignedBigInteger('sku_id')->nullable()->after('asset_id');
                $table->foreign('sku_id')->references('id')->on('skus')->nullOnDelete();
            });
        }
    }
};
