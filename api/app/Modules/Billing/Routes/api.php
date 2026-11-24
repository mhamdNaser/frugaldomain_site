<?php

use App\Modules\Billing\Controllers\PaymentTransactionController;
use App\Modules\Billing\Controllers\PlanController;
use App\Modules\Billing\Controllers\RefundController;
use App\Modules\Billing\Controllers\RefundItemController;
use App\Modules\Billing\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {
        Route::controller(PaymentTransactionController::class)->group(function () {
            Route::post('allPaymentTransactions', 'index');
            Route::get('payment-transactions/{id}', 'show');
        });

        Route::controller(RefundController::class)->group(function () {
            Route::post('allRefunds', 'index');
            Route::get('refunds/{id}', 'show');
        });

        Route::controller(RefundItemController::class)->group(function () {
            Route::post('allRefundItems', 'index');
            Route::get('refund-items/{id}', 'show');
        });
    });

    // Store owner: choose a plan and pay for it.
    Route::middleware(['auth:sanctum', 'role:partner'])->controller(\App\Modules\Billing\Controllers\CheckoutController::class)->group(function () {
        Route::get('billing/plans', 'plans');
        Route::post('billing/checkout', 'checkout')->middleware('throttle:10,1');
    });

    // Plans and subscriptions are platform billing settings: super admin only.
    // A store owner sees their own plan through GET /admin/my-store.
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::controller(PlanController::class)->group(function () {
            Route::post('allPlans', 'index');
            Route::get('plans/{id}', 'show');
            Route::post('plans', 'store');
            Route::put('plans/{id}', 'update');
            Route::delete('plans/{id}', 'destroy');
            Route::patch('plans/{id}/status', 'changeStatus');
        });

        Route::controller(SubscriptionController::class)->group(function () {
            Route::post('allSubscriptions', 'index');
            Route::get('subscriptions/{id}', 'show');
            Route::put('subscriptions/{id}', 'update');
        });

        Route::controller(\App\Modules\Billing\Controllers\PaymentMethodController::class)->group(function () {
            Route::get('billing/drivers', 'drivers');
            Route::get('payment-methods', 'index');
            Route::post('payment-methods', 'store');
            Route::put('payment-methods/{id}', 'update');
            Route::delete('payment-methods/{id}', 'destroy');
            Route::put('plans/{planId}/payment-methods', 'syncPlan');
        });
        Route::post('subscriptions/{id}/confirm', [\App\Modules\Billing\Controllers\CheckoutController::class, 'confirm']);
    });
});