<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = Setting::first();

        foreach (Asset::all() as $asset) {
            $testRunId = DB::table('test_runs')->insertGetId([
                'asset_id' => $asset->id,
                'run_name' => 'Initial Run',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('test_results')->insert([
                'test_run_id' => $testRunId,
                'result_name' => 'Boot Diagnostics',
                'status' => 'pass',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($settings && $settings->label2_2d_type !== 'none') {
                $size = Helper::barcodeDimensions($settings->label2_2d_type);
                $barcodeDir = public_path('/uploads/barcodes');
                if (! is_dir($barcodeDir)) {
                    mkdir($barcodeDir, 0755, true);
                }

                $qrFile = $barcodeDir.'/qr-'.Str::slug($asset->asset_tag).'-'.Str::slug((string) $asset->id).'.png';

                if (! file_exists($qrFile)) {
                    $barcode = new \Com\Tecnick\Barcode\Barcode();
                    $barcodeObj = $barcode->getBarcodeObj(
                        $settings->label2_2d_type,
                        route('hardware.show', $asset->id),
                        $size['height'],
                        $size['width'],
                        'black',
                        [-2, -2, -2, -2]
                    );
                    file_put_contents($qrFile, $barcodeObj->getPngData());
                }
            }
        }
    }
}

