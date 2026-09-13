<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMetafieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metafieldable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metafieldable_id' => ['sometimes', 'nullable', 'integer'],
            'namespace' => ['sometimes', 'nullable', 'string', 'max:255'],
            'key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'value' => ['sometimes', 'nullable'],
            'type' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}


