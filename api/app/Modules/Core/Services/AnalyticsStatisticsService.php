<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\DownloadEvent;
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

    // ------------------------------------------------- site-wide insights

    /**
     * The site's areas, as the header groups them. A path belongs to the
     * first area whose prefix it starts with; the Arabic twin (/ar/...)
     * counts towards the same area. Case-insensitive, as the paths are.
     */
    public const SECTIONS = [
        'home' => ['/'],
        'learn' => ['/learn'],
        'icons' => ['/IconsGalary', '/my-library'],
        'converters' => ['/ImageConvert', '/AudioConvert', '/FileConvert', '/convert'],
        'dev_tools' => ['/tools', '/svg-toolkit', '/token-calculator', '/agents-md', '/jsonl-validator', '/arabic-text',
            '/json-toolkit', '/mock-data', '/favicon-generator', '/jwt-decoder', '/cron-builder', '/text-diff',
            '/tool-schema', '/code-image', '/og-image', '/prompt-library', '/playground'],
        'design' => ['/vector-editor', '/Drower'],
        'components' => ['/components'],
        'apps' => ['/apps', '/desktop-apps'],
        'forms' => ['/f', '/forms'],
        'docs' => ['/documentation', '/About-Us', '/Contact-Us', '/Faq', '/Privacy-Policy', '/Terms-Of-Service'],
        'account' => ['/login', '/register', '/account', '/forgot-password'],
    ];

    /** SQL for the path with any /ar prefix removed ("/ar" itself is "/"). */
    private function basePathSql(): string
    {
        return "CASE WHEN path = '/ar' THEN '/' WHEN path LIKE '/ar/%' THEN SUBSTRING(path, 4) ELSE path END";
    }

    /** SQL that maps a path to its SECTIONS key, or 'other'. */
    private function sectionSql(): string
    {
        $base = $this->basePathSql();
        $cases = [];
        foreach (self::SECTIONS as $key => $prefixes) {
            $conditions = [];
            foreach ($prefixes as $prefix) {
                $quoted = DB::getPdo()->quote(strtolower($prefix));
                $conditions[] = $prefix === '/'
                    ? "LOWER({$base}) = '/'"
                    : "(LOWER({$base}) = {$quoted} OR LOWER({$base}) LIKE CONCAT({$quoted}, '/%'))";
            }
            $cases[] = 'WHEN ' . implode(' OR ', $conditions) . " THEN '{$key}'";
        }

        return 'CASE ' . implode(' ', $cases) . " ELSE 'other' END";
    }

    /**
     * Traffic per area of the site: where visitors actually spend their time,
     * and so where development effort pays off.
     */
    public function sections(int $days = 30): array
    {
        $section = $this->sectionSql();

        $rows = PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw("{$section} as section, COUNT(*) as visits, COUNT(DISTINCT session_id) as visitors, AVG(duration_seconds) as avg_duration, COUNT(DISTINCT path) as pages")
            ->groupBy('section')
            ->orderByDesc('visits')
            ->get();

        $total = (int) $rows->sum('visits');

        return $rows->map(fn ($row) => [
            'section' => $row->section,
            'visits' => (int) $row->visits,
            'visitors' => (int) $row->visitors,
            'pages' => (int) $row->pages,
            'share' => $total > 0 ? round($row->visits * 100 / $total, 1) : 0,
            'avg_duration' => $row->avg_duration ? round((float) $row->avg_duration) : null,
        ])->all();
    }

    /** Visits per course in the learning center, with the most-read lessons. */
    public function learning(int $days = 30, int $limit = 10): array
    {
        $base = $this->basePathSql();
        $since = Carbon::now()->subDays($days);
        $course = "SUBSTRING_INDEX(SUBSTRING({$base}, 8), '/', 1)";

        $courses = PageVisit::where('visited_at', '>=', $since)
            ->whereRaw("{$base} LIKE '/learn/%'")
            ->selectRaw("{$course} as course, COUNT(*) as visits, COUNT(DISTINCT session_id) as visitors, AVG(duration_seconds) as avg_duration")
            ->groupBy('course')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'course' => $row->course,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
                'avg_duration' => $row->avg_duration ? round((float) $row->avg_duration) : null,
            ])->all();

        $lessons = PageVisit::where('visited_at', '>=', $since)
            ->whereRaw("{$base} LIKE '/learn/%/%'")
            ->selectRaw("{$base} as lesson_path, MAX(page_title) as title, COUNT(*) as visits, COUNT(DISTINCT session_id) as visitors, AVG(duration_seconds) as avg_duration")
            ->groupBy('lesson_path')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->lesson_path,
                'title' => $row->title,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
                'avg_duration' => $row->avg_duration ? round((float) $row->avg_duration) : null,
            ])->all();

        return ['courses' => $courses, 'lessons' => $lessons];
    }

    /**
     * How visitors behave: new against returning, how many leave after one
     * page, how deep a visit goes, and the Arabic/English split.
     *
     * A "visitor" is a browser (the session id persists in localStorage), so
     * "returning" means the browser was first seen before this window.
     */
    public function engagement(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $perVisitor = PageVisit::where('visited_at', '>=', $since)
            ->selectRaw('session_id, COUNT(*) as views')
            ->groupBy('session_id');
        $depth = DB::query()->fromSub($perVisitor, 'v')
            ->selectRaw('COUNT(*) as visitors, SUM(views = 1) as single_page, AVG(views) as avg_views')
            ->first();

        $firstSeen = PageVisit::query()
            ->selectRaw('session_id, MIN(visited_at) as first_seen')
            ->groupBy('session_id')
            ->havingRaw('MAX(visited_at) >= ?', [$since]);
        $newVisitors = (int) DB::query()->fromSub($firstSeen, 'f')->where('first_seen', '>=', $since)->count();

        $visitors = (int) ($depth->visitors ?? 0);
        $arabic = PageVisit::where('visited_at', '>=', $since)
            ->where(fn ($q) => $q->where('path', '/ar')->orWhere('path', 'like', '/ar/%'))
            ->count();
        $all = PageVisit::where('visited_at', '>=', $since)->count();

        return [
            'visitors' => $visitors,
            'new_visitors' => $newVisitors,
            'returning_visitors' => max(0, $visitors - $newVisitors),
            'bounce_rate' => $visitors > 0 ? round(((int) $depth->single_page) * 100 / $visitors, 1) : 0,
            'pages_per_visitor' => $depth->avg_views ? round((float) $depth->avg_views, 1) : 0,
            'languages' => [
                ['label' => 'English', 'total' => $all - $arabic],
                ['label' => 'Arabic', 'total' => $arabic],
            ],
        ];
    }

    /**
     * Where visitors come from, judged by the referrer of each visitor's
     * first page in the window: a search engine, social network, an AI
     * assistant, another site, or nothing (typed, bookmarked, an app).
     */
    public function trafficSources(int $days = 30, int $limit = 10): array
    {
        $firstIds = PageVisit::where('visited_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('MIN(id) as edge_id')
            ->groupBy('session_id');

        $referrers = PageVisit::joinSub($firstIds, 'edges', 'edges.edge_id', '=', 'page_visits.id')
            ->selectRaw('page_visits.referrer as referrer, COUNT(*) as total')
            ->groupBy('page_visits.referrer')
            ->get();

        $channels = [];
        $sites = [];
        foreach ($referrers as $row) {
            $host = strtolower((string) parse_url((string) $row->referrer, PHP_URL_HOST));
            $host = preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host);
            $channel = $this->channel($host);
            $channels[$channel] = ($channels[$channel] ?? 0) + (int) $row->total;
            if ($host !== '' && $channel !== 'internal') {
                $sites[$host] = ($sites[$host] ?? 0) + (int) $row->total;
            }
        }

        arsort($channels);
        arsort($sites);

        return [
            'channels' => collect($channels)->map(fn ($total, $label) => ['label' => $label, 'total' => $total])->values()->all(),
            'referrers' => collect($sites)->take($limit)->map(fn ($total, $label) => ['label' => $label, 'total' => $total])->values()->all(),
        ];
    }

    private function channel(string $host): string
    {
        if ($host === '') {
            return 'direct';
        }
        if (str_ends_with($host, 'frugaldomain.site')) {
            return 'internal';
        }
        $groups = [
            'search' => '/(^|\.)(google|bing|yahoo|duckduckgo|yandex|baidu|ecosia|qwant|brave|search\.)/',
            'ai' => '/(chatgpt|openai|perplexity|claude\.ai|anthropic|gemini|copilot|you\.com|phind|poe\.com|deepseek)/',
            'social' => '/(facebook|fb\.|instagram|t\.co$|twitter|x\.com$|linkedin|lnkd|reddit|youtube|youtu\.be|tiktok|pinterest|telegram|t\.me$|whatsapp|discord)/',
            'code' => '/(github|gitlab|stackoverflow|stackexchange|dev\.to|medium|hashnode|npmjs)/',
        ];
        foreach ($groups as $label => $pattern) {
            if (preg_match($pattern, $host)) {
                return $label;
            }
        }

        return 'referral';
    }

    /** The pages visits start on and the pages they end on. */
    public function entryExitPages(int $days = 30, int $limit = 10): array
    {
        $since = Carbon::now()->subDays($days);

        $rank = function (string $edge) use ($since, $limit) {
            $ids = PageVisit::where('visited_at', '>=', $since)
                ->selectRaw("{$edge}(id) as edge_id")
                ->groupBy('session_id');

            return PageVisit::joinSub($ids, 'edges', 'edges.edge_id', '=', 'page_visits.id')
                ->selectRaw('page_visits.path as path, COUNT(*) as total')
                ->groupBy('page_visits.path')
                ->orderByDesc('total')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => ['path' => $row->path, 'total' => (int) $row->total])
                ->all();
        };

        return ['entry' => $rank('MIN'), 'exit' => $rank('MAX')];
    }

    /** Program downloads: per program, per day, and the headline counts. */
    public function downloads(int $days = 30, int $limit = 20): array
    {
        $since = Carbon::now()->subDays($days);
        $today = Carbon::today();

        $items = DownloadEvent::where('occurred_at', '>=', $since)
            ->selectRaw('item_type, item_id, MAX(item_slug) as slug, MAX(item_name) as name, COUNT(*) as downloads, COUNT(DISTINCT session_id) as visitors')
            ->groupBy('item_type', 'item_id')
            ->orderByDesc('downloads')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'type' => $row->item_type,
                'id' => $row->item_id ? (int) $row->item_id : null,
                'slug' => $row->slug,
                'name' => $row->name,
                'downloads' => (int) $row->downloads,
                'visitors' => (int) $row->visitors,
            ])->all();

        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $perDay = DownloadEvent::where('occurred_at', '>=', $start)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as downloads')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => (string) $row->day);
        $timeline = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $timeline[] = ['date' => $date, 'downloads' => (int) ($perDay->get($date)->downloads ?? 0)];
        }

        return [
            'today' => DownloadEvent::whereDate('occurred_at', $today)->count(),
            'period' => DownloadEvent::where('occurred_at', '>=', $since)->count(),
            'total' => DownloadEvent::count(),
            'items' => $items,
            'timeline' => $timeline,
            'platforms' => DownloadEvent::where('occurred_at', '>=', $since)
                ->whereNotNull('platform')
                ->selectRaw('platform as label, COUNT(*) as total')
                ->groupBy('platform')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
                ->all(),
        ];
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
