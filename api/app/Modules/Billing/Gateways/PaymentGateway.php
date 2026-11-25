<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Subscription;

/**
 * One way of taking payment for a plan. Add a gateway by implementing this and
 * registering it in GatewayRegistry.
 */
interface PaymentGateway
{
    public function key(): string;

    /**
     * Label and the config fields the admin form asks for:
     * [['key' => 'client_secret', 'label' => 'Client secret', 'secret' => true, 'required' => true, 'options' => [...]?]].
     * Secret fields are never sent back to the browser.
     */
    public function describe(): array;

    /** Starts paying for a pending subscription: ['type' => 'redirect', 'url' => ...] or ['type' => 'instructions', ...]. */
    public function checkout(Subscription $subscription, PaymentMethod $method): array;

    /**
     * Online gateways: asks the provider whether this subscription was paid in full.
     * Offline ones (proof checked by the admin) return null.
     */
    public function verify(Subscription $subscription, PaymentMethod $method): ?bool;

    /** null: no proof form; 'optional' or 'required': the owner may / must send a transfer number or receipt. */
    public function proof(): ?string;
}
