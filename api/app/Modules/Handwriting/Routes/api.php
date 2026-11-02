<?php

use App\Modules\Handwriting\Controllers\HandwritingController;
use Illuminate\Support\Facades\Route;

Route::post('handwriting/recognize', [HandwritingController::class, 'recognize'])
    ->middleware('throttle:convert');
