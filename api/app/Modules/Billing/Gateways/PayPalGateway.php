<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * PayPal Orders v2. The owner approves on PayPal (a PayPal account, or a Visa /
 * Mastercard as a guest) and comes back to the Upgrade page, which asks us to
 * capture the order; the plan switches only after a completed capture of the full price.
 */
class PayPalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function describe(): array
    {
        return ['key' => 'paypal', 'label' => 'PayPal (also Visa / Mastercard)', 'fields' => [
            ['key' => 'client_id', 'label' => 'Client ID', 'required' => true],
            ['key' => 'client_secret', 'label' => 'Client secret', 'secret' => true, 'required' => true],
            ['key' => 'mode', 'label' => 'Mode', 'options' => ['sandbox', 'live'], 'required' => true],
            ['key' => 'currency', 'label' => 'Currency (e.g. USD)'],
        ]];
    }

    public function checkout(Subscription $subscription, PaymentMethod $method): array
    {
        [$http, $base] = $this->client($method);
        $return = rtrim((string) config('app.frontend_url'), '/').'/admin/billing/upgrade?subscription='.$subscription->id;

        $order = $http->post("{$base}/v2/checkout/orders", [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id' => (string) $subscription->id,
                'description' => $subscription->plan->name,
                'amount' => ['currency_code' => $this->currency($method), 'value' => $this->amount($subscription)],
            ]],
            'application_context' => [
                'return_url' => $return.'&paid=1',
                'cancel_url' => $return.'&canceled=1',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ])->throw()->json();

        $subscription->forceFill(['reference' => $order['id']])->save();
        $approve = collect($order['links'] ?? [])->firstWhere('rel', 'approve') ?? collect($order['links'] ?? [])->firstWhere('rel', 'payer-action');
        abort_unless($approve, 502, 'PayPal did not return an approval link.');

        return ['type' => 'redirect', 'url' => $approve['href']];
    }

    public function verify(Subscription $subscription, PaymentMethod $method): ?bool
    {
        if (! $subscription->reference) {
            return false;
        }
        [$http, $base] = $this->client($method);

        $res = $http->post("{$base}/v2/checkout/orders/{$subscription->reference}/capture", (object) []);
        // Already captured (a second click, or a reload): read the order instead.
        $order = $res->successful() ? $res->json() : $http->get("{$base}/v2/checkout/orders/{$subscription->reference}")->json();

        $capture = data_get($order, 'purchase_units.0.payments.captures.0');

        return data_get($order, 'status') === 'COMPLETED'
            && data_get($capture, 'status') === 'COMPLETED'
            && data_get($capture, 'amount.value') === $this->amount($subscription)
            && strtoupper((string) data_get($capture, 'amount.currency_code')) === $this->currency($method);
    }

    public function proof(): ?string
    {
        return null;
    }

    /** @return array{0: PendingRequest, 1: string} */
    private function client(PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $base = ($config['mode'] ?? 'sandbox') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()->timeout(20)
            ->withBasicAuth((string) ($config['client_id'] ?? ''), (string) ($config['client_secret'] ?? ''))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()->json('access_token');

        return [Http::withToken($token)->acceptJson()->timeout(20), $base];
    }

    private function currency(PaymentMethod $method): string
    {
        return strtoupper(($method->config['currency'] ?? '') ?: 'USD');
    }

    private function amount(Subscription $subscription): string
    {
        return number_format((float) $subscription->plan->price, 2, '.', '');
    }
}
