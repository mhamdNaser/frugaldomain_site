<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
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

    public function checkout(Payable $payable, PaymentMethod $method): array
    {
        $return = rtrim((string) config('app.frontend_url'), '/').$payable->payableReturnPath();

        $session = $this->http($method)->asForm()->post('https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => (string) $payable->getKey(),
            'success_url' => $return.'&paid=1',
            'cancel_url' => $return.'&canceled=1',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $this->currency($method),
                    'unit_amount' => $this->cents($payable),
                    'product_data' => ['name' => $payable->payableTitle()],
                ],
            ]],
        ])->throw()->json();

        $payable->forceFill(['reference' => $session['id']])->save();

        return ['type' => 'redirect', 'url' => $session['url']];
    }

    public function verify(Payable $payable, PaymentMethod $method): ?bool
    {
        if (! $payable->reference) {
            return false;
        }
        $session = $this->http($method)->get('https://api.stripe.com/v1/checkout/sessions/'.$payable->reference)->throw()->json();

        return ($session['payment_status'] ?? null) === 'paid'
            && (int) ($session['amount_total'] ?? -1) === $this->cents($payable)
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

    private function cents(Payable $payable): int
    {
        return (int) round($payable->payableAmount() * 100);
    }
}
