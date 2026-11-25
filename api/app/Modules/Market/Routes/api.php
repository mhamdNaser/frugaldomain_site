<?php

use App\Modules\Market\Controllers\MarketAdminController;
use App\Modules\Market\Controllers\MarketController;
use Illuminate\Support\Facades\Route;

// Public catalogue.
Route::prefix('market')->controller(MarketController::class)->group(function () {
    Route::get('items', 'index');
    Route::get('payment-methods', 'paymentMethods');
    Route::get('items/{slug}', 'show');
    Route::get('items/{slug}/logo', 'logo');
    Route::get('items/{slug}/download', 'download')->middleware(['signed', 'throttle:30,1'])->name('market.download');

    // Buying needs an account.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('items/{slug}/checkout', 'checkout')->middleware('throttle:10,1');
        Route::post('items/{slug}/download-link', 'downloadLink')->middleware('throttle:30,1');
        Route::post('purchases/{id}/verify', 'verify')->middleware('throttle:20,1');
        Route::post('purchases/{id}/proof', 'proof')->middleware('throttle:10,1');
    });
});

Route::middleware('auth:sanctum')->get('account/purchases', [MarketController::class, 'myPurchases']);

// Super admin.
Route::prefix('admin/market')->middleware(['auth:sanctum', 'role:admin'])->controller(MarketAdminController::class)->group(function () {
    Route::get('items', 'index');
    Route::post('items', 'store');
    Route::put('items/{id}', 'update');
    Route::delete('items/{id}', 'destroy');
    Route::post('items/{id}/file', 'uploadFile');
    Route::post('items/{id}/logo', 'uploadLogo');
    Route::post('items/{id}/sync', 'sync')->middleware('throttle:10,1');
    Route::get('purchases', 'purchases');
    Route::post('purchases/{id}/confirm', 'confirm');
    Route::get('purchases/{id}/receipt', 'receipt');
});
