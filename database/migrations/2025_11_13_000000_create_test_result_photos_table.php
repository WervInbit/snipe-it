<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_result_photos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('test_result_id');
            $table->string('path');
            $table->timestamps();

            $table->foreign('test_result_id')
                ->references('id')
                ->on('test_results')
                ->onDelete('cascade');
        });

        // Backfill existing single-photo records
        DB::table('test_results')
            ->whereNotNull('photo_path')
            ->orderBy('id')
            ->chunkById(500, function ($results) {
                $insert = [];
                $now = now();
                foreach ($results as $result) {
                    $insert[] = [
                        'test_result_id' => $result->id,
                        'path' => $result->photo_path,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($insert)) {
                    DB::table('test_result_photos')->insert($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_result_photos');
    }
};
