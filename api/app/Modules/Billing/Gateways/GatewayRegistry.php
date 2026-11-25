<?php

namespace App\Modules\Billing\Gateways;

use InvalidArgumentException;

class GatewayRegistry
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const DRIVERS = [
        'manual' => ManualGateway::class,
        'paypal' => PayPalGateway::class,
        'stripe' => StripeGateway::class,
        'shamcash' => ShamCashGateway::class,
    ];

    public static function keys(): array
    {
        return array_keys(self::DRIVERS);
    }

    public function get(string $driver): PaymentGateway
    {
        $class = self::DRIVERS[$driver] ?? throw new InvalidArgumentException("Unknown payment driver [{$driver}].");

        return app($class);
    }

    public function drivers(): array
    {
        return array_map(fn ($class) => app($class)->describe(), array_values(self::DRIVERS));
    }

    /** The driver's config fields, keyed by name. */
    public function fields(string $driver): array
    {
        return collect($this->get($driver)->describe()['fields'] ?? [])->keyBy('key')->all();
    }
}
