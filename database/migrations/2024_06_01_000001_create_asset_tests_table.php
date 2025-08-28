<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->dateTime('performed_at');
            $table->string('status');
            $table->boolean('needs_cleaning')->default(false);
            $table->text('notes')->nullable();
$table->unsignedInteger('created_by')->nullable();
$table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

$table->unsignedInteger('updated_by')->nullable();
$table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_tests');
    }
};
