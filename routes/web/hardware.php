<?php

use App\Http\Controllers\MaintenancesController;
use App\Http\Controllers\Assets\AssetsController;
use App\Http\Controllers\Assets\BulkAssetsController;
use App\Http\Controllers\TestRunController;
use App\Http\Controllers\TestResultController;
use App\Http\Controllers\AssetTestController;
use App\Http\Controllers\AssetImageController;
use Tabuna\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Route;
use App\Models\Asset;

/*
|--------------------------------------------------------------------------
| Asset Routes
|--------------------------------------------------------------------------
|
| Register all the asset routes.
|
*/
Route::group(
    [
        'prefix' => 'hardware',
        'middleware' => ['auth'], 
    ],
    
    function () {
        
        Route::get('requested', [AssetsController::class, 'getRequestedIndex'])
            ->name('assets.requested')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('hardware.index')
                ->push(trans('admin/hardware/general.requested'), route('assets.requested'))
            );

        Route::get('history', [AssetsController::class, 'getImportHistory'])
            ->name('asset.import-history')
            ->breadcrumbs(fn (Trail $trail) =>
                $trail->parent('hardware.index')
                ->push(trans('general.import-history'), route('asset.import-history'))
            );

        Route::post('history',
            [AssetsController::class, 'postImportHistory']
        )->name('asset.process-import-history');

        Route::get('bytag/{any?}',
            [AssetsController::class, 'getAssetByTag']
        )->where('any', '.*')->name('findbytag/hardware');

        Route::get('byserial/{any?}',
            [AssetsController::class, 'getAssetBySerial']
        )->where('any', '.*')->name('findbyserial/hardware');

        Route::get('{asset}/clone',
            [AssetsController::class, 'getClone']
        )->name('clone/hardware')->withTrashed();

        Route::get('{assetId}/label',
            [AssetsController::class, 'getLabel']
        )->name('label/hardware');

        // Redirect old legacy /asset_id/view urls to the resource route version
        Route::get('{assetId}/view', function ($assetId) {
            return redirect()->route('hardware.show', $assetId);
        });

        Route::get('{asset}/barcode',
            [AssetsController::class, 'getBarCode']
        )->name('barcode/hardware')->withTrashed();

        Route::post('{asset}/restore',
            [AssetsController::class, 'getRestore']
        )->name('restore/hardware')->withTrashed();

        Route::patch('{asset}/sale-toggle', [AssetsController::class, 'toggleSaleAvailability'])
            ->name('hardware.toggle-sale');

        Route::patch('{asset}/internal-use-toggle', [AssetsController::class, 'toggleInternalUse'])
            ->name('hardware.toggle-internal');

        Route::post(
            'bulkedit',
            [BulkAssetsController::class, 'edit']
        )->name('hardware/bulkedit');

        Route::post(
            'bulkdelete',
            [BulkAssetsController::class, 'destroy']
        )->name('hardware/bulkdelete');

        Route::post(
            'bulkrestore',
            [BulkAssetsController::class, 'restore']
        )->name('hardware/bulkrestore');

        Route::post(
            'bulksave',
            [BulkAssetsController::class, 'update']
        )->name('hardware/bulksave');

        // Asset tests
        Route::get('{asset}/tests', [TestRunController::class, 'index'])
            ->name('test-runs.index');
        Route::post('{asset}/tests', [TestRunController::class, 'store'])
            ->name('test-runs.store');
        Route::delete('{asset}/tests/{testRun}', [TestRunController::class, 'destroy'])
            ->name('test-runs.destroy');
        Route::get('{asset}/tests/{testRun}/results/edit', [TestResultController::class, 'edit'])
            ->name('test-results.edit');
        Route::put('{asset}/tests/{testRun}/results', [TestResultController::class, 'update'])
            ->name('test-results.update');

        // Asset images
        Route::post('{asset}/images', [AssetImageController::class, 'store'])
            ->name('asset-images.store');
        Route::put('{asset}/images/{assetImage}', [AssetImageController::class, 'update'])
            ->name('asset-images.update');
        Route::delete('{asset}/images/{assetImage}', [AssetImageController::class, 'destroy'])
            ->name('asset-images.destroy');

        // Asset individual tests
        Route::get('{asset}/asset-tests', [AssetTestController::class, 'index'])
            ->name('asset-tests.index');
        Route::get('{asset}/asset-tests/create', [AssetTestController::class, 'create'])
            ->name('asset-tests.create');
        Route::post('{asset}/asset-tests', [AssetTestController::class, 'store'])
            ->name('asset-tests.store');
        Route::get('{asset}/asset-tests/{assetTest}/edit', [AssetTestController::class, 'edit'])
            ->name('asset-tests.edit');
        Route::put('{asset}/asset-tests/{assetTest}', [AssetTestController::class, 'update'])
            ->name('asset-tests.update');
        Route::delete('{asset}/asset-tests/{assetTest}', [AssetTestController::class, 'destroy'])
            ->name('asset-tests.destroy');
        Route::get('{asset}/asset-tests/{assetTest}/repeat', [AssetTestController::class, 'repeatForm'])
            ->name('asset-tests.repeat.form');
        Route::post('{asset}/asset-tests/{assetTest}/repeat', [AssetTestController::class, 'repeat'])
            ->name('asset-tests.repeat');

    });

Route::resource('hardware',
        AssetsController::class,
        ['middleware' => ['auth']
])->parameters(['hardware' => 'asset'])->withTrashed();


// Asset Maintenances
Route::resource('maintenances',
    MaintenancesController::class, [
        'parameters' => ['maintenance' => 'maintenance', 'asset' => 'asset_id'],
    ]);

Route::get('ht/{any?}',
    [AssetsController::class, 'getAssetByTag']
)->where('any', '.*')->name('ht/assetTag');
