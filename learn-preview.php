<?php

/**
 * What a lesson's address is served.
 *
 * The site is a single page behind an `index.html` fallback, and that file is
 * the prerendered *home page*: without this, a search engine fetching any of
 * the lessons read the home page's title and description, in English, at
 * every lesson address in every language. Same approach as form-preview.php:
 * fetch the lesson from the api in the address's language, write its title,
 * description, canonical and hreflang into the head, and put its title,
 * summary and objectives in #root as real text. The app boots as usual and
 * takes over (react-helmet replaces these tags, which carry data-rh for that).
 */

const API_BASE = 'https://api.frugaldomain.site/api';
const SITE = 'https://frugaldomain.site';
const CACHE_SECONDS = 3600;
const FAILURE_CACHE_SECONDS = 15;
const FETCH_TIMEOUT = 2.5;
const HREFLANG = ['en' => 'en', 'ar' => 'ar', 'hi' => 'hi', 'zh' => 'zh-Hans', 'tr' => 'tr'];
const OG_LOCALE = ['en' => 'en_US', 'ar' => 'ar_AR', 'hi' => 'hi_IN', 'zh' => 'zh_CN', 'tr' => 'tr_TR'];

$indexPath = __DIR__ . '/index.html';
if (! is_readable($indexPath)) {
    http_response_code(500);
    exit('Missing index.html');
}

$html = file_get_contents($indexPath);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$lang = (string) ($_GET['lang'] ?? '');
$lang = isset(HREFLANG[$lang]) ? $lang : 'en';
$course = (string) ($_GET['course'] ?? '');
$slug = (string) ($_GET['lesson'] ?? '');

[$status, $lesson] = lessonFor($lang, $course, $slug);

if ($status === 404) {
    // A real 404, not the home page answered 200; the app still shows its own message.
    http_response_code(404);
}

if ($lesson) {
    $path = '/learn/' . $course . '/' . $slug;
    $urlOf = fn (string $l) => SITE . ($l === 'en' ? '' : '/' . $l) . $path;
    // `locales` is newer than has_ar/content_language; an older api still gets the languages right.
    $known = $lesson['locales'] ?? ['en', ! empty($lesson['has_ar']) ? 'ar' : 'en', $lesson['content_language'] ?? 'en'];
    $locales = array_values(array_intersect(array_keys(HREFLANG), (array) $known));
    // Not yet written in this language (served in English), or a review pointer: kept out of the index.
    $indexable = empty($lesson['review']) && in_array($lang, $locales, true);

    // Same as the page's own <title> (LessonPage.jsx).
    $title = implode(' — ', array_filter([(string) ($lesson['title'] ?? ''), (string) ($lesson['course']['title'] ?? '')]));
    $description = singleLine((string) (($lesson['summary'] ?? '') ?: ($lesson['title'] ?? '')));

    $html = rewriteHead($html, $lang, $title, $description, $urlOf($lang), $indexable);
    $html = preg_replace('~<link rel="alternate" hreflang="[^"]*" href="[^"]*"[^>]*>~i', '', $html) ?? $html;
    $html = preg_replace('~<meta property="og:locale:alternate"[^>]*>~i', '', $html) ?? $html;
    $html = preg_replace('~<script type="application/ld\+json"[^>]*>.*?</script>~is', '', $html) ?? $html;

    $head = '';
    if ($indexable && count($locales) > 1) {
        foreach ($locales as $l) {
            $head .= '<link rel="alternate" hreflang="' . HREFLANG[$l] . '" href="' . e($urlOf($l)) . '" data-rh="true">';
        }
        $head .= '<link rel="alternate" hreflang="x-default" href="' . e($urlOf('en')) . '" data-rh="true">';
    }
    $head .= '<script type="application/ld+json" data-rh="true">' . json(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LearningResource',
        'name' => $lesson['title'] ?? '',
        'description' => $description,
        'url' => $urlOf($lang),
        'inLanguage' => $lesson['content_language'] ?? 'en',
        'learningResourceType' => 'Lesson',
        'isAccessibleForFree' => true,
        'timeRequired' => ! empty($lesson['duration_minutes']) ? 'PT' . (int) $lesson['duration_minutes'] . 'M' : null,
        'dateModified' => $lesson['updated_at'] ?? null,
        'teaches' => ($lesson['objectives'] ?? null) ?: null,
        'isPartOf' => ['@type' => 'Course', 'name' => $lesson['course']['title'] ?? '', 'url' => SITE . ($lang === 'en' ? '' : '/' . $lang) . '/learn/' . $course],
    ])) . '</script>';
    $html = insertInHead($html, $head);
}

