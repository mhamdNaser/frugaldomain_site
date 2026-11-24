<?php

use App\Modules\Fulfillment\Controllers\FulfillmentController;
use App\Modules\Fulfillment\Controllers\FulfillmentItemController;
use App\Modules\Fulfillment\Controllers\FulfillmentOrderController;
use App\Modules\Fulfillment\Controllers\FulfillmentOrderItemController;
use App\Modules\Fulfillment\Controllers\FulfillmentServiceController;
use App\Modules\Fulfillment\Controllers\FulfillmentTrackingController;
use App\Modules\Fulfillment\Controllers\ReverseFulfillmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::controller(FulfillmentController::class)->group(function () {
            Route::post('allFulfillments', 'index');
            Route::get('fulfillments/{id}', 'show');
        });

        Route::controller(FulfillmentOrderController::class)->group(function () {
            Route::post('allFulfillmentOrders', 'index');
            Route::get('fulfillment-orders/{id}', 'show');
        });

        Route::controller(FulfillmentServiceController::class)->group(function () {
            Route::post('allFulfillmentServices', 'index');
            Route::get('fulfillment-services/{id}', 'show');
        });

        Route::controller(ReverseFulfillmentController::class)->group(function () {
            Route::post('allReverseFulfillments', 'index');
            Route::get('reverse-fulfillments/{id}', 'show');
        });

        Route::controller(FulfillmentItemController::class)->group(function () {
            Route::post('allFulfillmentItems', 'index');
            Route::get('fulfillment-items/{id}', 'show');
        });

        Route::controller(FulfillmentOrderItemController::class)->group(function () {
            Route::post('allFulfillmentOrderItems', 'index');
            Route::get('fulfillment-order-items/{id}', 'show');
        });

        Route::controller(FulfillmentTrackingController::class)->group(function () {
            Route::post('allFulfillmentTracking', 'index');
            Route::get('fulfillment-tracking/{id}', 'show');
        });
    });
});
