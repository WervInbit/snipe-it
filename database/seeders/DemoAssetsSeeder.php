<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Services\QrLabelService;

class DemoAssetsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the demo remains small and focused for testing
        // Purge existing assets created by other seeders
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['test_results','test_runs','test_audits','asset_tests','asset_logs','asset_images','asset_status_history','checkout_requests','assets'] as $table) {
            \Illuminate\Support\Facades\DB::statement("TRUNCATE TABLE `{$table}`");
        }
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Minimal HP-focused demo
        $assets = [
            ['asset_tag' => 'HP-0001', 'name' => 'HP Spectre 13', 'category' => 'Laptops', 'manufacturer' => 'HP'],
            ['asset_tag' => 'HP-0002', 'name' => 'HP EliteBook 840', 'category' => 'Laptops', 'manufacturer' => 'HP'],
            ['asset_tag' => 'HP-0003', 'name' => 'HP ProBook 450', 'category' => 'Laptops', 'manufacturer' => 'HP'],
            ['asset_tag' => 'HP-0004', 'name' => 'HP Envy 15', 'category' => 'Laptops', 'manufacturer' => 'HP'],
        ];

        $user = User::first();
        if ($user) {
            Auth::login($user);
        }

        foreach ($assets as $data) {
            $category = Category::firstWhere('name', $data['category']);
            $manufacturer = Manufacturer::firstWhere('name', $data['manufacturer']);

            $model = AssetModel::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category_id' => $category?->id,
                    'manufacturer_id' => $manufacturer?->id,
                ]
            );

            $asset = Asset::factory()->create([
                'asset_tag' => $data['asset_tag'],
                'name'       => $data['name'],
                'model_id'   => $model->id,
            ]);

            // Generate initial QR labels for the asset
            app(QrLabelService::class)->generate($asset);

            // Create a baseline test run with results
            $run = TestRun::factory()->create([
                'asset_id' => $asset->id,
                'user_id'  => $user?->id,
            ]);

            $types = TestType::inRandomOrder()->take(3)->get();
            foreach ($types as $type) {
                $result = TestResult::factory()->create([
                    'test_run_id' => $run->id,
                    'test_type_id' => $type->id,
                    'status' => TestResult::STATUS_FAIL,
                ]);

                // Update the result to log an audit entry representing an edit
                $result->update(['status' => TestResult::STATUS_PASS]);
            }
        }

        if ($user) {
            Auth::logout();
        }
    }
}
