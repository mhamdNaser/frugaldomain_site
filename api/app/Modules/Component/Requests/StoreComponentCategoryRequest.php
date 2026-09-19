<?php

namespace App\Modules\Component\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComponentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'slug' => [
                $required,
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('component_categories', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'name' => [$required, 'string', 'max:190'],
            'name_ar' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'accent' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'ordering' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
