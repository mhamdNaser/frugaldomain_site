<?php

namespace App\Modules\Shopify\Webhooks\Console;

use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookTopicRegistrar;
use Illuminate\Console\Command;

/** Points the stores' Shopify webhooks at this site (run after a domain change or a reconnect). */
class RegisterWebhooksCommand extends Command
{
    protected $signature = 'shopify:register-webhooks {--owner= : Only the store of this owner (user id)}';

    protected $description = 'Register the Shopify webhook topics for every connected store (or one owner)';

    public function handle(ShopifyWebhookTopicRegistrar $registrar): int
    {
        $owner = $this->option('owner');
        $result = $owner ? $registrar->registerForOwnerId((string) $owner) : $registrar->registerForAllStores();
        $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
