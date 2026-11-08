<?php

use App\Modules\Learning\Controllers\LearnController;
use App\Modules\Learning\Controllers\LearningAdminController;
use Illuminate\Support\Facades\Route;

/*
 * Learning platform. Public catalogue under /api/learn, the course builder
 * under /api/admin/learn. See app/Modules/Learning/README.md.
 */
Route::prefix('admin/learn')
    ->middleware(['auth:sanctum', 'role:admin|super-admin'])
    ->controller(LearningAdminController::class)
    ->group(function () {
        Route::get('categories', 'categories');
        Route::post('categories', 'storeCategory');
        Route::put('categories/{id}', 'updateCategory')->whereNumber('id');
        Route::delete('categories/{id}', 'destroyCategory')->whereNumber('id');

        Route::get('courses', 'courses');
        Route::post('courses', 'storeCourse');
        Route::get('courses/{id}', 'course')->whereNumber('id');
        Route::put('courses/{id}', 'updateCourse')->whereNumber('id');
        Route::delete('courses/{id}', 'destroyCourse')->whereNumber('id');
        Route::post('courses/{id}/cover', 'uploadCover')->whereNumber('id');
        Route::delete('courses/{id}/cover', 'deleteCover')->whereNumber('id');
        Route::get('courses/{id}/export', 'export')->whereNumber('id');
        Route::post('import', 'import');

        Route::post('stages', 'storeStage');
        Route::put('stages/{id}', 'updateStage')->whereNumber('id');
        Route::delete('stages/{id}', 'destroyStage')->whereNumber('id');

        Route::post('units', 'storeUnit');
        Route::put('units/{id}', 'updateUnit')->whereNumber('id');
        Route::delete('units/{id}', 'destroyUnit')->whereNumber('id');

        Route::get('lessons/{id}', 'lesson')->whereNumber('id');
        Route::post('lessons', 'storeLesson');
        Route::put('lessons/{id}', 'updateLesson')->whereNumber('id');
        Route::post('lessons/{id}/duplicate', 'duplicateLesson')->whereNumber('id');
        Route::delete('lessons/{id}', 'destroyLesson')->whereNumber('id');

        Route::post('reorder', 'reorder');

        Route::get('media', 'media');
        Route::post('media', 'uploadMedia');
        Route::delete('media/{id}', 'destroyMedia')->whereNumber('id');
    });

Route::prefix('learn')->controller(LearnController::class)->group(function () {
    Route::get('sitemap.xml', 'sitemap');
    Route::get('categories', 'categories');
    Route::get('courses', 'courses');
    Route::get('courses/{slug}', 'course');
    Route::get('courses/{slug}/cover', 'cover');
    Route::get('courses/{slug}/lessons/{lesson}', 'lesson');
    Route::get('media/{file}', 'media')->where('file', '[0-9]+(\.[a-z0-9]+)?');
});
