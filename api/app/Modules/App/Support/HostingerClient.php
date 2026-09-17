<?php

namespace App\Modules\App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the Hostinger hosting API (developers.hostinger.com, spec
 * v1.53.1) covering exactly what subdomain provisioning needs.
 *
 * Two rules shape this class:
 *
 *  1. Nothing is hardcoded. The account username and the exact domain string
 *     are resolved from GET /api/hosting/v1/websites and cached, so moving the
 *     site to another plan or domain needs no code change.
 *
 *  2. Nothing throws. Every public method returns the same array shape —
 *     ['ok' => bool, 'status' => ?int, 'message' => string, 'data' => mixed,
 *      'configured' => bool] — so an unconfigured or unreachable Hostinger
 *     never takes the dashboard down with it.
 *
 * The "deploy static site archive" endpoint is deliberately NOT wrapped:
 * Hostinger's own spec warns that it overwrites existing site contents
 * irreversibly, which against a live main domain is unacceptable.
 */
class HostingerClient
{
    /** Cache key + TTL for the resolved {username, domain} pair. */
    protected const SITE_CACHE_KEY = 'hostinger.site';
    protected const SITE_CACHE_TTL = 3600; // one hour

    /**
     * A subdomain label: lowercase alphanumerics and inner hyphens only.
     * Deliberately stricter than RFC 1123 (no uppercase, no underscore) so the
     * label is also safe as a directory name.
     */
    public const PREFIX_PATTERN = '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/';

    public const PREFIX_MAX_LENGTH = 63;

    /**
     * Labels that either already exist on the zone or would shadow
     * infrastructure. Reserved rather than "taken" so the message is honest.
     */
    public const RESERVED_PREFIXES = [
        'www', 'api', 'cdn', 'mail', 'smtp', 'imap', 'pop', 'pop3', 'ftp',
        'ns', 'ns1', 'ns2', 'mx', 'webmail', 'webdisk', 'cpanel', 'whm',
        'autodiscover', 'autoconfig', 'localhost', 'admin', 'dashboard',
        'staging', '_domainkey', 'dkim', 'spf', 'dmarc',
    ];

    // ------------------------------------------------------------ configuration

    public function configured(): bool
    {
        return trim((string) config('services.hostinger.token')) !== '';
    }

    protected function token(): string
    {
        return trim((string) config('services.hostinger.token'));
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.hostinger.base_url', 'https://developers.hostinger.com'), '/');
    }

    /** The domain configured as the zone subdomains are created under. */
    public function configuredDomain(): string
    {
        return strtolower(trim((string) config('services.hostinger.domain', 'frugaldomain.site')));
    }

    // ------------------------------------------------------------ prefix rules

    /**
     * Validates a subdomain label before any network call is made, so an
     * obviously bad prefix never burns a request against the rate limit.
     *
     * @return array{ok: bool, message: string, prefix: string}
     */
    public function validatePrefix(?string $prefix): array
    {
        $prefix = strtolower(trim((string) $prefix));

        if ($prefix === '') {
            return $this->prefixError($prefix, 'The subdomain prefix is required.');
        }

        if (strlen($prefix) > self::PREFIX_MAX_LENGTH) {
            return $this->prefixError($prefix, 'The subdomain prefix may not be longer than ' . self::PREFIX_MAX_LENGTH . ' characters.');
        }

        if (!preg_match(self::PREFIX_PATTERN, $prefix)) {
            return $this->prefixError(
                $prefix,
                'The subdomain prefix may only contain lowercase letters, digits and inner hyphens, and must start and end with a letter or digit.'
            );
        }

        if (in_array($prefix, self::RESERVED_PREFIXES, true)) {
            return $this->prefixError($prefix, "\"{$prefix}\" is reserved and cannot be used as a subdomain.");
        }

        return ['ok' => true, 'message' => '', 'prefix' => $prefix];
    }

    protected function prefixError(string $prefix, string $message): array
    {
        return ['ok' => false, 'message' => $message, 'prefix' => $prefix];
    }

    /** The fully-qualified hostname a prefix resolves to. */
    public function fqdn(string $prefix): string
    {
        return strtolower(trim($prefix)) . '.' . $this->domain();
    }

    public function liveUrl(string $prefix): string
    {
        return 'https://' . $this->fqdn($prefix);
    }

    // ------------------------------------------------------------- site lookup

