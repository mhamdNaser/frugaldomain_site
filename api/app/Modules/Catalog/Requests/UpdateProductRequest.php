<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'handle' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'archived'])],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['sometimes', 'nullable', 'integer', 'exists:vendors,id'],
            'product_type_id' => ['sometimes', 'nullable', 'integer', 'exists:product_types,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer', 'exists:options,id'],
            'isGiftCard' => ['sometimes', 'boolean'],
            'hasOnlyDefaultVariant' => ['sometimes', 'boolean'],
        ];
    }
}

