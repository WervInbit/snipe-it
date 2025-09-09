<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('test_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('auditable_type');
            $table->unsignedInteger('auditable_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('field');
            $table->text('before')->nullable();
            $table->text('after')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_audits');
    }
};
