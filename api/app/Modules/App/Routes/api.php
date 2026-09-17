<?php

use App\Modules\App\Controllers\AppController;
use App\Modules\App\Controllers\AppMediaController;
use App\Modules\App\Controllers\AppSubdomainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public catalogue
|--------------------------------------------------------------------------
| These two replace the static public/json/apps.json the site used to fetch.
| Only published apps are ever returned.
*/

Route::get('apps', [AppController::class, 'publicIndex'])->name('apps.public.index');
Route::get('apps/{slug}', [AppController::class, 'publicShow'])->name('apps.public.show');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Guarded exactly like the Icon module's admin routes: sanctum session plus
| the admin role.
*/

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::controller(AppController::class)->group(function () {
            Route::post('apps/all', 'index');
            Route::get('apps/{id}', 'show');
            Route::post('apps', 'store');
            Route::put('apps/{id}', 'update');
            Route::delete('apps/{id}', 'destroy');
            Route::patch('apps/{id}/status', 'changeStatus');
        });

        Route::controller(AppMediaController::class)->group(function () {
            Route::post('apps/{id}/archive', 'uploadArchive');
            Route::delete('apps/{id}/archive', 'destroyArchive');
            Route::post('apps/{id}/images', 'uploadImage');
            Route::delete('apps/{id}/images/{imageId}', 'destroyImage');
        });

        // Automatic subdomain provisioning through the Hostinger hosting API.
        // Safe to expose with no token configured: the endpoints then answer
        // with configured:false rather than failing.
        Route::controller(AppSubdomainController::class)->group(function () {
            Route::post('apps/{id}/subdomain', 'store');
            Route::get('apps/{id}/subdomain/status', 'status');
            Route::delete('apps/{id}/subdomain', 'destroy');
        });
    });
});
