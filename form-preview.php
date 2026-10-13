<?php

/**
 * The link preview for a shared form.
 *
 * A form is handed out as a link pasted into a message, and the card that
 * message shows is the only thing most people judge it by before tapping. The
 * site is a single-page app behind an `index.html` fallback, so that card was
 * being built from the home page's tags: every form, whatever it asked for,
 * previewed as "Fruga - online forms, file converters, icons". For a form
 * asking a family for a national id and a photograph of their family book,
 * a preview that does not name the form is a preview that looks like a trap.
 *
 * Crawlers do not run javascript, so the tags have to be in the html the
 * server sends. This script serves the app's own `index.html` with the head
 * rewritten for the form being asked for - the same document a person gets,
 * so React boots over it exactly as before.
 *
 * It is deliberately small: no framework, one cached read of the public form
 * endpoint, and a fall back to the untouched page whenever anything is not
 * right. A preview is worth having, never worth a blank page.
 */

const API_BASE = 'https://api.frugaldomain.site/api';
const CACHE_SECONDS = 300;
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
        ? ($form['title_ar'] ?: $form['title'])
        : ($form['title'] ?: $form['title_ar']);

    $description = $isArabic
        ? ($form['description_ar'] ?: $form['description'])
        : ($form['description'] ?: $form['description_ar']);

    if (! $description) {
        $description = $isArabic
            ? 'استمارة إلكترونية - اضغط الرابط لتعبئتها.'
            : 'An online form - open the link to fill it in.';
    }

    $canonical = 'https://frugaldomain.site' . ($isArabic ? '/ar' : '') . '/f/' . rawurlencode($form['slug'] ?: $handle);

    $html = rewriteHead($html, trim($title), trim(singleLine($description)), $canonical);
}

echo $html;

/* ─────────────────────────────────────────────────────────────────── */

/**
 * The form behind this link, or null.
 *
 * Cached on disk for five minutes: a link posted to a group is fetched by
 * every client that renders a preview, and none of them should turn into a
 * request to the api. A failed read is cached as well - briefly - so an api
 * that is down cannot be hammered by the same burst.
 */
function formFor(string $handle): ?array
{
    if ($handle === '' || ! preg_match('/^[A-Za-z0-9_-]{1,120}$/', $handle)) {
        return null;
    }

    $cacheFile = sys_get_temp_dir() . '/fruga-form-' . sha1($handle) . '.json';

    if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_SECONDS) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);

        return is_array($cached) && $cached ? $cached : null;
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
    $form = is_array($data) && isset($data['data']) ? $data['data'] : null;

    $keep = $form ? [
        'slug' => (string) ($form['slug'] ?? $handle),
        'title' => (string) ($form['title'] ?? ''),
        'title_ar' => (string) ($form['title_ar'] ?? ''),
        'description' => (string) ($form['description'] ?? ''),
        'description_ar' => (string) ($form['description_ar'] ?? ''),
    ] : [];

    @file_put_contents($cacheFile, json_encode($keep, JSON_UNESCAPED_UNICODE));

    return $keep ?: null;
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
 * Only the tags that decide the card are touched; everything else the build
 * produced - the stylesheet links, the module preloads, the structured data -
 * is left exactly as it is, because this document still has to boot the app.
 *
 * `noindex` is deliberate. A form is handed to the people it is meant for,
 * and a search engine listing one that collects identity numbers is a leak
 * of who is collecting what from whom. Messaging apps read the card anyway;
 * they are not search engines.
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
        // A pattern that does not match leaves the page as it was rather than
        // blanking it, which is what preg_replace returns on failure.
        if ($result !== null) {
            $html = $result;
        }
    }

    // The build may not have emitted a robots tag at all.
    if (! preg_match('~<meta name="robots"~i', $html)) {
        $html = preg_replace('~<head([^>]*)>~i', '<head$1><meta name="robots" content="noindex,nofollow">', $html, 1) ?? $html;
    }

    return $html;
}
