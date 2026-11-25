<?php

/**
 * What a course, a component or a marketplace item's address is served.
 *
 * Same approach as learn-preview.php (lessons): these pages have no prerendered
 * snapshot, so a crawler would otherwise get the bare app page. This fetches
 * the record from the api in the address's language and writes its title,
 * description, canonical, hreflang and JSON-LD into the head, and its main text
 * and links into #root. The app boots as usual and takes over (react-helmet
 * replaces these tags, which carry data-rh for that).
 *
 *   page-preview.php?kind=course&lang=ar&slug=networking-foundations-ccna
 *   page-preview.php?kind=component&lang=&slug=pricing-cards
 *   page-preview.php?kind=market&type=themes&lang=&slug=aurora
 */

const API_BASE = 'https://api.frugaldomain.site/api';
const SITE = 'https://frugaldomain.site';
const CACHE_SECONDS = 3600;
const FAILURE_CACHE_SECONDS = 15;
const FETCH_TIMEOUT = 2.5;
const HREFLANG = ['en' => 'en', 'ar' => 'ar', 'hi' => 'hi', 'zh' => 'zh-Hans', 'tr' => 'tr'];
const OG_LOCALE = ['en' => 'en_US', 'ar' => 'ar_AR', 'hi' => 'hi_IN', 'zh' => 'zh_CN', 'tr' => 'tr_TR'];

// The bare app page; index.html is the prerendered home page.
$shell = is_readable(__DIR__ . '/app-shell.html') ? __DIR__ . '/app-shell.html' : __DIR__ . '/index.html';
if (! is_readable($shell)) {
    http_response_code(500);
    exit('Missing app-shell.html');
}
$html = file_get_contents($shell);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$lang = (string) ($_GET['lang'] ?? '');
$lang = isset(HREFLANG[$lang]) ? $lang : 'en';
$kind = (string) ($_GET['kind'] ?? '');
$slug = (string) ($_GET['slug'] ?? '');
$marketType = ($_GET['type'] ?? '') === 'apps' ? 'apps' : 'themes';

$page = match ($kind) {
    'course' => coursePage($lang, $slug),
    'component' => componentPage($lang, $slug),
    'market' => marketPage($lang, $slug, $marketType),
    default => ['status' => 404],
};

if (($page['status'] ?? 200) === 404) {
    // A real 404; the app still shows its own message.
    http_response_code(404);
}

if (! empty($page['title'])) {
    $urlOf = fn (string $l) => SITE . ($l === 'en' ? '' : '/' . $l) . $page['path'];
    $locales = $page['locales'];
    $indexable = in_array($lang, $locales, true);

    $html = rewriteHead($html, $lang, $page['title'], singleLine($page['description']), $urlOf($lang), $indexable, $page['og_type'] ?? 'website');
    $html = preg_replace('~<link rel="alternate" hreflang="[^"]*" href="[^"]*"[^>]*>~i', '', $html) ?? $html;
    $html = preg_replace('~<meta property="og:locale:alternate"[^>]*>~i', '', $html) ?? $html;
    $html = preg_replace('~<script type="application/ld\+json"[^>]*>.*?</script>~is', '', $html) ?? $html;

    $head = '';
    if (! preg_match('~<link rel="canonical"~i', $html)) {
        // The shell ships no canonical (react-helmet writes it); add one for crawlers.
        $head .= '<link rel="canonical" href="' . e($urlOf($lang)) . '" data-rh="true">';
    }
    if (! preg_match('~<meta name="robots"~i', $html)) {
        $head .= '<meta name="robots" content="' . ($indexable ? 'index,follow' : 'noindex,follow') . '" data-rh="true">';
    }
    if ($indexable && count($locales) > 1) {
        foreach ($locales as $l) {
            $head .= '<link rel="alternate" hreflang="' . HREFLANG[$l] . '" href="' . e($urlOf($l)) . '" data-rh="true">';
        }
        $head .= '<link rel="alternate" hreflang="x-default" href="' . e($urlOf('en')) . '" data-rh="true">';
    }
    $schema = $page['schema'];
    $schema['url'] = $urlOf($lang);
    $head .= '<script type="application/ld+json" data-rh="true">' . json(array_filter(['@context' => 'https://schema.org'] + $schema, fn ($v) => $v !== null && $v !== '' && $v !== [])) . '</script>';
    $head .= '<script type="application/ld+json" data-rh="true">' . json(breadcrumbs($lang, $page['crumbs'])) . '</script>';
    $html = insertInHead($html, $head);
}

$html = replaceBody($html, '<main dir="' . ($lang === 'ar' ? 'rtl' : 'ltr') . '" style="max-width:52rem;margin:0 auto;padding:96px 16px 48px;line-height:1.7">' . ($page['body'] ?? '') . '</main>');
echo $html;

