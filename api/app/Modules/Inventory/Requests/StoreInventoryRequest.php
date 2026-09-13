<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid'],
            'product_variant_id' => ['required', 'integer'],
            'inventory_item_id' => ['nullable', 'string', 'max:255'],
            'shopify_location_id' => ['nullable', 'string', 'max:255'],
            'available' => ['required', 'integer'],
            'committed' => ['sometimes', 'integer'],
            'incoming' => ['sometimes', 'integer'],
            'reserved' => ['sometimes', 'integer'],
            'on_hand' => ['sometimes', 'integer'],
            'shopify_updated_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
