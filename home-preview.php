<?php

/**
 * The five home pages (/, /ar, /hi, /zh, /tr): the language's prerendered
 * index.html with the admin's home sections as real text in #root (what a
 * crawler reads) and as window.__HOME_SECTIONS__ (what the app reads).
 * No sections, or a failing api: the prerendered file, unchanged.
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
$text = $sections ? fg_home_html($sections, $lang) : '';
if ($text === '') {
    echo $html;
    exit;
}

$start = stripos($html, '<div id="root"');
$openEnds = $start === false ? false : strpos($html, '>', $start);
$bodyEnds = stripos($html, '</body>');
$headEnds = stripos($html, '</head>');
$lastClose = $bodyEnds === false ? false : strripos(substr($html, 0, $bodyEnds), '</div>');
if ($openEnds === false || $headEnds === false || $lastClose === false || $lastClose <= $openEnds) {
    echo $html;
    exit;
}

// By position, not preg_replace: the json holds backslashes and dollars.
$script = '<script>window.__HOME_SECTIONS__=' . fg_home_json($sections) . ';</script>';
echo substr($html, 0, $headEnds) . $script . substr($html, $headEnds, $openEnds + 1 - $headEnds) . $text . substr($html, $lastClose);