/* ─────────────────────────── pages ─────────────────────────── */

function coursePage(string $lang, string $slug): array
{
    [$status, $data] = api($lang, '/learn/courses/' . rawurlencode($slug), $slug);
    $course = $data['data'] ?? null;
    if (! $course) {
        return ['status' => $status];
    }
    $prefix = $lang === 'en' ? '' : '/' . $lang;
    $t = label($lang);

    $body = '<p style="margin:0;font-size:13px;opacity:.7">' . e($t['learn']) . '</p>'
        . h1((string) $course['title'])
        . para((string) ($course['subtitle'] ?? ''), 'font-size:18px;opacity:.8');
    if (! empty($course['outcomes'])) {
        $body .= '<h2 style="margin-top:28px;font-size:20px">' . e($t['outcomes']) . '</h2>' . listOf((array) $course['outcomes']);
    }
    foreach ((array) ($course['stages'] ?? []) as $stage) {
        $body .= '<h2 style="margin-top:28px;font-size:20px">' . e((string) ($stage['title'] ?? '')) . '</h2>';
        foreach ((array) ($stage['units'] ?? []) as $unit) {
            $body .= '<h3 style="margin-top:16px;font-size:16px">' . e((string) ($unit['title'] ?? '')) . '</h3><ul style="padding-inline-start:20px">';
            foreach ((array) ($unit['lessons'] ?? []) as $lesson) {
                $href = $prefix . '/learn/' . rawurlencode($slug) . '/' . rawurlencode((string) ($lesson['slug'] ?? ''));
                $body .= '<li><a href="' . e($href) . '">' . e((string) ($lesson['title'] ?? '')) . '</a></li>';
            }
            $body .= '</ul>';
        }
    }

    return [
        'status' => 200,
        'path' => '/learn/' . $slug,
        // The api writes every course's title, outline and outcomes in all five languages.
        'locales' => array_keys(HREFLANG),
        'title' => $course['title'] . ' — ' . $t['learn'],
        'description' => (string) (($course['subtitle'] ?? '') ?: $course['title']),
        'og_type' => 'website',
        'schema' => [
            '@type' => 'Course',
            'name' => $course['title'],
            'description' => singleLine((string) (($course['subtitle'] ?? '') ?: $course['title'])),
            'inLanguage' => $course['language'] ?? 'en',
            'isAccessibleForFree' => true,
            'provider' => ['@type' => 'Organization', 'name' => 'FrugalDomain', 'sameAs' => SITE],
            'image' => $course['cover_url'] ?? null,
            'teaches' => ($course['outcomes'] ?? null) ?: null,
        ],
        'crumbs' => [[$t['home'], '/'], [$t['learn'], '/learn'], [$course['title'], '/learn/' . $slug]],
        'body' => $body,
    ];
}

function componentPage(string $lang, string $slug): array
{
    [$status, $data] = api('en', '/components/' . rawurlencode($slug), $slug);
    $c = $data['component'] ?? null;
    if (! $c) {
        return ['status' => $status];
    }
    $ar = $lang === 'ar';
    $name = (string) (($ar ? ($c['name_ar'] ?? '') : '') ?: $c['name']);
    $tagline = (string) (($ar ? ($c['tagline_ar'] ?? '') : '') ?: ($c['tagline'] ?? ''));
    $summary = (string) (($ar ? ($c['summary_ar'] ?? '') : '') ?: ($c['summary'] ?? ''));
    $t = label($lang);

    $features = [];
    foreach ((array) ($c['features'] ?? []) as $f) {
        $features[] = (string) (($ar ? ($f['label_ar'] ?? '') : '') ?: ($f['label'] ?? ''));
    }

    return [
        'status' => 200,
        'path' => '/components/' . $slug,
        // Only English and Arabic are written; hi / zh / tr show the English and stay out of the index.
        'locales' => ! empty($c['name_ar']) ? ['en', 'ar'] : ['en'],
        'title' => sprintf($t['component_title'], $name),
        'description' => $tagline ?: $summary ?: $name,
        'og_type' => 'article',
        'schema' => [
            '@type' => 'SoftwareSourceCode',
            'name' => $name,
            'description' => singleLine($tagline ?: $summary ?: $name),
            'programmingLanguage' => implode(', ', (array) ($c['stack'] ?? [])) ?: 'HTML',
            'keywords' => implode(', ', (array) ($c['tags'] ?? [])),
            'dateModified' => $c['updated'] ?? null,
            'isAccessibleForFree' => true,
        ],
        'crumbs' => [[$t['home'], '/'], [$t['components'], '/components'], [$name, '/components/' . $slug]],
        'body' => '<p style="margin:0;font-size:13px;opacity:.7">' . e($t['components']) . '</p>' . h1($name)
            . para($tagline, 'font-size:18px;opacity:.8') . para($summary) . listOf(array_filter($features)),
    ];
}

