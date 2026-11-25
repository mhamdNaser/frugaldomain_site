<?php

namespace App\Modules\Billing\Gateways;

/**
 * Something a gateway can take payment for: a plan subscription or a
 * marketplace purchase. It is an Eloquent model with `reference` and
 * `paymentMethod`, so gateways can store the provider's order id on it.
 */
interface Payable
{
    public function payableAmount(): float;

    public function payableTitle(): string;

    /** Site path the buyer comes back to; the gateway appends &paid=1 or &canceled=1. */
    public function payableReturnPath(): string;
}
