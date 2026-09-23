<?php

namespace App\Modules\Account\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for the account requests: every route that uses one is either public
 * by design (sign-up) or already behind auth:sanctum, so authorisation is
 * the route's job; and a failure answers in the same JSON shape as the rest
 * of the API.
 *
 * Messages are fixed English sentences on purpose. The site translates them
 * on the client, keyed by that text, the same way it translates its own
 * interface - so they must not be reworded casually.
 */
abstract class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
