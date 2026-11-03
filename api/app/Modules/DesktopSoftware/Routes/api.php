<?php

use App\Modules\DesktopSoftware\Controllers\DesktopSoftwareController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/desktop-apps')
    ->middleware(['auth:sanctum', 'role:admin|super-admin'])
    ->controller(DesktopSoftwareController::class)
    ->group(function () {
        Route::get('/', 'adminIndex');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
        Route::post('{id}/file', 'uploadFile');
        Route::delete('{id}/file', 'deleteFile');
    });

Route::prefix('desktop-apps')->controller(DesktopSoftwareController::class)->group(function () {
    Route::get('/', 'index')->name('desktop-apps.index');
    Route::get('{slug}', 'show')->name('desktop-apps.show');
    Route::get('{slug}/download', 'download')->name('desktop-apps.download');
});
