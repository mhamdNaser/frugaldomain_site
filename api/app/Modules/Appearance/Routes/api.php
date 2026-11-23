<?php

use App\Modules\Appearance\Controllers\AppearanceController;
use Illuminate\Support\Facades\Route;

Route::get('site/appearance', [AppearanceController::class, 'show']);

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/appearance')->group(function () {
    Route::put('theme', [AppearanceController::class, 'updateTheme']);
    Route::put('home-sections', [AppearanceController::class, 'updateHomeSections']);
});
