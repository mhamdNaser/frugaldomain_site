<?php

use App\Modules\Form\Controllers\FormController;
use App\Modules\Form\Controllers\FormSubmissionController;
use App\Modules\Form\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

/*
 * Forms, in two halves.
 *
 * The account half is for whoever built the form: no role middleware, because
 * every query is scoped to the caller's own id and there is no privilege to
 * check - any signed-in person may build forms.
 *
 * The public half is the shared link. It is open by necessity, which is why it
 * is the only part of this module that is rate-limited by address, carries a
 * honeypot and a page token, and returns nothing about the owner.
 */

Route::middleware('auth:sanctum')->prefix('account')->group(function () {
    // The field catalogue. Served from the server's own registry so the
    // builder can never offer a type the validator does not know.
    Route::get('form-field-types', [FormController::class, 'types']);

    // Ready-made forms, and creating one from them.
    Route::get('form-templates', [FormController::class, 'templates']);
    Route::post('form-templates/{key}', [FormController::class, 'fromTemplate'])
        ->middleware('throttle:form-write')
        ->where('key', '[a-z0-9-]+');

    Route::get('forms', [FormController::class, 'index']);
    Route::post('forms', [FormController::class, 'store'])->middleware('throttle:form-write');

    Route::prefix('forms/{id}')->whereNumber('id')->group(function () {
        Route::get('/', [FormController::class, 'show']);
        Route::put('/', [FormController::class, 'update'])->middleware('throttle:form-write');
        Route::patch('status', [FormController::class, 'setStatus'])->middleware('throttle:form-write');
        Route::delete('/', [FormController::class, 'destroy']);

        Route::get('submissions', [FormSubmissionController::class, 'index']);
        // Before the {submissionId} route, or "export" would be read as an id.
        Route::get('submissions/export', [FormSubmissionController::class, 'export'])
            ->middleware('throttle:form-export');
        // Every answer as JSON, for the spreadsheet the browser builds.
        Route::get('submissions/rows', [FormSubmissionController::class, 'rows'])
            ->middleware('throttle:form-export');
        Route::delete('submissions/{submissionId}', [FormSubmissionController::class, 'destroy'])
            ->whereNumber('submissionId');

        // A picture someone attached. Private: only the form's owner, and
        // only through this route.
        Route::get('submissions/{submissionId}/files/{key}', [FormSubmissionController::class, 'file'])
            ->whereNumber('submissionId')
            ->where('key', '[a-z0-9_]+');
    });
});

/*
 * The shared link. `f` is short on purpose: this is the url people paste into
 * a message, and it accepts either the slug or the uuid.
 */
Route::get('f/{handle}', [PublicFormController::class, 'show'])->middleware('throttle:form-view');
Route::post('f/{handle}', [PublicFormController::class, 'submit'])->middleware('throttle:form-submit');
