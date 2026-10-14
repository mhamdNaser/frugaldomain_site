<?php

use App\Modules\Convert\Controllers\ConvertController;
use Illuminate\Support\Facades\Route;

/*
 * Conversions that cannot be done in the browser.
 *
 * Public, like the converters themselves - a visitor has never needed an
 * account to convert a file here - but rate limited, because each one costs
 * real cpu time on a small machine.
 */
Route::prefix('convert')->controller(ConvertController::class)->group(function () {
    Route::get('formats', 'formats');
    Route::post('document', 'document')->middleware('throttle:convert');
    Route::post('audio', 'audio')->middleware('throttle:convert');
});
