<?php

/**
 * /appearance.css: the admin's theme as css custom properties. Linked
 * render-blocking from every page so the first paint already has the colours.
 * Cached on disk for a minute; a failing api serves the stale copy, then the
 * built-in defaults.
 */

require __DIR__ . '/scripts/appearance-lib.php';

const APPEARANCE_URL = 'https://api.frugaldomain.site/api/site/appearance';
const APPEARANCE_TTL = 60;
const APPEARANCE_TIMEOUT = 2.5;

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: public, max-age=60');

const APPEARANCE_FAILURE_TTL = 15;

$cacheFile = sys_get_temp_dir() . '/fruga-appearance.json';
$raw = @file_get_contents($cacheFile);
$cached = $raw === false ? null : json_decode($raw, true);
$theme = is_array($cached['theme'] ?? null) ? $cached['theme'] : null;
$mtime = $raw === false ? 0 : (int) @filemtime($cacheFile);
// A failure marker, a missing theme or a corrupt file is only trusted for the short window.
$ttl = $theme && empty($cached['failed']) ? APPEARANCE_TTL : APPEARANCE_FAILURE_TTL;

if (! $mtime || time() - $mtime >= $ttl) {
    $context = stream_context_create([
        'http' => ['timeout' => APPEARANCE_TIMEOUT, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents(APPEARANCE_URL, false, $context);
    $data = $body ? json_decode($body, true) : null;
    if (is_array($data['theme'] ?? null)) {
        $theme = $data['theme'];
        @file_put_contents($cacheFile, json_encode(['theme' => $theme]));
    } else {
        // Keep the stale theme (or none) and do not refetch for a few seconds.
        @file_put_contents($cacheFile, json_encode(['theme' => $theme, 'failed' => true]));
    }
}

echo fg_css($theme);
