<?php

use App\Http\Controllers\Components;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'components',
    'middleware' => ['auth'],
], function (): void {
    Route::get('tray', [Components\ComponentWorkflowController::class, 'tray'])
        ->name('components.tray');

    Route::post('{component_id}/remove-to-tray', [Components\ComponentWorkflowController::class, 'removeToTray'])
        ->name('components.remove_to_tray');

    Route::post('{component_id}/install', [Components\ComponentWorkflowController::class, 'install'])
        ->name('components.install');

    Route::post('{component_id}/move-to-stock', [Components\ComponentWorkflowController::class, 'moveToStock'])
        ->name('components.move_to_stock');

    Route::post('{component_id}/flag-needs-verification', [Components\ComponentWorkflowController::class, 'flagNeedsVerification'])
        ->name('components.flag_needs_verification');

    Route::post('{component_id}/confirm-verification', [Components\ComponentWorkflowController::class, 'confirmVerification'])
        ->name('components.confirm_verification');

    Route::post('{component_id}/mark-destruction-pending', [Components\ComponentWorkflowController::class, 'markDestructionPending'])
        ->name('components.mark_destruction_pending');

    Route::post('{component_id}/mark-destroyed', [Components\ComponentWorkflowController::class, 'markDestroyed'])
        ->name('components.mark_destroyed');
});

Route::resource('components', Components\ComponentsController::class, [
    'middleware' => ['auth'],
    'parameters' => ['components' => 'component_id'],
]);
