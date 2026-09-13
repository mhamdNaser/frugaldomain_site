<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['sometimes', 'integer'],
            'inventory_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_location_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'available' => ['sometimes', 'integer'],
            'committed' => ['sometimes', 'integer'],
            'incoming' => ['sometimes', 'integer'],
            'reserved' => ['sometimes', 'integer'],
            'on_hand' => ['sometimes', 'integer'],
            'shopify_updated_at' => ['sometimes', 'nullable', 'date'],
            'raw_payload' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
