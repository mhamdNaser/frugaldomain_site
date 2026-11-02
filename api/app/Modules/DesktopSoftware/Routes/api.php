<?php

use App\Modules\DesktopSoftware\Controllers\DesktopSoftwareController;
use Illuminate\Support\Facades\Route;

Route::prefix('desktop-apps')->controller(DesktopSoftwareController::class)->group(function () {
    Route::get('/', 'index')->name('desktop-apps.index');
    Route::get('{slug}', 'show')->name('desktop-apps.show');
    Route::get('{slug}/download', 'download')->name('desktop-apps.download');
});
