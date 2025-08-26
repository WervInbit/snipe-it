<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_audits', function (Blueprint $table) {
            $table->id();
            $table->morphs('testable');
            $table->string('event');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_audits');
    }
};
