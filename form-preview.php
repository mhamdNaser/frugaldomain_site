<?php

/**
 * What a shared form link is served.
 *
 * A form is handed out as a link pasted into a message, and three different
 * things read that link: the app that draws a preview card, the person who
 * taps it, and a search engine that should not index it at all. All three get
 * their answer from here, because the site is a single page behind an
 * `index.html` fallback and none of them run javascript.
 *
 * `index.html` is the *prerendered home page*. Serving it for a form link
 * meant the browser drew the whole home page - hero, sections, seventy
 * kilobytes of it - and then replaced it with the form once the chunk arrived.
 * The router makes that worse on purpose: it reuses the server-rendered markup
 * as the placeholder while a route's chunk loads, keyed on the canonical url
 * matching the address. Rewriting the canonical to the form's own url, which
 * is right for the preview card, is what told it the home page *was* this
 * page.
 *
 * So the body is replaced here too: the head keeps the scripts and styles that
 * boot the app, and `#root` carries a skeleton of the form instead of a
 * different page. The router then shows that skeleton while the chunk loads,
 * and the form takes its place - one page, start to finish.
 *
 * The form's own data is embedded as well. The schema has already been fetched
 * to write the title; sending it with the page saves the browser a second
 * round trip to the api before it can draw anything, which on a slow
 * connection is the difference between a form and a spinner.
 */

const API_BASE = 'https://api.frugaldomain.site/api';
const CACHE_SECONDS = 300;
// A failure is remembered for seconds, not minutes. Caching one for as long as
// a success means a single hiccup from the api serves every visitor a page
// with no title and no form embedded for the next five minutes.
const FAILURE_CACHE_SECONDS = 15;
const FETCH_TIMEOUT = 2.5;

$documentRoot = __DIR__;
$indexPath = $documentRoot . '/index.html';

if (! is_readable($indexPath)) {
    http_response_code(500);
    exit('Missing index.html');
}

$html = file_get_contents($indexPath);

$handle = (string) ($_GET['handle'] ?? '');
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$isArabic = str_starts_with($requestUri, '/ar/');

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$form = formFor($handle);

if ($form) {
    $title = $isArabic
        ? (($form['title_ar'] ?? '') ?: ($form['title'] ?? ''))
        : (($form['title'] ?? '') ?: ($form['title_ar'] ?? ''));

    $description = $isArabic
        ? (($form['description_ar'] ?? '') ?: ($form['description'] ?? ''))
        : (($form['description'] ?? '') ?: ($form['description_ar'] ?? ''));

    if (! $description) {
        $description = $isArabic
            ? 'استمارة إلكترونية - اضغط الرابط لتعبئتها.'
            : 'An online form - open the link to fill it in.';
    }

    $slug = ($form['slug'] ?? '') ?: $handle;
    $canonical = 'https://frugaldomain.site' . ($isArabic ? '/ar' : '') . '/f/' . rawurlencode($slug);

    $html = rewriteHead($html, trim((string) $title), trim(singleLine((string) $description)), $canonical);
    $html = embedForm($html, $form);
}

// Whether or not the form was found, the home page's markup has no business
// being drawn here.
$html = replaceBody($html, skeleton($isArabic, $form));
$html = preloadFormChunks($html, $documentRoot);

echo $html;

/* ─────────────────────────────────────────────────────────────────── */

/**
 * The form behind this link, whole, or null.
 *
 * The entire schema is kept rather than the few fields the card needs,
 * because the page is served with it embedded. Cached on disk for five
 * minutes: a link posted to a group is fetched by every client that draws a
 * preview, and none of them should become a request to the api. The browser
 * revalidates in the background once the page is running, so a form edited in
 * the meantime is still correct on screen - the cache only decides how fast
 * the first paint is, not what is true.
 */
