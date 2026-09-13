<?php

namespace App\Modules\User\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'note' => ['sometimes', 'nullable', 'string'],
            'verified_email' => ['sometimes', 'boolean'],
            'tax_exempt' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:20'],
            'default_address_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}


