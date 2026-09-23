<?php

namespace App\Modules\Account\Requests;

class ForgotPasswordRequest extends AccountRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:190',
            'altcha' => 'required|string|max:2048',
            // A field hidden from people. A form-filling bot fills it in.
            'website' => 'nullable|max:0',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'altcha.required' => 'The security check did not finish. Try again.',
            'website.max' => 'The security check failed. Try again.',
        ];
    }
}
