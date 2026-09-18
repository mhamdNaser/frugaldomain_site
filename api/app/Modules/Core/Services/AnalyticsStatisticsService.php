<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\IconEvent;
use App\Modules\Core\Models\LoginEvent;
use App\Modules\Core\Models\PageVisit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the analytics dashboard.
 *
 * Every figure is derived from page_visits / login_events / icon_events plus
 * the existing icon_downloads table. Nothing here touches stores.
 */
class AnalyticsStatisticsService
{
    /** Headline numbers for the statistics page. */
    public function overview(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);
        $today = Carbon::today();

        return [
            'visits' => [
                'today' => PageVisit::whereDate('visited_at', $today)->count(),
                'period' => PageVisit::where('visited_at', '>=', $since)->count(),
                'total' => PageVisit::count(),
            ],
            'visitors' => [
                'today' => PageVisit::whereDate('visited_at', $today)->distinct('session_id')->count('session_id'),
                'period' => PageVisit::where('visited_at', '>=', $since)->distinct('session_id')->count('session_id'),
            ],
            'logins' => [
                'today' => LoginEvent::whereDate('logged_in_at', $today)->where('successful', true)->count(),
                'period' => LoginEvent::where('logged_in_at', '>=', $since)->where('successful', true)->count(),
                'failed_period' => LoginEvent::where('logged_in_at', '>=', $since)->where('successful', false)->count(),
            ],
            'icon_interactions' => [
                'today' => IconEvent::whereDate('occurred_at', $today)->count(),
                'period' => IconEvent::where('occurred_at', '>=', $since)->count(),
            ],
            'registered_visitors' => PageVisit::where('visited_at', '>=', $since)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'days' => $days,
        ];
    }

    /** Visits per day, for the trend chart. */
    public function visitsTimeline(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = PageVisit::where('visited_at', '>=', $since)
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as visits, COUNT(DISTINCT session_id) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy(fn ($row) => (string) $row->day);

        // Fill the gaps so the chart has a point for every day in the window.
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $row = $rows->get($date);
            $out[] = [
                'date' => $date,
                'visits' => (int) ($row->visits ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ];
        }

        return $out;
    }

    /** Most visited pages. */
    public function topPages(int $days = 30, int $limit = 20): array
    {
        return PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('path, COUNT(*) as visits, COUNT(DISTINCT session_id) as visitors, AVG(duration_seconds) as avg_duration')
            ->groupBy('path')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
                'avg_duration' => $row->avg_duration ? round((float) $row->avg_duration) : null,
            ])
            ->all();
    }

    /** Device / browser / country breakdowns. */
    public function audience(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $group = fn (string $column) => PageVisit::where('visited_at', '>=', $since)
            ->whereNotNull($column)
            ->selectRaw("$column as label, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();

        return [
            'devices' => $group('device_type'),
            'browsers' => $group('browser'),
            'platforms' => $group('platform'),
            'countries' => $group('country'),
        ];
    }

    /**
     * Icon engagement, combining clicks (icon_events) with downloads
     * (icon_downloads) so one row tells the whole story for an icon.
     */
    public function iconEngagement(int $days = 30, int $limit = 50): array
    {
        $since = Carbon::now()->subDays($days);

        $clicks = IconEvent::where('occurred_at', '>=', $since)
            ->selectRaw('icon_id, COUNT(*) as total, SUM(event_type = "click") as clicks, SUM(event_type = "copy") as copies, COUNT(DISTINCT session_id) as unique_sessions')
            ->groupBy('icon_id')
            ->get()
            ->keyBy('icon_id');

        $downloads = DB::table('icon_downloads')
            ->where('downloaded_at', '>=', $since)
            ->whereNull('deleted_at')
            ->selectRaw('icon_id, COUNT(*) as downloads')
            ->groupBy('icon_id')
            ->get()
            ->keyBy('icon_id');

        $iconIds = $clicks->keys()->merge($downloads->keys())->unique()->values();

        if ($iconIds->isEmpty()) {
            return [];
        }

        $icons = DB::table('icons')
            ->leftJoin('icon_categories', 'icons.category_id', '=', 'icon_categories.id')
            ->whereIn('icons.id', $iconIds)
            ->whereNull('icons.deleted_at')
            ->select('icons.id', 'icons.title', 'icons.file_svg', 'icons.file_png', 'icon_categories.name as category')
            ->get();

        return $icons
            ->map(function ($icon) use ($clicks, $downloads) {
                $click = $clicks->get($icon->id);
                $download = (int) ($downloads->get($icon->id)->downloads ?? 0);
                $clickTotal = (int) ($click->clicks ?? 0);

                return [
                    'icon_id' => (int) $icon->id,
                    'title' => $icon->title,
                    'category' => $icon->category,
                    'file_svg' => $icon->file_svg,
                    'file_png' => $icon->file_png,
                    'clicks' => $clickTotal,
                    'copies' => (int) ($click->copies ?? 0),
                    'unique_sessions' => (int) ($click->unique_sessions ?? 0),
                    'downloads' => $download,
                    // One ranking number so the table has a sensible default order.
                    'engagement' => $clickTotal + $download,
                ];
            })
            ->sortByDesc('engagement')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Pages ranked by how long visitors actually stay on them.
     *
     * Only views that reported a duration count towards the average: a page
     * the visitor closed the tab on never sends one, and averaging those in as
     * zero would punish exactly the pages people read to the end. `samples`
     * is returned so a 200-second average taken from one visit is visibly
     * weaker evidence than the same average over fifty.
     */
    public function longestStayPages(int $days = 30, int $limit = 10, int $minSamples = 1): array
    {
        return PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->whereNotNull('duration_seconds')
            ->selectRaw('path, COUNT(*) as samples, AVG(duration_seconds) as avg_duration, MAX(duration_seconds) as max_duration, SUM(duration_seconds) as total_duration')
            ->groupBy('path')
            ->havingRaw('COUNT(*) >= ?', [$minSamples])
            ->orderByDesc('avg_duration')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'samples' => (int) $row->samples,
                'avg_duration' => round((float) $row->avg_duration),
                'max_duration' => (int) $row->max_duration,
                'total_duration' => (int) $row->total_duration,
            ])
            ->all();
    }

    /**
     * One row per visitor session: who they are, their IP, how long they
     * stayed and how many pages they moved through.
     *
     * Session length is the span from the first to the last page view, which
     * also captures time on the final page even when no duration beacon
     * arrived for it.
     */
    public function visitorSessions(int $days = 30, int $limit = 100): array
    {
        $sessions = PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('session_id, MAX(user_id) as user_id, MAX(ip_address) as ip_address, MAX(country) as country, MAX(device_type) as device_type, MAX(browser) as browser, MAX(platform) as platform, COUNT(*) as page_views, COUNT(DISTINCT path) as unique_pages, MIN(visited_at) as started_at, MAX(visited_at) as last_seen, SUM(duration_seconds) as reported_duration')
            ->groupBy('session_id')
            ->orderByDesc('last_seen')
            ->limit($limit)
            ->get();

        $userIds = $sessions->pluck('user_id')->filter()->unique();
        $users = $userIds->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        return $sessions
            ->map(function ($row) use ($users) {
                $started = Carbon::parse($row->started_at);
                $last = Carbon::parse($row->last_seen);
                $span = $started->diffInSeconds($last);
                $user = $row->user_id ? $users->get($row->user_id) : null;

                return [
                    'session_id' => $row->session_id,
                    'user_id' => $row->user_id ? (int) $row->user_id : null,
                    'name' => $user->name ?? null,
                    'email' => $user->email ?? null,
                    'is_registered' => (bool) $row->user_id,
                    'ip_address' => $row->ip_address,
                    'country' => $row->country,
                    'device_type' => $row->device_type,
                    'browser' => $row->browser,
                    'platform' => $row->platform,
                    'page_views' => (int) $row->page_views,
                    'unique_pages' => (int) $row->unique_pages,
                    // Prefer the wall-clock span; fall back to the summed
                    // per-page durations for a single-page session.
                    'session_duration' => $span > 0 ? $span : (int) ($row->reported_duration ?? 0),
                    'started_at' => $started->toIso8601String(),
                    'last_seen' => $last->toIso8601String(),
                ];
            })
            ->all();
    }

    /** Every page one session visited, in order, with time spent on each. */
    public function sessionJourney(string $sessionId, int $limit = 200): array
    {
        return PageVisit::where('session_id', $sessionId)
            ->orderBy('visited_at')
            ->limit($limit)
            ->get(['id', 'path', 'page_title', 'referrer', 'duration_seconds', 'visited_at', 'ip_address', 'device_type', 'browser'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'path' => $row->path,
                'title' => $row->page_title,
                'referrer' => $row->referrer,
                'duration' => $row->duration_seconds,
                'visited_at' => $row->visited_at?->toIso8601String(),
                'ip_address' => $row->ip_address,
                'device_type' => $row->device_type,
                'browser' => $row->browser,
            ])
            ->all();
    }

    /** Recent sign-ins for the security log. */
    public function recentLogins(int $limit = 50): array
    {
        return LoginEvent::with('user:id,name,email')
            ->orderByDesc('logged_in_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'user' => $row->user?->name,
                'email' => $row->email ?? $row->user?->email,
                'successful' => (bool) $row->successful,
                'failure_reason' => $row->failure_reason,
                'provider' => $row->provider,
                'ip_address' => $row->ip_address,
                'country' => $row->country,
                'device_type' => $row->device_type,
                'browser' => $row->browser,
                'platform' => $row->platform,
                'logged_in_at' => $row->logged_in_at?->toIso8601String(),
            ])
            ->all();
    }

    /** Visitors who are signed in, most recently seen first. */
    public function activeUsers(int $days = 30, int $limit = 50): array
    {
        return PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->selectRaw('user_id, COUNT(*) as visits, MAX(visited_at) as last_seen')
            ->groupBy('user_id')
            ->orderByDesc('last_seen')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'name' => $row->user?->name,
                'email' => $row->user?->email,
                'visits' => (int) $row->visits,
                'last_seen' => $row->last_seen,
            ])
            ->all();
    }
}
