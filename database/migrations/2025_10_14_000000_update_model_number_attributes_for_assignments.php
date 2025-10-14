<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_number_attributes', function (Blueprint $table) {
            if (!Schema::hasColumn('model_number_attributes', 'display_order')) {
                $table->integer('display_order')->default(0)->after('attribute_option_id');
            }
        });

        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });

        Schema::table('model_number_attributes', function (Blueprint $table) {
            $table->text('value')->nullable(false)->change();
        });
    }
};
