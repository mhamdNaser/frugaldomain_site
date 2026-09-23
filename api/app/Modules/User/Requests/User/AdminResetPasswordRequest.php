<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * An administrator setting a new password for someone who has lost theirs.
 * The route is behind role:admin; this only checks the password itself.
 */
class AdminResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|max:190|confirmed',
            // Signing the user out everywhere is the default: a reset is
            // usually asked for because someone else may have the old one.
            'revoke_sessions' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Choose a new password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
        ];
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
