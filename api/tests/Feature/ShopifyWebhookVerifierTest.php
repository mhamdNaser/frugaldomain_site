<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookSecretResolver;
use App\Modules\Shopify\Webhooks\Services\ShopifyWebhookVerifier;

// The resolver reads per-store secrets from the DB with an env fallback; stub it so the test needs neither.
beforeEach(function () {
    app()->instance(ShopifyWebhookSecretResolver::class, new class extends ShopifyWebhookSecretResolver {
        public function resolveByStoreId(?string $storeId): ?string
        {
            return 'test-secret';
        }
    });
    $this->rawBody = '{"id":123,"title":"Example"}';
    $this->verifier = app(ShopifyWebhookVerifier::class);
});

it('verifies shopify webhook signature', function () {
    $header = base64_encode(hash_hmac('sha256', $this->rawBody, 'test-secret', true));

    expect($this->verifier->verify($this->rawBody, $header))->toBeTrue();
    expect($this->verifier->verify($this->rawBody, 'invalid'))->toBeFalse();
});

it('rejects a signature made with the wrong secret', function () {
    $header = base64_encode(hash_hmac('sha256', $this->rawBody, 'wrong-secret', true));

    expect($this->verifier->verify($this->rawBody, $header))->toBeFalse();
    expect($this->verifier->verify($this->rawBody, null))->toBeFalse();
});
