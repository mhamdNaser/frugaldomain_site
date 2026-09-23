<?php

use App\Modules\Account\Controllers\AccountAuthController;
use App\Modules\Account\Controllers\AccountLibraryController;
use App\Modules\Account\Controllers\AccountProfileController;
use App\Modules\Account\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
 * The account area: what any signed-in person can do with their own account.
 *
 * Kept apart from the admin routes on purpose. Nothing here carries a role
 * middleware, because nothing here reaches beyond the caller's own rows -
 * every query is scoped to $request->user(). The admin panel's routes stay
 * behind role:admin / role:partner and are not reused from here.
 */

// Sign-up is public, and throttled: it creates rows and sends nothing back
// but a token, so an unthrottled one is an open invitation to fill the table.
Route::post('account/register', [AccountAuthController::class, 'register'])
    ->middleware('throttle:account-register');

// Forgotten password. Each step is throttled by IP here; the service adds a
// per-address limit, and every submission needs a solved proof-of-work.
Route::prefix('account/password')->controller(PasswordResetController::class)->group(function () {
    Route::get('challenge', 'challenge')->middleware('throttle:password-challenge');
    Route::post('forgot', 'sendCode')->middleware('throttle:password-forgot');
    Route::post('reset', 'reset')->middleware('throttle:password-reset');
});

Route::middleware('auth:sanctum')->prefix('account')->group(function () {
    Route::post('logout', [AccountAuthController::class, 'logout']);

    Route::get('me', [AccountProfileController::class, 'me']);
    Route::get('overview', [AccountProfileController::class, 'overview']);
    Route::put('profile', [AccountProfileController::class, 'update']);
    Route::put('password', [AccountProfileController::class, 'changePassword'])->middleware('throttle:account-password');
    Route::delete('/', [AccountProfileController::class, 'destroy'])->middleware('throttle:account-delete');

    Route::get('icons/favorites', [AccountLibraryController::class, 'favoriteIcons']);
    Route::post('icons/favorites/{iconId}', [AccountLibraryController::class, 'toggleFavoriteIcon'])
        ->whereNumber('iconId');
    Route::get('icons/downloads', [AccountLibraryController::class, 'downloads']);

    Route::get('components', [AccountLibraryController::class, 'savedComponents']);
    Route::get('components/ids', [AccountLibraryController::class, 'savedComponentIds']);
    Route::post('components/{componentId}', [AccountLibraryController::class, 'toggleSavedComponent'])
        ->whereNumber('componentId');
});