function marketPage(string $lang, string $slug, string $type): array
{
    [$status, $data] = api('en', '/market/items/' . rawurlencode($slug), $slug);
    $item = $data['data'] ?? null;
    if (! $item || ($item['type'] === 'app') !== ($type === 'apps')) {
        return ['status' => $item ? 404 : $status];
    }
    $local = $lang === 'ar' ? (array) ($item['translations']['ar'] ?? []) : [];
    $pick = fn (string $k) => (string) (($local[$k] ?? '') ?: ($item[$k] ?? ''));
    $features = array_filter((array) (($local['features'] ?? []) ?: ($item['features'] ?? [])));
    $t = label($lang);
    $section = $type === 'apps' ? $t['apps'] : $t['themes'];
    $name = $pick('name');
    $price = (float) $item['price'] > 0 ? number_format((float) $item['price'], 2, '.', '') : '0.00';

    return [
        'status' => 200,
        'path' => '/shopify/' . $type . '/' . $slug,
        'locales' => ! empty($item['translations']['ar']['name']) ? ['en', 'ar'] : ['en'],
        'title' => $pick('seo_title') ?: $name . ' — ' . $section,
        'description' => $pick('seo_description') ?: $pick('summary') ?: $name,
        'og_type' => 'product',
        'schema' => [
            '@type' => 'Product',
            'name' => $name,
            'description' => singleLine($pick('seo_description') ?: $pick('summary') ?: $name),
            'image' => $item['logo_url'] ?? null,
            'category' => $type === 'apps' ? 'Shopify app' : 'Shopify theme',
            'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock'],
        ],
        'crumbs' => [[$t['home'], '/'], [$section, '/shopify/' . $type], [$name, '/shopify/' . $type . '/' . $slug]],
        'body' => '<p style="margin:0;font-size:13px;opacity:.7">' . e($section) . '</p>' . h1($name)
            . para('$' . $price, 'font-size:20px;font-weight:600') . para($pick('summary'), 'font-size:18px;opacity:.8')
            . listOf($features) . para(strip_tags($pick('readme'))),
    ];
}

/* ─────────────────────────── helpers ─────────────────────────── */

/** Section names for titles and breadcrumbs, matching the site's own wording. */
function label(string $lang): array
{
    $all = [
        'en' => ['home' => 'Home', 'learn' => 'Learn', 'outcomes' => 'What you will learn', 'components' => 'Components', 'component_title' => '%s - HTML template | FrugalDomain', 'themes' => 'Shopify themes', 'apps' => 'Shopify apps'],
        'ar' => ['home' => 'الرئيسية', 'learn' => 'تعلّم', 'outcomes' => 'ما ستتعلمه', 'components' => 'المكوّنات', 'component_title' => '%s - قالب HTML | FrugalDomain', 'themes' => 'ثيمات Shopify', 'apps' => 'تطبيقات Shopify'],
        'hi' => ['home' => 'होम', 'learn' => 'सीखें', 'outcomes' => 'आप क्या सीखेंगे', 'components' => 'कंपोनेंट', 'component_title' => '%s - HTML टेम्पलेट | FrugalDomain', 'themes' => 'Shopify थीम', 'apps' => 'Shopify ऐप्स'],
        'zh' => ['home' => '首页', 'learn' => '学习', 'outcomes' => '你将学到', 'components' => '组件', 'component_title' => '%s - HTML 模板 | FrugalDomain', 'themes' => 'Shopify 主题', 'apps' => 'Shopify 应用'],
        'tr' => ['home' => 'Ana sayfa', 'learn' => 'Öğren', 'outcomes' => 'Neler öğreneceksiniz', 'components' => 'Bileşenler', 'component_title' => '%s - HTML şablonu | FrugalDomain', 'themes' => 'Shopify temaları', 'apps' => 'Shopify uygulamaları'],
    ];

    return $all[$lang];
}

