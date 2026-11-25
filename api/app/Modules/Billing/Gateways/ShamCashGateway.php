<?php

namespace App\Modules\Billing\Gateways;

use App\Modules\Billing\Models\PaymentMethod;

/**
 * Sham Cash has no public payment API: the owner sends the amount to the admin's
 * account code, then sends the transfer number or a photo of the receipt, and the
 * admin confirms it from Subscriptions.
 */
class ShamCashGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'shamcash';
    }

    public function describe(): array
    {
        return ['key' => 'shamcash', 'label' => 'Sham Cash (transfer, confirmed by admin)', 'fields' => [
            ['key' => 'account_code', 'label' => 'Sham Cash account code', 'required' => true],
            ['key' => 'account_name', 'label' => 'Account holder name'],
        ]];
    }

    public function checkout(Payable $payable, PaymentMethod $method): array
    {
        $config = $method->config ?? [];

        return [
            'type' => 'instructions',
            'subscription_id' => $payable->getKey(),
            'payable_id' => $payable->getKey(),
            'instructions' => (object) ($method->instructions ?? []),
            'account' => ['code' => $config['account_code'] ?? '', 'name' => $config['account_name'] ?? ''],
            'amount' => number_format($payable->payableAmount(), 2, '.', ''),
            'proof' => $this->proof(),
        ];
    }

    public function verify(Payable $payable, PaymentMethod $method): ?bool
    {
        return null;
    }

    public function proof(): ?string
    {
        return 'required';
    }
}
