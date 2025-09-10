<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id');
            $table->unsignedInteger('old_status_id')->nullable();
            $table->unsignedInteger('new_status_id');
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamp('changed_at');

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('old_status_id')->references('id')->on('status_labels');
            $table->foreign('new_status_id')->references('id')->on('status_labels');
            $table->foreign('changed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_status_history');
    }
};

