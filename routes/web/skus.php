<?php

use App\Http\Controllers\SkusController;
use Illuminate\Support\Facades\Route;

Route::resource('skus', SkusController::class, [
    'middleware' => ['auth'],
]);
