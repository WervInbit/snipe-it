<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('test_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('auditable');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('field');
            $table->text('before')->nullable();
            $table->text('after')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_audits');
    }
};