function formFor(string $handle): ?array
{
    if ($handle === '' || ! preg_match('/^[A-Za-z0-9_-]{1,120}$/', $handle)) {
        return null;
    }

    $cacheFile = sys_get_temp_dir() . '/fruga-form-' . sha1($handle) . '.json';

    if (is_readable($cacheFile)) {
        $age = time() - filemtime($cacheFile);
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        $hit = is_array($cached) && $cached;

        if ($age < ($hit ? CACHE_SECONDS : FAILURE_CACHE_SECONDS)) {
            return $hit ? $cached : null;
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => FETCH_TIMEOUT,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $body = @file_get_contents(API_BASE . '/f/' . rawurlencode($handle), false, $context);
    $data = $body ? json_decode($body, true) : null;
    $form = is_array($data) && isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

    @file_put_contents($cacheFile, json_encode($form, JSON_UNESCAPED_UNICODE));

    return $form ?: null;
}

/**
 * Put something in just before </head>.
 *
 * Done by position, because `preg_replace` reads its replacement: a backslash
 * or a dollar sign in the text is taken as an escape or a backreference. The
 * embedded form is json full of < escapes, and passing it through a
 * regex replacement quietly ate the backslashes and produced a script the
 * browser could not parse.
 */
function insertInHead(string $html, string $markup): string
{
    $position = stripos($html, '</head>');

    if ($position === false) {
        return $html;
    }

    return substr($html, 0, $position) . $markup . substr($html, $position);
}

/** A description for a card is one line, however it was typed. */
function singleLine(string $text): string
{
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return mb_strimwidth($text, 0, 200, '…', 'UTF-8');
}

/**
 * Put this form's identity into the page's head.
 *
 * Only the tags that decide the card are touched; everything the build
 * produced - the stylesheet links, the module preloads - is left alone,
 * because this document still has to boot the app.
 *
 * `noindex` is deliberate. A form is handed to the people it is meant for, and
 * a search engine listing one that collects identity numbers is a leak of who
 * is collecting what from whom. Messaging apps read the card anyway; they are
 * not search engines.
 */
function rewriteHead(string $html, string $title, string $description, string $canonical): string
{
    $t = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $d = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $u = htmlspecialchars($canonical, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $replacements = [
        '~<title>.*?</title>~is' => '<title>' . $t . '</title>',
        '~<meta name="description" content="[^"]*"~i' => '<meta name="description" content="' . $d . '"',
        '~<meta property="og:title" content="[^"]*"~i' => '<meta property="og:title" content="' . $t . '"',
        '~<meta property="og:description" content="[^"]*"~i' => '<meta property="og:description" content="' . $d . '"',
        '~<meta property="og:url" content="[^"]*"~i' => '<meta property="og:url" content="' . $u . '"',
        '~<meta name="twitter:title" content="[^"]*"~i' => '<meta name="twitter:title" content="' . $t . '"',
        '~<meta name="twitter:description" content="[^"]*"~i' => '<meta name="twitter:description" content="' . $d . '"',
        '~<link rel="canonical" href="[^"]*"~i' => '<link rel="canonical" href="' . $u . '"',
        '~<meta name="robots" content="[^"]*"~i' => '<meta name="robots" content="noindex,nofollow"',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $result = preg_replace($pattern, $replacement, $html, 1);
        if ($result !== null) {
            $html = $result;
        }
    }

    if (! preg_match('~<meta name="robots"~i', $html)) {
        $html = preg_replace('~<head([^>]*)>~i', '<head$1><meta name="robots" content="noindex,nofollow">', $html, 1) ?? $html;
    }

    return $html;
}

/**
 * Hand the page the form it is about to draw.
 *
 * Without this the browser parses the html, downloads the app, works out which
 * route it is on, and only then asks the api what the questions are - four
 * steps in series before a single field appears.
 */
function embedForm(string $html, array $form): string
{
    $json = json_encode(
        $form,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    if ($json === false) {
        return $html;
    }

    return insertInHead($html, '<script>window.__FRUGA_FORM__=' . $json . ';</script>');
}

/**
 * Start the form's own chunks downloading with the page, not after it.
 *
 * They are named with a build fingerprint, so they are found on disk rather
 * than guessed. Without this the browser only learns they exist once the entry
 * bundle has parsed and the router has resolved the route - two round trips
 * later, on a connection where round trips are what hurt.
 */
function preloadFormChunks(string $html, string $documentRoot): string
{
    $links = '';

    foreach (['PublicForm', 'PublicFormLayout'] as $chunk) {
        $matches = glob($documentRoot . '/assets/' . $chunk . '-*.js') ?: [];

        if (! $matches) {
            continue;
        }

        // Newest first: two generations of a chunk are kept so a tab open
        // across a deploy still works, and the current build is the later one.
        usort($matches, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $links .= '<link rel="modulepreload" crossorigin href="/assets/' . basename($matches[0]) . '">';
    }

    return $links ? insertInHead($html, $links) : $html;
}

/**
 * Swap the prerendered page inside #root for something that belongs here.
 *
 * Found by position rather than by pattern. The prerendered home page holds
 * ninety-six closing divs and no script tag after the root, so a lazy regex
 * stops at the first one and leaves most of the page behind - which is how
 * this was wrong the first time. The root element is the last container
 * before </body>, so its closing tag is the last </div> before it.
 */
function replaceBody(string $html, string $inner): string
{
    $start = stripos($html, '<div id="root"');

    if ($start === false) {
        return $html;
    }

    $openEnds = strpos($html, '>', $start);
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
 * The shape of the page, before the page exists.
 *
 * Drawn from the real thing - the same slim header, the same single column,
 * the same card - so what appears first and what replaces it are the same
 * layout and nothing moves. The title is real when it is known, because a
 * person who tapped a link wants to see they arrived at the right place
 * before anything else has loaded.
 */
function skeleton(bool $isArabic, ?array $form): string
{
    $direction = $isArabic ? 'rtl' : 'ltr';

    $title = '';
    if ($form) {
        $title = $isArabic
            ? (($form['title_ar'] ?? '') ?: ($form['title'] ?? ''))
            : (($form['title'] ?? '') ?: ($form['title_ar'] ?? ''));
    }

    $heading = $title
        ? '<h1 style="margin:0 0 20px;font-size:22px;font-weight:800;line-height:1.35">'
            . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>'
        : '<div style="height:26px;width:62%;border-radius:8px;background:currentColor;opacity:.10;margin-bottom:20px"></div>';

    $line = static function (string $width, string $height = '42px', string $space = '16px'): string {
        return '<div style="height:' . $height . ';width:' . $width . ';border-radius:12px;'
            . 'background:currentColor;opacity:.08;margin-bottom:' . $space . '"></div>';
    };

    return '<div dir="' . $direction . '" style="min-height:100vh;display:flex;flex-direction:column">'
        . '<header style="border-bottom:1px solid currentColor;border-color:rgba(128,128,128,.22)">'
        . '<div style="max-width:42rem;margin:0 auto;padding:10px 16px;display:flex;align-items:center;gap:8px">'
        . '<img src="/images/logo.svg" alt="" width="32" height="32" style="height:32px;width:32px;border-radius:8px">'
        . '<span style="font-size:15px;font-weight:800;letter-spacing:-.01em">Fruga</span>'
        . '</div></header>'
        . '<main style="margin:0 auto;width:100%;max-width:42rem;padding:40px 16px">'
        . '<div style="border:1px solid rgba(128,128,128,.22);border-radius:16px;padding:24px">'
        . $heading
        . $line('34%', '13px', '10px') . $line('100%')
        . $line('28%', '13px', '10px') . $line('100%')
        . $line('40%', '13px', '10px') . $line('100%', '42px', '0')
        . '</div></main></div>';
}
