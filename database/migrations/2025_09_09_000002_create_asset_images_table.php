<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_images', function (Blueprint $table) {
            $table->id();
            // The assets table uses an unsigned integer primary key, so
            // we need to ensure the foreign key column matches its type
            // to avoid MySQL foreign key constraint errors.
            $table->unsignedInteger('asset_id');
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_images');
    }
};
