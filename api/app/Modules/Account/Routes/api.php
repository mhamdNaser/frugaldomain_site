<?php

use App\Modules\Account\Controllers\AccountAuthController;
use App\Modules\Account\Controllers\AccountLibraryController;
use App\Modules\Account\Controllers\AccountProfileController;
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
    ->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->prefix('account')->group(function () {
    Route::post('logout', [AccountAuthController::class, 'logout']);

    Route::get('me', [AccountProfileController::class, 'me']);
    Route::get('overview', [AccountProfileController::class, 'overview']);
    Route::put('profile', [AccountProfileController::class, 'update']);
    Route::put('password', [AccountProfileController::class, 'changePassword'])->middleware('throttle:6,1');
    Route::delete('/', [AccountProfileController::class, 'destroy'])->middleware('throttle:3,1');

    Route::get('icons/favorites', [AccountLibraryController::class, 'favoriteIcons']);
    Route::post('icons/favorites/{iconId}', [AccountLibraryController::class, 'toggleFavoriteIcon'])
        ->whereNumber('iconId');
    Route::get('icons/downloads', [AccountLibraryController::class, 'downloads']);

    Route::get('components', [AccountLibraryController::class, 'savedComponents']);
    Route::get('components/ids', [AccountLibraryController::class, 'savedComponentIds']);
    Route::post('components/{componentId}', [AccountLibraryController::class, 'toggleSavedComponent'])
        ->whereNumber('componentId');
});
