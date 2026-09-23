<?php

namespace App\Modules\Account\Requests;

class ChangePasswordRequest extends AccountRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string|current_password:sanctum',
            'password' => 'required|string|min:8|max:190|confirmed|different:current_password',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Enter your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'Choose a new password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
            'password.different' => 'The new password must be different from the current one.',
        ];
    }
}
