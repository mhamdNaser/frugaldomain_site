<?php
require __DIR__ . '/appearance-lib.php';

function check(bool $ok, string $what): void
{
    if (! $ok) { fwrite(STDERR, "FAIL: $what\n"); exit(1); }
}

check(fg_triplet('#c9a96e') === '201 169 110', 'triplet');
check(fg_mix('#000000', '#ffffff', 0.5) === '#808080', 'mix');

$css = fg_css(fg_defaults());
check(str_contains($css, '--fg-accent: 154 122 63'), 'light accent');
$dark = substr($css, strpos($css, ':root[data-theme="dark"]'));
check(str_contains($dark, '--fg-accent: 201 169 110'), 'dark accent');
check(str_contains($css, 'IBM+Plex+Sans+Arabic'), 'font import');
check(str_contains($css, '--fg-radius: 14px'), 'radius');
check(! str_contains($css, '--fg-logo'), 'no logo when unset');
check(str_contains($css, '"Noto Sans Devanagari", "Noto Sans SC", system-ui, sans-serif'), 'font stack tail');
check(fg_css(null) === $css && fg_css('garbage') === $css && fg_css([]) === $css, 'garbage falls back');

$t = fg_defaults();
$t['light']['accent'] = '#fff;}body{display:none';
$t['dark']['canvas'] = 'red';
$t['font_body'] = 'Evil;}';
$t['radius'] = 99;
$t['logo_url'] = 'https://x.test/a).svg';
$t['logo_dark_url'] = '//evil.test/x.svg';
$bad = fg_css($t);
check(str_contains($bad, '--fg-accent: 154 122 63') && ! str_contains($bad, 'display:none'), 'bad accent');
check(str_contains($bad, '--fg-canvas: 10 15 28'), 'bad canvas');
check(! str_contains($bad, 'Evil') && str_contains($bad, '--fg-radius: 14px'), 'bad font/radius');
check(! str_contains($bad, '--fg-logo'), 'bad logo omitted');

$t = fg_defaults();
$t['logo_url'] = '/uploads/logo.png';
$ok = fg_css($t);
check(str_contains($ok, '--fg-logo: url(/uploads/logo.png)') && str_contains($ok, '--fg-logo-dark: url(/uploads/logo.png)'), 'logo fallback');
$t['logo_dark_url'] = 'https://x.test/d.svg';
check(str_contains(fg_css($t), '--fg-logo-dark: url(https://x.test/d.svg)'), 'dark logo');
$t['radius'] = 0;
check(str_contains(fg_css($t), '--fg-radius: 0px'), 'radius 0');

echo "ok\n";