/** [http status, decoded json or null]. Cached on disk so crawls never fan out into api requests. */
function api(string $lang, string $path, string $slug): array
{
    if (! preg_match('/^[A-Za-z0-9_-]{1,190}$/', $slug)) {
        return [404, null];
    }
    $cacheFile = sys_get_temp_dir() . '/fruga-page-' . sha1("$lang$path") . '.json';
    if (is_readable($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        $hit = is_array($cached) && ! empty($cached['data']);
        if (time() - filemtime($cacheFile) < ($hit ? CACHE_SECONDS : FAILURE_CACHE_SECONDS)) {
            return [$cached['status'] ?? 200, $hit ? $cached['data'] : null];
        }
    }

    $context = stream_context_create([
        'http' => ['timeout' => FETCH_TIMEOUT, 'ignore_errors' => true, 'header' => "Accept: application/json\r\nX-Language: $lang\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents(API_BASE . $path, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m)) {
            $status = (int) $m[1];
        }
    }
    $data = $status === 200 && $body ? json_decode($body, true) : null;
    // Only the api's own 404 is passed on; a timeout or 5xx must not tell Google the page is gone.
    $status = $status === 404 ? 404 : 200;
    @file_put_contents($cacheFile, json_encode(['status' => $status, 'data' => $data], JSON_UNESCAPED_UNICODE));

    return [$status, is_array($data) ? $data : null];
}

function breadcrumbs(string $lang, array $crumbs): array
{
    $prefix = $lang === 'en' ? '' : '/' . $lang;
    $items = [];
    foreach ($crumbs as $i => [$name, $path]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => SITE . $prefix . ($path === '/' && $prefix ? '' : $path)];
    }

    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function h1(string $text): string
{
    return '<h1 style="margin:8px 0 16px;font-size:30px;font-weight:700;line-height:1.25">' . e($text) . '</h1>';
}

function para(string $text, string $style = ''): string
{
    $text = trim($text);

    return $text === '' ? '' : '<p dir="auto"' . ($style ? ' style="' . $style . '"' : '') . '>' . nl2br(e($text)) . '</p>';
}

function listOf(array $items): string
{
    if (! $items) {
        return '';
    }
    $out = '<ul style="margin-top:16px;padding-inline-start:20px">';
    foreach ($items as $item) {
        $out .= '<li dir="auto">' . e((string) $item) . '</li>';
    }

    return $out . '</ul>';
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json(array $data): string
{
    return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
}

/** Google shows about 155 characters of a description. */
function singleLine(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

    return mb_strimwidth($text, 0, 160, '…', 'UTF-8');
}

/** Insert by position: preg_replace would read backslashes and dollars in the json. */
function insertInHead(string $html, string $markup): string
{
    $position = stripos($html, '</head>');

    return $position === false ? $html : substr($html, 0, $position) . $markup . substr($html, $position);
}

function rewriteHead(string $html, string $lang, string $title, string $description, string $canonical, bool $indexable, string $ogType): string
{
    [$t, $d, $u] = [e($title), e($description), e($canonical)];
    $robots = $indexable ? 'index,follow' : 'noindex,follow';
    $dir = $lang === 'ar' ? 'rtl' : 'ltr';

    $replacements = [
        '~<html lang="[^"]*" dir="[^"]*"~i' => '<html lang="' . HREFLANG[$lang] . '" dir="' . $dir . '"',
        '~<title>.*?</title>~is' => '<title>' . $t . '</title>',
        '~<meta name="description" content="[^"]*"~i' => '<meta name="description" content="' . $d . '"',
        '~<meta property="og:title" content="[^"]*"~i' => '<meta property="og:title" content="' . $t . '"',
        '~<meta property="og:description" content="[^"]*"~i' => '<meta property="og:description" content="' . $d . '"',
        '~<meta property="og:url" content="[^"]*"~i' => '<meta property="og:url" content="' . $u . '"',
        '~<meta property="og:type" content="[^"]*"~i' => '<meta property="og:type" content="' . $ogType . '"',
        '~<meta property="og:locale" content="[^"]*"~i' => '<meta property="og:locale" content="' . OG_LOCALE[$lang] . '"',
        '~<meta name="twitter:title" content="[^"]*"~i' => '<meta name="twitter:title" content="' . $t . '"',
        '~<meta name="twitter:description" content="[^"]*"~i' => '<meta name="twitter:description" content="' . $d . '"',
        '~<link rel="canonical" href="[^"]*"~i' => '<link rel="canonical" href="' . $u . '"',
        '~<meta name="robots" content="[^"]*"~i' => '<meta name="robots" content="' . $robots . '"',
        '~<meta name="googlebot" content="[^"]*"~i' => '<meta name="googlebot" content="' . $robots . ',max-snippet:-1,max-image-preview:large,max-video-preview:-1"',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $html = preg_replace($pattern, $replacement, $html, 1) ?? $html;
    }

    return $html;
}

/** Fill #root; its closing tag is the last </div> before </body>. */
function replaceBody(string $html, string $inner): string
{
    $start = stripos($html, '<div id="root"');
    $openEnds = $start === false ? false : strpos($html, '>', $start);
    $bodyEnds = stripos($html, '</body>');
    if ($openEnds === false || $bodyEnds === false) {
        return $html;
    }
    $lastClose = strripos(substr($html, 0, $bodyEnds), '</div>');
    if ($lastClose === false || $lastClose <= $openEnds) {
        return $html;
    }

    return substr($html, 0, $openEnds + 1) . $inner . substr($html, $lastClose);
}
