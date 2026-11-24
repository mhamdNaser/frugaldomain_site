<?php

namespace App\Modules\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ShopifyClient
{
    private const API_VERSION = '2026-04';

    private const THROTTLE_RETRIES = 6;

    public function __construct(
        private readonly Store $store
    ) {
        $this->validateStoreCredentials();
    }

    /**
     * تنفيذ GraphQL query على Shopify
     *
     * @param string $query
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     *
     * @throws ShopifySyncException
     */
    public function query(string $query, array $variables = []): array
    {
        $url = $this->buildGraphqlUrl();

        $payload = ['query' => $query];

        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->request()->post($url, $payload);
            } catch (\Throwable $e) {
                throw new ShopifySyncException(
                    message: 'Failed to connect to Shopify GraphQL API: ' . $e->getMessage(),
                    code: 0,
                    previous: $e,
                    context: [
                        'store_id' => $this->store->id,
                        'url' => $url,
                    ]
                );
            }

            $this->ensureSuccessfulHttpResponse($response, $url, $payload);

            $json = $response->json();

            if (!is_array($json)) {
                throw new ShopifySyncException(
                    message: 'Invalid JSON response returned from Shopify GraphQL API.',
                    context: [
                        'store_id' => $this->store->id,
                        'url' => $url,
                        'response_body' => $response->body(),
                    ]
                );
            }

            $wait = $this->throttleWaitSeconds($json);

            if ($wait === null || $attempt >= self::THROTTLE_RETRIES) {
                break;
            }

            sleep($wait);
        }

        $this->ensureNoGraphqlErrors($json, $url, $payload);

        return $json;
    }

    /**
     * Shopify answers THROTTLED (HTTP 200) when the query-cost bucket is empty, e.g. while
     * an import's follow-up jobs use it up. Returns how long to wait for enough points,
     * or null when the response is not throttled.
     */
    private function throttleWaitSeconds(array $json): ?int
    {
        $throttled = collect($json['errors'] ?? [])
            ->contains(fn ($error) => ($error['extensions']['code'] ?? null) === 'THROTTLED');

        if (!$throttled) {
            return null;
        }

        $cost = $json['extensions']['cost'] ?? [];
        $status = $cost['throttleStatus'] ?? [];
        $missing = (float) ($cost['requestedQueryCost'] ?? 0) - (float) ($status['currentlyAvailable'] ?? 0);
        $restoreRate = max(1.0, (float) ($status['restoreRate'] ?? 50));

        return (int) min(20, max(1, ceil($missing / $restoreRate)));
    }

    /**
     * تجهيز HTTP client
     */
    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => Crypt::decryptString($this->store->shopify_access_token),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(3, 500);
    }

    /**
     * بناء GraphQL endpoint
     */
    private function buildGraphqlUrl(): string
    {
        $domain = $this->normalizeDomain($this->store->shopify_domain);

        return sprintf(
            'https://%s/admin/api/%s/graphql.json',
            $domain,
            self::API_VERSION
        );
    }

    /**
     * تنظيف الدومين
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain;
    }

    /**
     * التحقق من وجود بيانات الاتصال الأساسية
     */
    private function validateStoreCredentials(): void
    {
        if (blank($this->store->shopify_domain)) {
            throw new ShopifySyncException('Store shopify_domain is missing.');
        }

        if (blank($this->store->shopify_access_token)) {
            throw new ShopifySyncException('Store shopify_access_token is missing.');
        }
    }

    /**
     * التحقق من HTTP status
     */
    private function ensureSuccessfulHttpResponse(Response $response, string $url, array $payload): void
    {
        if ($response->successful()) {
            return;
        }

        throw new ShopifySyncException(
            message: sprintf(
                'Shopify GraphQL HTTP request failed. Status: %s | Reason: %s',
                $response->status(),
                $response->reason() ?? 'Unknown error'
            ),
            context: [
                'store_id' => $this->store->id,
                'url' => $url,
                'status' => $response->status(),
                'reason' => $response->reason(),
                'payload' => $payload,
                'response' => $response->json() ?? $response->body(),
            ]
        );
    }

    /**
     * التحقق من GraphQL errors داخل الـ body
     */
    private function ensureNoGraphqlErrors(array $json, string $url, array $payload): void
    {
        if (!empty($json['errors'])) {
            $messages = collect($json['errors'])
                ->pluck('message')
                ->filter()
                ->implode(' | ');

            throw new ShopifySyncException(
                message: 'Shopify GraphQL returned errors' . ($messages ? ': ' . $messages : '.'),
                context: [
                    'store_id' => $this->store->id,
                    'url' => $url,
                    'payload' => $payload,
                    'errors' => $json['errors'],
                ]
            );
        }
    }
}