$html = replaceBody($html, body($lang, $lesson));
echo $html;

/* ─────────────────────────────────────────────────────────────────── */

/** [http status, lesson or null]. Cached on disk so crawls never fan out into api requests. */
function lessonFor(string $lang, string $course, string $slug): array
{
    $valid = '/^[A-Za-z0-9_-]{1,190}$/';
    if (! preg_match($valid, $course) || ! preg_match($valid, $slug)) {
        return [404, null];
    }

    $cacheFile = sys_get_temp_dir() . '/fruga-lesson-' . sha1("$lang/$course/$slug") . '.json';
    if (is_readable($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        $hit = is_array($cached) && ! empty($cached['lesson']);
        if (time() - filemtime($cacheFile) < ($hit ? CACHE_SECONDS : FAILURE_CACHE_SECONDS)) {
            return [$cached['status'] ?? 200, $hit ? $cached['lesson'] : null];
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => FETCH_TIMEOUT,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nX-Language: $lang\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $url = API_BASE . '/learn/courses/' . rawurlencode($course) . '/lessons/' . rawurlencode($slug);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m)) {
            $status = (int) $m[1];
        }
    }
    $data = $body ? json_decode($body, true) : null;
    $lesson = $status === 200 && is_array($data['data'] ?? null) ? $data['data'] : null;
    // Only the api's own 404 is passed on; a timeout or 5xx must not tell Google the lesson is gone.
    $status = $status === 404 ? 404 : 200;

    @file_put_contents($cacheFile, json_encode(['status' => $status, 'lesson' => $lesson], JSON_UNESCAPED_UNICODE));

    return [$status, $lesson];
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

function rewriteHead(string $html, string $lang, string $title, string $description, string $canonical, bool $indexable): string
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
        '~<meta property="og:type" content="[^"]*"~i' => '<meta property="og:type" content="article"',
        '~<meta property="og:locale" content="[^"]*"~i' => '<meta property="og:locale" content="' . OG_LOCALE[$lang] . '"',
        '~<meta property="og:image:alt" content="[^"]*"~i' => '<meta property="og:image:alt" content="' . $t . '"',
        '~<meta name="twitter:title" content="[^"]*"~i' => '<meta name="twitter:title" content="' . $t . '"',
        '~<meta name="twitter:description" content="[^"]*"~i' => '<meta name="twitter:description" content="' . $d . '"',
        '~<meta name="twitter:image:alt" content="[^"]*"~i' => '<meta name="twitter:image:alt" content="' . $t . '"',
        '~<link rel="canonical" href="[^"]*"~i' => '<link rel="canonical" href="' . $u . '"',
        '~<meta name="robots" content="[^"]*"~i' => '<meta name="robots" content="' . $robots . '"',
        '~<meta name="googlebot" content="[^"]*"~i' => '<meta name="googlebot" content="' . $robots . ',max-snippet:-1,max-image-preview:large,max-video-preview:-1"',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $html = preg_replace($pattern, $replacement, $html, 1) ?? $html;
    }

    return $html;
}

/** Swap the prerendered home page inside #root; its closing tag is the last </div> before </body>. */
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

/**
 * The lesson as plain text: what a crawler indexes, and what the router shows
 * while the lesson's chunk loads (it reuses #root when the canonical matches).
 */
function body(string $lang, ?array $lesson): string
{
    $dir = $lang === 'ar' ? 'rtl' : 'ltr';
    $out = '<main dir="' . $dir . '" style="max-width:48rem;margin:0 auto;padding:96px 16px 48px;line-height:1.7">';

    if ($lesson) {
        $content = e((string) ($lesson['content_language'] ?? 'en'));
        $out .= '<p style="margin:0;font-size:13px;opacity:.7">' . e((string) ($lesson['course']['title'] ?? '')) . '</p>'
            . '<h1 style="margin:8px 0 16px;font-size:30px;font-weight:700;line-height:1.25">' . e((string) ($lesson['title'] ?? '')) . '</h1>';
        if (! empty($lesson['summary'])) {
            $out .= '<p lang="' . $content . '" style="font-size:18px;opacity:.8">' . e((string) $lesson['summary']) . '</p>';
        }
        if (! empty($lesson['objectives']) && is_array($lesson['objectives'])) {
            $out .= '<ul lang="' . $content . '" style="margin-top:20px;padding-inline-start:20px">';
            foreach ($lesson['objectives'] as $objective) {
                $out .= '<li>' . e((string) $objective) . '</li>';
            }
            $out .= '</ul>';
        }
    }

    return $out . '</main>';
}
