<?php

use App\Modules\Rating\Controllers\SiteRatingController;
use Illuminate\Support\Facades\Route;

Route::get('ratings', [SiteRatingController::class, 'show'])->middleware('throttle:60,1');
Route::post('ratings', [SiteRatingController::class, 'store'])->middleware('throttle:10,1');
