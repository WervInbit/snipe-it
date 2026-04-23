<?php

use App\Http\Controllers\Components;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'components',
    'middleware' => ['auth'],
], function (): void {
    Route::get('tray', [Components\ComponentWorkflowController::class, 'tray'])
        ->name('components.tray');

    Route::get('{component_id}/remove-to-tray', [Components\ComponentWorkflowController::class, 'createRemoveToTray'])
        ->name('components.remove_to_tray.create');

    Route::post('{component_id}/remove-to-tray', [Components\ComponentWorkflowController::class, 'removeToTray'])
        ->name('components.remove_to_tray');

    Route::get('{component_id}/install', [Components\ComponentWorkflowController::class, 'createInstall'])
        ->name('components.install.create');

    Route::post('{component_id}/install', [Components\ComponentWorkflowController::class, 'install'])
        ->name('components.install');

    Route::get('{component_id}/move-to-stock', [Components\ComponentWorkflowController::class, 'createMoveToStock'])
        ->name('components.move_to_stock.create');

    Route::post('{component_id}/move-to-stock', [Components\ComponentWorkflowController::class, 'moveToStock'])
        ->name('components.move_to_stock');

    Route::get('{component_id}/flag-needs-verification', [Components\ComponentWorkflowController::class, 'createFlagNeedsVerification'])
        ->name('components.flag_needs_verification.create');

    Route::post('{component_id}/flag-needs-verification', [Components\ComponentWorkflowController::class, 'flagNeedsVerification'])
        ->name('components.flag_needs_verification');

    Route::get('{component_id}/confirm-verification', [Components\ComponentWorkflowController::class, 'createConfirmVerification'])
        ->name('components.confirm_verification.create');

    Route::post('{component_id}/confirm-verification', [Components\ComponentWorkflowController::class, 'confirmVerification'])
        ->name('components.confirm_verification');

    Route::get('{component_id}/mark-destruction-pending', [Components\ComponentWorkflowController::class, 'createMarkDestructionPending'])
        ->name('components.mark_destruction_pending.create');

    Route::post('{component_id}/mark-destruction-pending', [Components\ComponentWorkflowController::class, 'markDestructionPending'])
        ->name('components.mark_destruction_pending');

    Route::post('{component_id}/mark-defective', [Components\ComponentWorkflowController::class, 'markDefective'])
        ->name('components.mark_defective');

    Route::get('{component_id}/mark-destroyed', [Components\ComponentWorkflowController::class, 'createMarkDestroyed'])
        ->name('components.mark_destroyed.create');

    Route::post('{component_id}/mark-destroyed', [Components\ComponentWorkflowController::class, 'markDestroyed'])
        ->name('components.mark_destroyed');
});

Route::resource('components', Components\ComponentsController::class, [
    'middleware' => ['auth'],
    'parameters' => ['components' => 'component_id'],
]);
