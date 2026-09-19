<?php

use App\Modules\Component\Controllers\ComponentCategoryController;
use App\Modules\Component\Controllers\ComponentController;
use App\Modules\Component\Controllers\ComponentFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public catalogue
|--------------------------------------------------------------------------
| Only published components are ever returned. The gallery endpoint also
| carries the active categories so the filter bar renders in one paint.
*/

Route::get('components', [ComponentController::class, 'publicIndex'])->name('components.public.index');
Route::get('component-categories', [ComponentCategoryController::class, 'publicIndex'])->name('components.categories.public');
Route::get('components/{slug}', [ComponentController::class, 'publicShow'])->name('components.public.show');
Route::post('components/{slug}/download', [ComponentController::class, 'registerDownload'])->name('components.public.download');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Guarded exactly like the App module's admin routes.
*/

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::controller(ComponentController::class)->group(function () {
        Route::post('all-components', 'index')->name('admin.components.index');
        Route::get('components/{id}', 'show')->name('admin.components.show');
        Route::post('components', 'store')->name('admin.components.store');
        Route::put('components/{id}', 'update')->name('admin.components.update');
        Route::delete('components/{id}', 'destroy')->name('admin.components.destroy');
        Route::patch('components/{id}/status', 'toggleStatus')->name('admin.components.status');
    });

    Route::controller(ComponentFileController::class)->group(function () {
        Route::post('components/{id}/file', 'store')->name('admin.components.file.store');
        Route::delete('components/{id}/file', 'destroy')->name('admin.components.file.destroy');
    });

    Route::controller(ComponentCategoryController::class)->group(function () {
        Route::post('all-component-categories', 'index')->name('admin.component-categories.index');
        Route::post('component-categories', 'store')->name('admin.component-categories.store');
        Route::put('component-categories/{id}', 'update')->name('admin.component-categories.update');
        Route::delete('component-categories/{id}', 'destroy')->name('admin.component-categories.destroy');
    });
});
