<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\TestType;
use App\Models\User;
use App\Services\QrLabelService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $demoAssets = [
            ['asset_tag' => 'ASSET-0001', 'model' => 'Macbook Pro 13"'],
            ['asset_tag' => 'ASSET-0002', 'model' => 'Macbook Air'],
            ['asset_tag' => 'ASSET-0003', 'model' => 'Surface'],
            ['asset_tag' => 'ASSET-0004', 'model' => 'iPad'],
            ['asset_tag' => 'ASSET-0005', 'model' => 'iPhone 12'],
        ];

        $user = User::first();
        $testType = TestType::first();
        $labelService = app(QrLabelService::class);

        foreach ($demoAssets as $data) {
            $model = AssetModel::where('name', $data['model'])->first();
            $asset = Asset::factory()->create([
                'asset_tag' => $data['asset_tag'],
                'name' => $data['model'],
                'model_id' => $model?->id ?? AssetModel::factory()->create(['name' => $data['model']])->id,
            ]);

            $testRunId = DB::table('test_runs')->insertGetId([
                'asset_id' => $asset->id,
                'user_id' => $user?->id,
                'started_at' => now(),
                'finished_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('test_results')->insert([
                'test_run_id' => $testRunId,
                'test_type_id' => $testType?->id,
                'status' => 'pass',
                'note' => 'Initial test run',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                $labelService->generate($asset);
            } catch (\Throwable $e) {
                // Ignore QR generation errors (e.g. missing imagick)
            }
        }
    }
}
