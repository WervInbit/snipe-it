<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('test_run_id');
            $table->unsignedInteger('test_type_id');
            $table->enum('status', ['pass', 'fail', 'nvt']);
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('test_run_id')->references('id')->on('test_runs')->onDelete('cascade');
            $table->foreign('test_type_id')->references('id')->on('test_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_results');
    }
};
