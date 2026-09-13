<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shopify_variant_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'numeric'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric'],
            'inventory_quantity' => ['sometimes', 'nullable', 'integer'],
            'position' => ['sometimes', 'nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'availableForSale' => ['sometimes', 'boolean'],
            'taxable' => ['sometimes', 'boolean'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer', 'exists:option_values,id'],
        ];
    }
}

