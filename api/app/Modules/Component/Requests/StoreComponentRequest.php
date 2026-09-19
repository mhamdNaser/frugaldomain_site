<?php

namespace App\Modules\Component\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already behind auth:sanctum + role:admin
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            // The slug becomes a folder name, so it is constrained to the same
            // character set the storage guard accepts.
            'slug' => [
                $required,
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('components', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'name' => [$required, 'string', 'max:190'],
            'name_ar' => ['nullable', 'string', 'max:190'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],

            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'version' => ['nullable', 'string', 'max:40'],
            'accent' => ['nullable', 'string', 'max:32'],

            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:60'],
            'stack' => ['sometimes', 'array'],
            'stack.*' => ['string', 'max:60'],

            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:500'],

            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:component_categories,id'],

            'preview_theme' => ['sometimes', Rule::in(['auto', 'light', 'dark'])],
            'preview_height' => ['sometimes', 'integer', 'min:160', 'max:1600'],
            'ordering' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, digits and single hyphens.',
        ];
    }
}
