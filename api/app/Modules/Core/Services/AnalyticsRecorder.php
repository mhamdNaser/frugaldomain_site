<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\IconEvent;
use App\Modules\Core\Models\LoginEvent;
use App\Modules\Core\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes visitor analytics.
 *
 * Every public method swallows its own failures: analytics must never break a
 * page view or block a sign-in. Failures are logged instead.
 */
class AnalyticsRecorder
{
    /** Derive device, browser, platform and country from the request. */
    public function context(Request $request): array
    {
        $agent = (string) $request->userAgent();

        return [
            'ip_address' => $request->ip(),
            'country' => $this->country($request),
            'device_type' => $this->deviceType($agent),
            'browser' => $this->browser($agent),
            'platform' => $this->platform($agent),
            'user_agent' => mb_substr($agent, 0, 1000),
        ];
    }

    /**
     * Record a page view, or attach a duration to one already recorded.
     *
     * The client cannot know how long a page was viewed until the visitor
     * leaves it, so it sends a second beacon carrying `visit_id` and
     * `duration`. That MUST update the original row: inserting again would
     * count every page view twice and double every figure on the dashboard.
     */
    /**
     * Paths excluded from visitor analytics.
     *
     * The dashboard is the admin's own workspace, not public traffic: counting
     * it would inflate every figure with the sessions of the person reading
     * the report. Filtering here rather than in the queries means the rows are
     * never written at all, so no later report can accidentally include them.
     */
    public const EXCLUDED_PATH_PREFIXES = ['/admin'];

    public static function isExcludedPath(?string $path): bool
    {
        $normalised = '/' . ltrim(strtolower(trim((string) $path)), '/');

        foreach (self::EXCLUDED_PATH_PREFIXES as $prefix) {
            // Match the segment exactly: "/admin" and "/admin/..." are
            // excluded, but a public page such as "/administrators" is not.
            if ($normalised === $prefix || str_starts_with($normalised, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    public function recordPageVisit(Request $request, array $payload): ?PageVisit
    {
        try {
            if (self::isExcludedPath($payload['path'] ?? null)) {
                return null;
            }

            $visitId = isset($payload['visit_id']) ? (int) $payload['visit_id'] : null;

            if ($visitId && isset($payload['duration'])) {
                $visit = PageVisit::find($visitId);

                // Only the owning session may set the duration, so a guessed
                // id cannot rewrite someone else's row.
                if ($visit && $visit->session_id === $this->sessionId($request, $payload)) {
                    $visit->forceFill([
                        'duration_seconds' => max(0, (int) $payload['duration']),
                    ])->save();

                    return $visit;
                }

                // Unknown id: fall through and record the view normally rather
                // than silently losing it.
            }

            $context = $this->context($request);

            return PageVisit::create([
                'user_id' => $this->userId($request),
                'session_id' => $this->sessionId($request, $payload),
                'path' => mb_substr((string) ($payload['path'] ?? '/'), 0, 255),
                'page_title' => isset($payload['title']) ? mb_substr((string) $payload['title'], 0, 255) : null,
                'referrer' => isset($payload['referrer']) ? mb_substr((string) $payload['referrer'], 0, 512) : null,
                'duration_seconds' => isset($payload['duration']) ? (int) $payload['duration'] : null,
                'visited_at' => now(),
            ] + $context);
        } catch (Throwable $e) {
            Log::warning('Failed to record page visit.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function recordIconEvent(Request $request, int $iconId, string $eventType = 'click'): ?IconEvent
    {
        try {
            $context = $this->context($request);

            return IconEvent::create([
                'icon_id' => $iconId,
                'user_id' => $this->userId($request),
                'session_id' => $this->sessionId($request, []),
                'event_type' => in_array($eventType, ['click', 'view', 'copy', 'favorite'], true) ? $eventType : 'click',
                'ip_address' => $context['ip_address'],
                'country' => $context['country'],
                'device_type' => $context['device_type'],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to record icon event.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function recordLogin(Request $request, array $payload): ?LoginEvent
    {
        try {
            return LoginEvent::create([
                'user_id' => $payload['user_id'] ?? null,
                'email' => isset($payload['email']) ? mb_substr((string) $payload['email'], 0, 255) : null,
                'successful' => (bool) ($payload['successful'] ?? true),
                'failure_reason' => $payload['failure_reason'] ?? null,
                'provider' => $payload['provider'] ?? 'password',
                'logged_in_at' => now(),
            ] + $this->context($request));
        } catch (Throwable $e) {
            Log::warning('Failed to record login event.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function userId(Request $request): ?int
    {
        $user = $request->user('sanctum') ?? $request->user();
        return $user ? (int) $user->getKey() : null;
    }

    /**
     * A stable per-visitor key.
     *
     * The client sends one so anonymous visits can be grouped into sessions;
     * otherwise fall back to a hash of IP + agent, which is good enough for
     * counting and stores no personal identifier.
     */
    private function sessionId(Request $request, array $payload): string
    {
        $provided = $payload['session_id'] ?? $request->header('X-Session-Id');

        if (is_string($provided) && $provided !== '') {
            return mb_substr($provided, 0, 64);
        }

        return substr(hash('sha256', $request->ip() . '|' . $request->userAgent()), 0, 64);
    }

    /** Cloudflare and most proxies expose the resolved country as a header. */
    private function country(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code', 'X-AppEngine-Country'] as $header) {
            $value = $request->header($header);
            if (is_string($value) && strlen($value) === 2 && ctype_alpha($value)) {
                return strtoupper($value);
            }
        }

        return null;
    }

    private function deviceType(string $agent): string
    {
        if ($agent === '') {
            return 'unknown';
        }

        // Tablets must be tested first: most tablet agents also contain "Mobile".
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android|BlackBerry|Opera Mini|IEMobile/i', $agent)) {
            return 'mobile';
        }

        if (preg_match('/bot|crawl|spider|slurp/i', $agent)) {
            return 'bot';
        }

        return 'desktop';
    }

    private function browser(string $agent): string
    {
        // Order matters: Edge and Opera agents also advertise Chrome, and
        // Chrome advertises Safari.
        $tests = [
            'Edge' => '/Edg[e]?\//i',
            'Opera' => '/OPR\/|Opera/i',
            'Samsung Internet' => '/SamsungBrowser/i',
            'Chrome' => '/Chrome|CriOS/i',
            'Firefox' => '/Firefox|FxiOS/i',
            'Safari' => '/Safari/i',
            'Internet Explorer' => '/MSIE|Trident/i',
        ];

        foreach ($tests as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return 'Other';
    }

    private function platform(string $agent): string
    {
        $tests = [
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS X/i',
            'Linux' => '/Linux/i',
        ];

        foreach ($tests as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return 'Other';
    }
}
