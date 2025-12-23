<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_test_type', function (Blueprint $table) {
            $table->unsignedInteger('test_type_id');
            $table->unsignedInteger('category_id');

            $table->primary(['test_type_id', 'category_id']);
            $table->index('category_id');

            $table->foreign('test_type_id')
                ->references('id')
                ->on('test_types')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_test_type');
    }
};
