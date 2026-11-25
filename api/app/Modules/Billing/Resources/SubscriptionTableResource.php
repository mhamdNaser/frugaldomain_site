<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan?->name,
            'store_name' => $this->store?->name ?: $this->store?->shopify_domain,
            'payment_method' => $this->paymentMethod?->name,
            'payment_driver' => $this->paymentMethod?->driver,
            'reference' => $this->reference,
            'has_receipt' => (bool) $this->receipt_path,
            'proof_submitted_at' => $this->proof_submitted_at,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'ends_at' => $this->ends_at,
            'trial_ends_at' => $this->trial_ends_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
