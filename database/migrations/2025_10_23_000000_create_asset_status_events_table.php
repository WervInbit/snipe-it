<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id');
            $table->unsignedInteger('from_status_id')->nullable();
            $table->unsignedInteger('to_status_id')->nullable();
            $table->unsignedInteger('triggered_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('from_status_id')->references('id')->on('status_labels')->onDelete('set null');
            $table->foreign('to_status_id')->references('id')->on('status_labels')->onDelete('set null');
            $table->foreign('triggered_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['asset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_status_events');
    }
};

