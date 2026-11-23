<?php

require __DIR__ . '/appearance-lib.php';

function check(bool $ok, string $what): void
{
    if (! $ok) {
        fwrite(STDERR, "FAIL: $what\n");
        exit(1);
    }
}

$s = fn ($id, $i18n, $visible = true) => ['id' => $id, 'type' => 'x', 'visible' => $visible, 'props' => [], 'i18n' => $i18n];
$sections = [
    $s('a', ['en' => ['headline' => 'Hello', 'subtitle' => 'Sub'], 'ar' => ['headline' => 'مرحبا']]),
    $s('b', ['en' => ['title' => 'English only', 'text' => 'Body']]),
    $s('c', ['hi' => ['headline' => 'no en']]),
    $s('d', ['en' => ['headline' => 'Hidden']], false),
    $s('e', ['en' => ['headline' => '<img onerror=x>', 'description' => 5]]),
];

$ar = fg_home_html($sections, 'ar');
check(str_contains($ar, '<h2>مرحبا</h2>'), 'ar text used');
check(! str_contains($ar, 'Sub'), 'ar section does not mix in en subtitle');
check(str_contains($ar, '<h2>English only</h2>') && str_contains($ar, '<p>Body</p>'), 'en fallback');
check(! str_contains($ar, 'no en'), 'no en omitted');
check(! str_contains($ar, 'Hidden'), 'invisible omitted');
check(str_contains($ar, '&lt;img onerror=x&gt;') && ! str_contains($ar, '<img'), 'escaped');
check(! str_contains($ar, '<p></p>') && substr_count($ar, '<section>') === 3, 'non-strings skipped');
check(str_contains(fg_home_html($sections, 'en'), '<p>Sub</p>'), 'en subtitle');
check(fg_home_html([], 'en') === '', 'empty');

// Types whose component renders nothing, and the hero as the page's one h1.
$typed = fn ($type, $en, $props = []) => ['id' => $type, 'type' => $type, 'visible' => true, 'props' => $props, 'i18n' => ['en' => $en]];
$skip = fg_home_html([
    $typed('continue_learning', ['title' => 'Resume me']),
    $typed('testimonials', ['title' => 'Quotes none', 'quotes' => []]),
    $typed('faq', ['title' => 'Faq half', 'items' => [['q' => 'Q', 'a' => '']]]),
    $typed('stats_bar', ['items' => [['label' => 'Custom empty']]], ['items' => [['source' => 'custom', 'value' => '']]]),
    $typed('feature_cards', ['title' => 'Cards none', 'cards' => [['body' => 'no title']]]),
], 'en');
check($skip === '', 'empty-rendering sections and continue_learning skipped');
$kept = fg_home_html([
    $typed('stats_bar', ['title' => 'Stats', 'items' => [['label' => 'Icons']]], ['items' => [['source' => 'icons']]]),
    $typed('faq', ['title' => 'Faq full', 'items' => [['q' => 'Q', 'a' => 'A']]]),
    $typed('testimonials', ['title' => 'Quotes one', 'quotes' => [['quote' => 'Nice']]]),
], 'en');
check(str_contains($kept, 'Faq full') && str_contains($kept, 'Quotes one') && str_contains($kept, 'Stats'), 'non-empty list sections kept');
$hero = fn ($t, $h) => $typed('hero_paths', ['title' => $t, 'highlight' => $h, 'subtitle' => 'S']);
$two = fg_home_html([$hero('Everything you need to', 'design & build'), $hero('Second', 'hero')], 'en');
check(substr_count($two, '<h1>') === 1 && str_contains($two, '<h1>Everything you need to design &amp; build</h1>'), 'first hero is the one h1');
check(str_contains($two, '<h2>Second</h2>'), 'later hero is an h2');

$page = '<html><head><title>x</title></head><body><div id="root"><header><a href="/tools">Tools</a></header>'
    . '<main class="fg-page"><section><h1>Old</h1></section></main><footer>Foot</footer></div><script src="/a.js"></script></body></html>';
$out = fg_home_page($page, [$hero('New', 'title')], 'en');
check(str_contains($out, '<header><a href="/tools">Tools</a></header>') && str_contains($out, '<footer>Foot</footer>'), 'chrome kept');
check(str_contains($out, '<main class="fg-page"><section><h1>New title</h1>') && ! str_contains($out, 'Old'), 'main content replaced');
check(substr_count($out, '<h1>') === 1, 'one h1');
check(strpos($out, '__HOME_SECTIONS__') < strpos($out, '</head>'), 'script in head');
check(str_contains($out, '<script src="/a.js"></script></body>'), 'tail kept');
$none = fg_home_page($page, [$typed('continue_learning', ['title' => 'R'])], 'en');
check(str_contains($none, '__HOME_SECTIONS__') && str_contains($none, '<main class="fg-page"></main>'), 'script even with no crawlable text');
$noMain = '<html><head></head><body><div id="root"><p>Hi</p></div></body></html>';
$out = fg_home_page($noMain, [$hero('New', 'x')], 'en');
check(str_replace(substr($out, strpos($out, '<script>'), strpos($out, '</script>') + 9 - strpos($out, '<script>')), '', $out) === $noMain, 'no main: page unchanged plus script');

$json = fg_home_json([$s('x', ['en' => ['headline' => '</script><b>']])]);
check(str_contains($json, '\u003C/script\u003E') && ! str_contains($json, '</script>'), 'json hex tag');

echo "ok\n";
