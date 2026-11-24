<?php

use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookTopicRegistrar;

it('registers webhooks for every store', function () {
    $registrar = Mockery::mock(ShopifyWebhookTopicRegistrar::class);
    $registrar->shouldReceive('registerForAllStores')->once()->andReturn(['stores' => 2]);
    app()->instance(ShopifyWebhookTopicRegistrar::class, $registrar);

    $this->artisan('shopify:register-webhooks')->assertSuccessful();
});

it('registers webhooks for one owner', function () {
    $registrar = Mockery::mock(ShopifyWebhookTopicRegistrar::class);
    $registrar->shouldReceive('registerForOwnerId')->once()->with('42')->andReturn(['ok' => true]);
    app()->instance(ShopifyWebhookTopicRegistrar::class, $registrar);

    $this->artisan('shopify:register-webhooks', ['--owner' => '42'])->assertSuccessful();
});
