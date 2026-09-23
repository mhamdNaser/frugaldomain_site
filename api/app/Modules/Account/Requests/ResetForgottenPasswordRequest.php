<?php

namespace App\Modules\Account\Requests;

class ResetForgottenPasswordRequest extends AccountRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|max:190|confirmed',
            'altcha' => 'required|string|max:2048',
            'website' => 'nullable|max:0',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'code.required' => 'Enter the 6-digit code from the email.',
            'code.digits' => 'Enter the 6-digit code from the email.',
            'password.required' => 'Choose a new password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
            'altcha.required' => 'The security check did not finish. Try again.',
            'website.max' => 'The security check failed. Try again.',
        ];
    }
}
