<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Subscription;

/** Bank transfer, card details or cash: shows the admin's instructions; the admin confirms the payment. */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function describe(): array
    {
        return ['key' => 'manual', 'label' => 'Manual (bank transfer, confirmed by admin)', 'fields' => []];
    }

    public function checkout(Subscription $subscription, PaymentMethod $method): array
    {
        return [
            'type' => 'instructions',
            'subscription_id' => $subscription->id,
            'instructions' => (object) ($method->instructions ?? []),
            'proof' => $this->proof(),
        ];
    }

    public function verify(Subscription $subscription, PaymentMethod $method): ?bool
    {
        return null;
    }

    public function proof(): ?string
    {
        return 'optional';
    }
}
