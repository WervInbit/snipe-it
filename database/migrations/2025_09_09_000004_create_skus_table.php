<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('model_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skus');
    }
};
