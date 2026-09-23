<?php

namespace App\Modules\Account\Requests;

class RegisterAccountRequest extends AccountRequest
{
    public function rules(): array
    {
        return [
            // `name` is the public display name and unique across accounts.
            'name' => 'required|string|min:2|max:60|unique:users,name',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:190|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name.',
            'name.min' => 'The name must be at least 2 characters.',
            'name.max' => 'The name must be at most 60 characters.',
            'name.unique' => 'That name is already taken.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'An account with this email already exists.',
            'password.required' => 'Choose a password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
        ];
    }
}
