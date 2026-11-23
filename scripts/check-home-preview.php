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

$json = fg_home_json([$s('x', ['en' => ['headline' => '</script><b>']])]);
check(str_contains($json, '\u003C/script\u003E') && ! str_contains($json, '</script>'), 'json hex tag');

echo "ok\n";
