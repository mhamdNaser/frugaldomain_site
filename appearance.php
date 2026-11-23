<?php

/**
 * /appearance.css: the admin's theme as css custom properties. Linked
 * render-blocking from every page so the first paint already has the colours.
 * Cached on disk for a minute; a failing api serves the stale copy, then the
 * built-in defaults.
 */

require __DIR__ . '/scripts/appearance-lib.php';

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: public, max-age=60');

$theme = fg_fetch_appearance()['theme'] ?? null;

echo fg_css($theme);
