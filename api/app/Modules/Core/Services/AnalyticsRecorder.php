<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\DownloadEvent;
use App\Modules\Core\Models\IconEvent;
use App\Modules\Core\Models\Scopes\ExcludeStaffScope;
use App\Modules\Core\Models\LoginEvent;
use App\Modules\Core\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    // ------------------------------------------------------------ staff

    /**
     * Is this request from the site's own staff?
     *
     * A signed-in admin is staff, and so is any browser an admin has been
     * seen on: once recognised, that browser stays excluded after sign-out.
     * Recognising it here means the browser is remembered the first time an
     * admin is seen on it, so its later anonymous visits are dropped too.
     */
    public function isStaff(Request $request, array $payload = []): bool
    {
        try {
            $sessionId = $this->sessionId($request, $payload);
            $user = $request->user('sanctum') ?? $request->user();

            if ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(ExcludeStaffScope::STAFF_ROLES)) {
                $this->rememberStaffBrowser($sessionId, (int) $user->getKey());
                return true;
            }

            return DB::table('analytics_excluded_sessions')->where('session_id', $sessionId)->exists();
        } catch (Throwable $e) {
            Log::warning('Failed to check analytics staff exclusion.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Mark the browser behind an admin request as staff. Called by the
     * dashboard's own requests, which always come from an admin.
     */
    public function rememberStaffRequest(Request $request): void
    {
        try {
            $user = $request->user('sanctum') ?? $request->user();
            $provided = $request->header('X-Session-Id');
            if (is_string($provided) && $provided !== '') {
                $this->rememberStaffBrowser(mb_substr($provided, 0, 64), $user ? (int) $user->getKey() : null);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to remember a staff browser.', ['error' => $e->getMessage()]);
        }
    }

    private function rememberStaffBrowser(string $sessionId, ?int $userId): void
    {
        DB::table('analytics_excluded_sessions')->updateOrInsert(
            ['session_id' => $sessionId],
            ['user_id' => $userId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    // ----------------------------------------------------------- recording

    public function recordPageVisit(Request $request, array $payload): ?PageVisit
    {
        try {
            if (self::isExcludedPath($payload['path'] ?? null) || $this->isStaff($request, $payload)) {
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
            if ($this->isStaff($request)) {
                return null;
            }

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

    /**
     * Record one download of a program. Called by the download endpoint
     * itself, so a download is counted however the visitor reached the link.
     * The page appends `sid` (the browser's analytics id) to the link, which
     * ties the download to the visit and lets staff downloads be skipped.
     */
    public function recordDownload(Request $request, string $type, ?int $itemId, ?string $slug, ?string $name): ?DownloadEvent
    {
        try {
            $payload = ['session_id' => $request->query('sid')];
            if ($this->isStaff($request, $payload)) {
                return null;
            }

            $context = $this->context($request);
            if ($context['device_type'] === 'bot') {
                return null;
            }

            return DownloadEvent::create([
                'item_type' => mb_substr($type, 0, 32),
                'item_id' => $itemId,
                'item_slug' => $slug ? mb_substr($slug, 0, 191) : null,
                'item_name' => $name ? mb_substr($name, 0, 255) : null,
                'user_id' => $this->userId($request),
                'session_id' => $this->sessionId($request, $payload),
                'ip_address' => $context['ip_address'],
                'country' => $context['country'],
                'device_type' => $context['device_type'],
                'platform' => $context['platform'],
                'referrer' => ($ref = $request->headers->get('referer')) ? mb_substr($ref, 0, 512) : null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to record download.', ['error' => $e->getMessage()]);
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
