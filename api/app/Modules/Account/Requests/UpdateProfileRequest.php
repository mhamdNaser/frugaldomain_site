<?php

namespace App\Modules\Account\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends AccountRequest
{
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('users', 'name')->ignore($userId)],
            'first_name' => 'nullable|string|max:60',
            'last_name' => 'nullable|string|max:60',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
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
        ];
    }
}
