<?php

/**
 * Create generic SKUs for existing laptop and desktop assets lacking one
 * and associate those assets with the new SKU. This assumes a one-to-one
 * mapping between an asset model and its generic SKU named identically to
 * the model.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Asset;
use App\Models\Sku;

return new class extends Migration {
    public function up(): void
    {
        Asset::with(['model.category'])
            ->whereNull('sku_id')
            ->chunkById(100, function ($assets) {
                foreach ($assets as $asset) {
                    $model = $asset->model;
                    if (!$model || !$model->category) {
                        continue;
                    }

                    $slug = Str::singular(Str::slug($model->category->name));
                    if (!in_array($slug, ['laptop', 'desktop'])) {
                        continue;
                    }

                    $sku = Sku::firstOrCreate(
                        ['model_id' => $model->id, 'name' => $model->name]
                    );

                    $asset->sku()->associate($sku);
                    $asset->save();
                }
            });
    }

    public function down(): void
    {
        $genericSkuIds = DB::table('skus')
            ->join('models', 'skus.model_id', '=', 'models.id')
            ->whereColumn('skus.name', 'models.name')
            ->pluck('skus.id');

        DB::table('assets')->whereIn('sku_id', $genericSkuIds)->update(['sku_id' => null]);
        DB::table('skus')->whereIn('id', $genericSkuIds)->delete();
    }
};
