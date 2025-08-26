<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Manufacturer;

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

            Asset::factory()->create([
                'asset_tag' => $data['asset_tag'],
                'name' => $data['name'],
                'model_id' => $model->id,
            ]);
        }
    }
}
