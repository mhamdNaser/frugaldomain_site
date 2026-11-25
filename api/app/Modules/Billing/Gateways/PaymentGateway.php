<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;

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

    /** Starts paying for a pending subscription or purchase: ['type' => 'redirect', 'url' => ...] or ['type' => 'instructions', ...]. */
    public function checkout(Payable $payable, PaymentMethod $method): array;

    /**
     * Online gateways: asks the provider whether this subscription or purchase was paid in full.
     * Offline ones (proof checked by the admin) return null.
     */
    public function verify(Payable $payable, PaymentMethod $method): ?bool;

    /** null: no proof form; 'optional' or 'required': the owner may / must send a transfer number or receipt. */
    public function proof(): ?string;
}
