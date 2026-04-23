<?php

use App\Http\Controllers\MaintenancesController;
use App\Http\Controllers\Assets\AssetsController;
use App\Http\Controllers\Assets\AssetComponentsController;
use App\Http\Controllers\Assets\AssetLabelPrintController;
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

        Route::patch('{asset}/status', [AssetsController::class, 'updateStatus'])
            ->name('hardware.status.update');

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
        Route::get('{asset}/tests/active', [TestResultController::class, 'active'])
            ->name('test-results.active')
            ->breadcrumbs(fn (Trail $trail, Asset $asset) =>
                $trail->parent('hardware.show', $asset)
                    ->push(trans('tests.tests'), route('test-results.active', $asset))
            );
        Route::post('{asset}/tests', [TestRunController::class, 'store'])
            ->name('test-runs.store');
        Route::delete('{asset}/tests/{testRun}', [TestRunController::class, 'destroy'])
            ->name('test-runs.destroy');
        Route::get('{asset}/tests/{testRun}/results/edit', [TestResultController::class, 'edit'])
            ->name('test-results.edit');
        Route::put('{asset}/tests/{testRun}/results', [TestResultController::class, 'update'])
            ->name('test-results.update');
        Route::post('{asset}/tests/{testRun}/results/{result}', [TestResultController::class, 'partialUpdate'])
            ->name('test-results.partial-update');
        Route::post('{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote', [TestResultController::class, 'promotePhoto'])
            ->name('test-results.photos.promote');

        Route::post('{asset}/print-label', [AssetLabelPrintController::class, 'store'])
            ->name('hardware.print-label');

        // Asset images
        Route::post('{asset}/images', [AssetImageController::class, 'store'])
            ->name('asset-images.store');
        Route::put('{asset}/images/{assetImage}', [AssetImageController::class, 'update'])
            ->name('asset-images.update');
        Route::delete('{asset}/images/{assetImage}', [AssetImageController::class, 'destroy'])
            ->name('asset-images.destroy');

        Route::get('{asset}/components/add', [AssetComponentsController::class, 'add'])
            ->name('hardware.components.add');
        Route::post('{asset}/components/install', [AssetComponentsController::class, 'install'])
            ->name('hardware.components.install');
        Route::post('{asset}/components/install-tray', [AssetComponentsController::class, 'installFromTray'])
            ->name('hardware.components.install-tray');
        Route::post('{asset}/components/install-existing', [AssetComponentsController::class, 'installExisting'])
            ->name('hardware.components.install-existing');
        Route::post('{asset}/components/register', [AssetComponentsController::class, 'register'])
            ->name('hardware.components.register');
        Route::post('{asset}/components/expected/{template}/to-tray', [AssetComponentsController::class, 'expectedToTray'])
            ->name('hardware.components.expected.tray');

        Route::get('{asset}/components/expected/{template}/to-storage', [AssetComponentsController::class, 'createExpectedStorage'])
            ->name('hardware.components.expected.storage.create');
        Route::post('{asset}/components/expected/{template}/to-storage', [AssetComponentsController::class, 'storeExpectedStorage'])
            ->name('hardware.components.expected.storage.store');
        Route::get('{asset}/components/{component}/to-storage', [AssetComponentsController::class, 'createTrackedStorage'])
            ->name('hardware.components.storage.create');
        Route::post('{asset}/components/{component}/to-storage', [AssetComponentsController::class, 'storeTrackedStorage'])
            ->name('hardware.components.storage.store');

        Route::get('{asset}/components/expected/{template}/move', [AssetComponentsController::class, 'createExpectedTransfer'])
            ->name('hardware.components.expected.transfer.create');
        Route::post('{asset}/components/expected/{template}/move', [AssetComponentsController::class, 'storeExpectedTransfer'])
            ->name('hardware.components.expected.transfer.store');
        Route::get('{asset}/components/{component}/move', [AssetComponentsController::class, 'createTrackedTransfer'])
            ->name('hardware.components.transfer.create');
        Route::post('{asset}/components/{component}/move', [AssetComponentsController::class, 'storeTrackedTransfer'])
            ->name('hardware.components.transfer.store');

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
