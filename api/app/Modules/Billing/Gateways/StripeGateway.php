<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\Http;

/**
 * Stripe Checkout (Visa / Mastercard). The owner pays on Stripe's page and comes
 * back to the Upgrade page; we read the session and switch the plan once it is paid in full.
 */
class StripeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function describe(): array
    {
        return ['key' => 'stripe', 'label' => 'Stripe (Visa / Mastercard)', 'fields' => [
            ['key' => 'secret_key', 'label' => 'Secret key (sk_...)', 'secret' => true, 'required' => true],
            ['key' => 'currency', 'label' => 'Currency (e.g. USD)'],
        ]];
    }

    public function checkout(Subscription $subscription, PaymentMethod $method): array
    {
        $return = rtrim((string) config('app.frontend_url'), '/').'/admin/billing/upgrade?subscription='.$subscription->id;

        $session = $this->http($method)->asForm()->post('https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => (string) $subscription->id,
            'success_url' => $return.'&paid=1',
            'cancel_url' => $return.'&canceled=1',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $this->currency($method),
                    'unit_amount' => $this->cents($subscription),
                    'product_data' => ['name' => $subscription->plan->name],
                ],
            ]],
        ])->throw()->json();

        $subscription->forceFill(['reference' => $session['id']])->save();

        return ['type' => 'redirect', 'url' => $session['url']];
    }

    public function verify(Subscription $subscription, PaymentMethod $method): ?bool
    {
        if (! $subscription->reference) {
            return false;
        }
        $session = $this->http($method)->get('https://api.stripe.com/v1/checkout/sessions/'.$subscription->reference)->throw()->json();

        return ($session['payment_status'] ?? null) === 'paid'
            && (int) ($session['amount_total'] ?? -1) === $this->cents($subscription)
            && strtolower((string) ($session['currency'] ?? '')) === $this->currency($method);
    }

    public function proof(): ?string
    {
        return null;
    }

    private function http(PaymentMethod $method)
    {
        return Http::withToken((string) ($method->config['secret_key'] ?? ''))->acceptJson()->timeout(20);
    }

    private function currency(PaymentMethod $method): string
    {
        return strtolower(($method->config['currency'] ?? '') ?: 'usd');
    }

    private function cents(Subscription $subscription): int
    {
        return (int) round((float) $subscription->plan->price * 100);
    }
}
