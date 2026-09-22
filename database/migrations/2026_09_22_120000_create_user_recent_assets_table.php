<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_recent_assets', function (Blueprint $table): void {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('asset_id');
            $table->timestamp('last_activity_at');

            $table->primary(['user_id', 'asset_id']);
            $table->index(['user_id', 'last_activity_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_recent_assets');
    }
};