    /**
     * Resolves {username, domain} from GET /websites and caches the pair.
     *
     * Preference order: the website whose domain matches the configured one,
     * then the main vhost, then the first row. Falls back to the configured
     * domain with a null username when the call fails, so callers can still
     * render a sensible URL.
     *
     * @return array{ok: bool, username: ?string, domain: string, message: string, configured: bool}
     */
    public function site(bool $fresh = false): array
    {
        if (!$this->configured()) {
            return [
                'ok' => false,
                'username' => null,
                'domain' => $this->configuredDomain(),
                'message' => $this->notConfiguredMessage(),
                'configured' => false,
            ];
        }

        if ($fresh) {
            Cache::forget(self::SITE_CACHE_KEY);
        } else {
            $cached = Cache::get(self::SITE_CACHE_KEY);
            if (is_array($cached) && !empty($cached['username'])) {
                return $cached + ['ok' => true, 'message' => '', 'configured' => true];
            }
        }

        $result = $this->request('get', '/api/hosting/v1/websites');

        if (!$result['ok']) {
            return [
                'ok' => false,
                'username' => null,
                'domain' => $this->configuredDomain(),
                'message' => $result['message'],
                'configured' => true,
            ];
        }

        $websites = is_array($result['data']) ? $result['data'] : [];
        // Some responses wrap the collection in a `data` key.
        if (isset($websites['data']) && is_array($websites['data'])) {
            $websites = $websites['data'];
        }

        $chosen = $this->pickWebsite($websites);

        if (!$chosen) {
            return [
                'ok' => false,
                'username' => null,
                'domain' => $this->configuredDomain(),
                'message' => 'Hostinger returned no websites for this account.',
                'configured' => true,
            ];
        }

        $site = [
            'username' => (string) ($chosen['username'] ?? ''),
            'domain' => strtolower((string) ($chosen['domain'] ?? $this->configuredDomain())),
            'root_directory' => (string) ($chosen['root_directory'] ?? ''),
        ];

        if ($site['username'] === '') {
            return [
                'ok' => false,
                'username' => null,
                'domain' => $site['domain'] ?: $this->configuredDomain(),
                'message' => 'Hostinger did not return an account username for this website.',
                'configured' => true,
            ];
        }

        Cache::put(self::SITE_CACHE_KEY, $site, self::SITE_CACHE_TTL);

        return $site + ['ok' => true, 'message' => '', 'configured' => true];
    }

    /** Configured domain wins, then the main vhost, then the first entry. */
    protected function pickWebsite(array $websites): ?array
    {
        $rows = array_values(array_filter($websites, 'is_array'));

        if (!$rows) {
            return null;
        }

        $wanted = $this->configuredDomain();

        foreach ($rows as $row) {
            if (strtolower((string) ($row['domain'] ?? '')) === $wanted) {
                return $row;
            }
        }

        foreach ($rows as $row) {
            if (($row['vhost_type'] ?? null) === 'main') {
                return $row;
            }
        }

        return $rows[0];
    }

    /** The resolved domain, falling back to config when the lookup fails. */
    public function domain(): string
    {
        $site = $this->site();

        return $site['domain'] ?: $this->configuredDomain();
    }

    // --------------------------------------------------------------- subdomains

    /**
     * POST /api/hosting/v1/accounts/{username}/websites/{domain}/subdomains
     *
     * $directory is relative to the website root (Hostinger's own wording), so
     * "apps/my-project" points the subdomain at public_html/apps/my-project.
     */
    public function createSubdomain(string $prefix, ?string $directory = null, bool $usePublicDirectory = false): array
    {
        $check = $this->validatePrefix($prefix);
        if (!$check['ok']) {
            return $this->failure($check['message'], 422);
        }

        $site = $this->site();
        if (!$site['ok']) {
            return $this->failure($site['message'], null, $site['configured']);
        }

        $payload = ['subdomain' => $check['prefix']];

        if ($directory !== null && trim($directory) !== '') {
            $payload['directory'] = trim($directory);
        }

        $payload['is_using_public_directory'] = $usePublicDirectory;

        return $this->request(
            'post',
            $this->subdomainsPath($site),
            $payload
        );
    }

    /** GET .../subdomains */
    public function listSubdomains(): array
    {
        $site = $this->site();
        if (!$site['ok']) {
            return $this->failure($site['message'], null, $site['configured']);
        }

        return $this->request('get', $this->subdomainsPath($site));
    }

    /** True when the zone already carries this label. */
    public function subdomainExists(string $prefix): ?bool
    {
        $result = $this->listSubdomains();

        if (!$result['ok'] || !is_array($result['data'])) {
            return null; // unknown, not "no"
        }

        $rows = $result['data'];
        if (isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }

        $prefix = strtolower(trim($prefix));
        $fqdn = $this->fqdn($prefix);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                if (is_string($row) && (strtolower($row) === $prefix || strtolower($row) === $fqdn)) {
                    return true;
                }
                continue;
            }

