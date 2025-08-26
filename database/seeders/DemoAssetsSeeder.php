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
        $assets = [
            ['asset_tag' => 'ASSET-0001', 'name' => 'Macbook Pro 13"', 'category' => 'Laptops', 'manufacturer' => 'Apple'],
            ['asset_tag' => 'ASSET-0002', 'name' => 'Macbook Air', 'category' => 'Laptops', 'manufacturer' => 'Apple'],
            ['asset_tag' => 'ASSET-0003', 'name' => 'Surface', 'category' => 'Laptops', 'manufacturer' => 'Microsoft'],
            ['asset_tag' => 'ASSET-0004', 'name' => 'iPad', 'category' => 'Tablets', 'manufacturer' => 'Apple'],
            ['asset_tag' => 'ASSET-0005', 'name' => 'iPhone 12', 'category' => 'Mobile Phones', 'manufacturer' => 'Apple'],
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
