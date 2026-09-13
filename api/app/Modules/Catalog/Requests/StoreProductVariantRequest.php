<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'shopify_variant_id' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'compare_at_price' => ['nullable', 'numeric'],
            'inventory_quantity' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'availableForSale' => ['sometimes', 'boolean'],
            'taxable' => ['sometimes', 'boolean'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer', 'exists:option_values,id'],
        ];
    }
}