            foreach (['subdomain', 'domain', 'name'] as $key) {
                $value = strtolower((string) ($row[$key] ?? ''));
                if ($value !== '' && ($value === $prefix || $value === $fqdn)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** DELETE .../subdomains/{subdomain} */
    public function deleteSubdomain(string $prefix): array
    {
        $check = $this->validatePrefix($prefix);
        if (!$check['ok']) {
            return $this->failure($check['message'], 422);
        }

        $site = $this->site();
        if (!$site['ok']) {
            return $this->failure($site['message'], null, $site['configured']);
        }

        return $this->request(
            'delete',
            $this->subdomainsPath($site) . '/' . rawurlencode($check['prefix'])
        );
    }

    // ---------------------------------------------------------------------- SSL

    /**
     * POST .../ssl/setup — issuance is asynchronous, so a 200 here means
     * "requested", never "HTTPS is live". Poll sslStatus() for that.
     */
    public function installSsl(string $fqdn): array
    {
        $site = $this->site();
        if (!$site['ok']) {
            return $this->failure($site['message'], null, $site['configured']);
        }

        return $this->request('post', $this->websitePath($site, $fqdn) . '/ssl/setup');
    }

    /** GET .../ssl/status */
    public function sslStatus(string $fqdn): array
    {
        $site = $this->site();
        if (!$site['ok']) {
            return $this->failure($site['message'], null, $site['configured']);
        }

        return $this->request('get', $this->websitePath($site, $fqdn) . '/ssl/status');
    }

    /**
     * Reads an SSL status payload and decides whether HTTPS is actually live.
     * Hostinger's shape varies, so several plausible keys are inspected and an
     * unrecognised payload reports "pending" rather than a false "live".
     */
    public function sslIsActive($payload): bool
    {
        if (is_bool($payload)) {
            return $payload;
        }

        if (!is_array($payload)) {
            return false;
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        // A list of certificates: active if any one of them is.
        if (array_is_list($payload)) {
            foreach ($payload as $row) {
                if ($this->sslIsActive($row)) {
                    return true;
                }
            }
            return false;
        }

        foreach (['is_active', 'active', 'installed', 'enabled', 'https'] as $key) {
            if (array_key_exists($key, $payload)) {
                return filter_var($payload[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        foreach (['status', 'state', 'ssl_status'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = strtolower((string) $payload[$key]);
            if (in_array($value, ['active', 'installed', 'issued', 'valid', 'ok', 'success', 'enabled'], true)) {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------ plumbing

    protected function subdomainsPath(array $site): string
    {
        return $this->websitePath($site, $site['domain']) . '/subdomains';
    }

    protected function websitePath(array $site, string $domain): string
    {
        return '/api/hosting/v1/accounts/' . rawurlencode((string) $site['username'])
            . '/websites/' . rawurlencode(strtolower($domain));
    }

    /**
     * Single exit point for every HTTP call. Normalises transport failures,
     * 401s and 422s into the same array shape, surfacing Hostinger's own
     * validation text verbatim so the admin sees what Hostinger actually said.
     */
    protected function request(string $method, string $path, array $payload = []): array
    {
        if (!$this->configured()) {
            return $this->failure($this->notConfiguredMessage(), null, false);
        }

        try {
            /** @var Response $response */
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.hostinger.timeout', 20))
                ->connectTimeout(10)
                ->{$method}($this->baseUrl() . $path, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Hostinger request failed to connect', ['path' => $path, 'error' => $e->getMessage()]);

            return $this->failure('Could not reach the Hostinger API: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::warning('Hostinger request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return $this->failure('The Hostinger request failed: ' . $e->getMessage());
        }

        $body = null;
        try {
            $body = $response->json();
        } catch (\Throwable $e) {
            $body = null;
        }

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'message' => '',
                'data' => $body,
                'configured' => true,
            ];
        }

        return $this->failure($this->messageFor($response, $body), $response->status());
    }

    /** Pulls the most useful human message out of a Hostinger error body. */
    protected function messageFor(Response $response, $body): string
    {
        $status = $response->status();

        if (is_array($body)) {
            // 422: {"message": "...", "errors": {"subdomain": ["..."]}}
            $parts = [];

            if (!empty($body['message']) && is_string($body['message'])) {
                $parts[] = $body['message'];
            }

            $errors = $body['errors'] ?? null;
            if (is_array($errors)) {
                foreach ($errors as $field => $messages) {
                    foreach ((array) $messages as $line) {
                        if (is_string($line) && $line !== '') {
                            $parts[] = is_string($field) ? "{$field}: {$line}" : $line;
                        }
                    }
                }
            }

            if (empty($parts) && !empty($body['error']) && is_string($body['error'])) {
                $parts[] = $body['error'];
            }

            if ($parts) {
                return implode(' ', array_unique($parts));
            }
        }

        if ($status === 401) {
            return 'Hostinger rejected the API token (401). Check HOSTINGER_API_TOKEN.';
        }

        if ($status === 429) {
            return 'Hostinger rate limit reached (90 requests per minute). Try again shortly.';
        }

        $text = trim((string) $response->body());

        return $text !== ''
            ? "Hostinger returned HTTP {$status}: " . mb_substr($text, 0, 300)
            : "Hostinger returned HTTP {$status}.";
    }

    protected function failure(string $message, ?int $status = null, bool $configured = true): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'data' => null,
            'configured' => $configured,
        ];
    }

    public function notConfiguredMessage(): string
    {
        return 'Automatic subdomain provisioning is not configured. Set HOSTINGER_API_TOKEN in the API environment to enable it.';
    }
}
