<?php

use App\Modules\Drawing\Controllers\CreatorPointsController;
use App\Modules\Drawing\Controllers\DrawingController;
use App\Modules\Drawing\Controllers\DrawingGalleryController;
use App\Modules\Drawing\Controllers\AdminTemplateController;
use App\Modules\Drawing\Controllers\DrawingModerationController;
use App\Modules\Drawing\Controllers\DrawingTemplateController;
use Illuminate\Support\Facades\Route;

/*
 * Drawing routes.
 *
 * Grouped by who may reach them, so the access rule for any endpoint is
 * visible in one place rather than scattered through the controllers.
 */

// ---- Public: the gallery of approved drawings --------------------------- //
Route::prefix('drawings')->group(function () {
    Route::get('/gallery', [DrawingGalleryController::class, 'index']);
    Route::get('/gallery/{slug}', [DrawingGalleryController::class, 'show']);
});

// ---- Public: the starter templates -------------------------------------- //
// Open on purpose: a template is site-owned content, and requiring an account
// before someone can start from one would defeat the point of shipping them.
Route::prefix('drawing-templates')->group(function () {
    Route::get('/', [DrawingTemplateController::class, 'index']);
    Route::get('/{template}', [DrawingTemplateController::class, 'show']);
});

// ---- Signed in: a user's own drawings ----------------------------------- //
Route::middleware('auth:sanctum')->prefix('drawings')->group(function () {
    Route::get('/', [DrawingController::class, 'index']);
    Route::post('/', [DrawingController::class, 'store']);
    Route::get('/{drawing}', [DrawingController::class, 'show']);
    Route::put('/{drawing}', [DrawingController::class, 'update']);
    Route::delete('/{drawing}', [DrawingController::class, 'destroy']);

    // Publishing is a request, never an act: these only move the drawing in
    // and out of the review queue.
    Route::post('/{drawing}/submit', [DrawingController::class, 'submit']);
    Route::post('/{drawing}/withdraw', [DrawingController::class, 'withdraw']);

    // Credit another creator for a drawing placed on this canvas.
    Route::post('/{drawing}/use', [DrawingController::class, 'recordUsage']);
});

// ---- Signed in: what the creator has earned ----------------------------- //
Route::middleware('auth:sanctum')->prefix('creator-points')->group(function () {
    Route::get('/', [CreatorPointsController::class, 'summary']);
    Route::get('/entries', [CreatorPointsController::class, 'entries']);
});

// ---- Admin: managing the starter templates ------------------------------ //
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/drawing-templates')->group(function () {
    Route::get('/', [AdminTemplateController::class, 'index']);
    Route::post('/', [AdminTemplateController::class, 'store']);
    Route::get('/{template}', [AdminTemplateController::class, 'show']);
    Route::put('/{template}', [AdminTemplateController::class, 'update']);
    Route::delete('/{template}', [AdminTemplateController::class, 'destroy']);
    Route::post('/{template}/toggle', [AdminTemplateController::class, 'toggle']);
});

// ---- Admin: the review queue -------------------------------------------- //
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/drawings')->group(function () {
    Route::get('/queue', [DrawingModerationController::class, 'queue']);
    Route::get('/{drawing}', [DrawingModerationController::class, 'show']);
    Route::post('/{drawing}/approve', [DrawingModerationController::class, 'approve']);
    Route::post('/{drawing}/reject', [DrawingModerationController::class, 'reject']);
    Route::post('/{drawing}/unpublish', [DrawingModerationController::class, 'unpublish']);
});
