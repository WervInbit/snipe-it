<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('old_status_id')->nullable();
            $table->unsignedBigInteger('new_status_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('old_status_id')->references('id')->on('status_labels');
            $table->foreign('new_status_id')->references('id')->on('status_labels');
            $table->foreign('changed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_status_history');
    }
};

