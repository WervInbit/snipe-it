<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'image_override_enabled')) {
                $table->boolean('image_override_enabled')->default(false)->after('image');
            }
        });

        Schema::table('asset_images', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_images', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('caption');
            }

            if (!Schema::hasColumn('asset_images', 'source')) {
                $table->string('source', 32)->default('asset_upload')->after('sort_order');
            }

            if (!Schema::hasColumn('asset_images', 'source_photo_id')) {
                $table->unsignedInteger('source_photo_id')->nullable()->after('source');
            }
        });

        if (Schema::hasTable('test_result_photos') && Schema::hasColumn('asset_images', 'source_photo_id')) {
            Schema::table('asset_images', function (Blueprint $table) {
                $table->foreign('source_photo_id')
                    ->references('id')
                    ->on('test_result_photos')
                    ->nullOnDelete();
            });
        }

        Schema::table('asset_images', function (Blueprint $table) {
            $table->index(['asset_id', 'sort_order', 'id'], 'asset_images_asset_sort_idx');
        });

        Schema::create('model_number_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_number_id');
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('model_number_id')
                ->references('id')
                ->on('model_numbers')
                ->cascadeOnDelete();
            $table->index(['model_number_id', 'sort_order', 'id'], 'model_number_images_sort_idx');
        });

        DB::table('assets')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->update(['image_override_enabled' => true]);

        $assetImages = DB::table('asset_images')
            ->orderBy('asset_id')
            ->orderBy('id')
            ->get(['id', 'asset_id']);

        $currentAssetId = null;
        $order = 0;
        foreach ($assetImages as $image) {
            if ($currentAssetId !== $image->asset_id) {
                $currentAssetId = $image->asset_id;
                $order = 0;
            }

            DB::table('asset_images')
                ->where('id', $image->id)
                ->update(['sort_order' => $order]);

            $order++;
        }

        $models = DB::table('models')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->get(['id', 'name', 'image', 'primary_model_number_id']);

        foreach ($models as $model) {
            $modelNumberId = $model->primary_model_number_id;

            if (!$modelNumberId) {
                $modelNumberId = DB::table('model_numbers')
                    ->where('model_id', $model->id)
                    ->orderBy('id')
                    ->value('id');
            }

            if (!$modelNumberId) {
                continue;
            }

            $filePath = 'models/'.$model->image;

            $exists = DB::table('model_number_images')
                ->where('model_number_id', $modelNumberId)
                ->where('file_path', $filePath)
                ->exists();

            if ($exists) {
                continue;
            }

            $maxOrder = (int) DB::table('model_number_images')
                ->where('model_number_id', $modelNumberId)
                ->max('sort_order');

            DB::table('model_number_images')->insert([
                'model_number_id' => $modelNumberId,
                'file_path' => $filePath,
                'caption' => $model->name ? 'Default: '.$model->name : 'Default model image',
                'sort_order' => $maxOrder + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('model_number_images')) {
            Schema::dropIfExists('model_number_images');
        }

        if (Schema::hasTable('asset_images')) {
            if (Schema::hasColumn('asset_images', 'source_photo_id')) {
                Schema::table('asset_images', function (Blueprint $table) {
                    $table->dropForeign(['source_photo_id']);
                });
            }

            Schema::table('asset_images', function (Blueprint $table) {
                $table->dropIndex('asset_images_asset_sort_idx');
            });

            Schema::table('asset_images', function (Blueprint $table) {
                if (Schema::hasColumn('asset_images', 'source_photo_id')) {
                    $table->dropColumn('source_photo_id');
                }

                if (Schema::hasColumn('asset_images', 'source')) {
                    $table->dropColumn('source');
                }

                if (Schema::hasColumn('asset_images', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }

        if (Schema::hasColumn('assets', 'image_override_enabled')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('image_override_enabled');
            });
        }
    }
};
