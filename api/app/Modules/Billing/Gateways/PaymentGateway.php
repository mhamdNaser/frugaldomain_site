<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Subscription;

/**
 * One way of taking payment for a plan. Add an online gateway by implementing
 * this and registering it in GatewayRegistry.
 */
interface PaymentGateway
{
    public function key(): string;

    /** Label and the config fields the admin form asks for: [['key' => 'api_key', 'label' => 'API key', 'secret' => true]]. */
    public function describe(): array;

    /** Starts paying for a pending subscription: ['type' => 'redirect', 'url' => ...] or ['type' => 'instructions', 'instructions' => [...]]. */
    public function checkout(Subscription $subscription, PaymentMethod $method): array;

    public function cancel(Subscription $subscription, PaymentMethod $method): void;
}
