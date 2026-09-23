<?php

namespace App\Modules\Account\Requests;

class DeleteAccountRequest extends AccountRequest
{
    public function rules(): array
    {
        return [
            // Deleting is irreversible, so it asks for the password again
            // rather than trusting a token that may be on a shared machine.
            'password' => 'required|string|current_password:sanctum',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Enter your password to confirm.',
            'password.current_password' => 'The password is incorrect.',
        ];
    }
}
