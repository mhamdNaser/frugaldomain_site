<?php

/**
 * The five home pages (/, /ar, /hi, /zh, /tr): the language's prerendered
 * index.html with the admin's home sections as real text inside its <main>
 * (what a crawler reads; header, nav and footer stay) and as
 * window.__HOME_SECTIONS__ (what the app reads).
 * No saved sections, or a failing api: the prerendered file, unchanged.
 */

require __DIR__ . '/scripts/appearance-lib.php';

$lang = (string) ($_GET['lang'] ?? '');
$lang = in_array($lang, ['ar', 'hi', 'zh', 'tr'], true) ? $lang : 'en';
$path = __DIR__ . ($lang === 'en' ? '' : '/' . $lang) . '/index.html';
if (! is_readable($path)) {
    http_response_code(500);
    exit('Missing index.html');
}

$html = file_get_contents($path);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$sections = fg_fetch_appearance()['home_sections'] ?? [];
echo $sections ? fg_home_page($html, $sections, $lang) : $html;
