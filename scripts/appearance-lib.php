<?php

/** Pure helpers behind /appearance.css. Every value is re-validated: the api checks too, but this prints into CSS. */

const FG_FONTS = ['IBM Plex Sans Arabic', 'Readex Pro', 'Noto Kufi Arabic', 'Tajawal', 'Cairo', 'Noto Naskh Arabic'];
// Google Fonts css2 answers 400 for a weight a family lacks, which would kill the whole @import.
const FG_WEIGHTS = ['Tajawal' => '300;400;700', 'Noto Naskh Arabic' => '400;600;700'];
const FG_FONT_TAIL = '"Noto Sans Devanagari", "Noto Sans SC", system-ui, sans-serif';
const FG_COLOR_KEYS = ['canvas', 'surface', 'text', 'muted', 'accent', 'hairline'];
const FG_INK = [0 => 0, 50 => .04, 100 => .1, 300 => .35, 500 => .55, 700 => .75, 800 => .85, 900 => .93, 950 => 1];

function fg_defaults(): array
{
    return [
        'logo_url' => null, 'logo_dark_url' => null, 'favicon_url' => null,
        'font_body' => 'IBM Plex Sans Arabic', 'font_heading' => 'IBM Plex Sans Arabic',
        'dark' => ['canvas' => '#0a0f1c', 'surface' => '#111827', 'text' => '#e8e6e1', 'muted' => '#9aa3b5', 'accent' => '#c9a96e', 'hairline' => '#1c2438'],
        'light' => ['canvas' => '#f7f5f0', 'surface' => '#ffffff', 'text' => '#1a1a1a', 'muted' => '#6b6b6b', 'accent' => '#80632c', 'hairline' => '#e7e2d6'],
        'radius' => 14,
        'default_mode' => 'dark',
    ];
}

function fg_triplet(string $hex): string
{
    return implode(' ', array_map('hexdec', str_split(ltrim($hex, '#'), 2)));
}

function fg_mix(string $a, string $b, float $t): string
{
    $a = array_map('hexdec', str_split(ltrim($a, '#'), 2));
    $b = array_map('hexdec', str_split(ltrim($b, '#'), 2));
    $out = '#';
    foreach ($a as $i => $c) {
        $out .= sprintf('%02x', (int) round($c + ($b[$i] - $c) * $t));
    }

    return $out;
}

/** $mode is the colour map being printed; $light/$dark give the ink scale its ends. */
function fg_tokens(array $mode, array $light, array $dark): array
{
    $m = $mode;
    $tokens = [
        'canvas' => $m['canvas'], 'surface' => $m['surface'], 'text' => $m['text'],
        'text-muted' => $m['muted'], 'hairline' => $m['hairline'], 'accent' => $m['accent'],
        'surface-raised' => fg_mix($m['surface'], $m['text'], .04),
        'text-subtle' => fg_mix($m['muted'], $m['canvas'], .35),
        'accent-bright' => fg_mix($m['accent'], '#ffffff', .2),
        'accent-deep' => fg_mix($m['accent'], '#000000', .2),
    ];
    foreach (FG_INK as $n => $f) {
        $tokens["ink-$n"] = fg_mix($light['canvas'], $dark['canvas'], $f);
    }

    return array_map('fg_triplet', $tokens);
}

/** Merge a stored theme over the defaults, dropping anything that is not valid. */
function fg_sanitize($theme): array
{
    $d = fg_defaults();
    $theme = is_array($theme) ? $theme : [];
    $hex = fn ($v, $fallback) => is_string($v) && preg_match('/^#[0-9a-f]{6}$/iD', $v) ? strtolower($v) : $fallback;
    $url = fn ($v) => is_string($v) && preg_match('#^(https?://|/(?![/\x5c]))[^\x00-\x20\x7f"\'()\x5c]*$#D', $v) ? $v : null;

    foreach (['light', 'dark'] as $mode) {
        foreach (FG_COLOR_KEYS as $k) {
            $d[$mode][$k] = $hex(is_array($theme[$mode] ?? null) ? ($theme[$mode][$k] ?? null) : null, $d[$mode][$k]);
        }
    }
    foreach (['font_body', 'font_heading'] as $k) {
        if (in_array($theme[$k] ?? null, FG_FONTS, true)) {
            $d[$k] = $theme[$k];
        }
    }
    $r = $theme['radius'] ?? null;
    if (is_int($r) && $r >= 0 && $r <= 32) {
        $d['radius'] = $r;
    }
    $d['logo_url'] = $url($theme['logo_url'] ?? null);
    $d['logo_dark_url'] = $url($theme['logo_dark_url'] ?? null) ?? $d['logo_url'];

    return $d;
}

function fg_css($theme): string
{
    $t = fg_sanitize($theme);
    $block = fn (array $mode) => implode('', array_map(
        fn ($k, $v) => "--fg-$k: $v;",
        array_keys($tokens = fg_tokens($mode, $t['light'], $t['dark'])),
        $tokens
    ));

    $stack = fn (string $f) => "\"$f\", " . FG_FONT_TAIL;
    $families = implode('&', array_map(
        fn ($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@' . (FG_WEIGHTS[$f] ?? '300;400;600;700'),
        array_unique([$t['font_body'], $t['font_heading']])
    ));

    $root = $block($t['light'])
        . '--fg-font-body: ' . $stack($t['font_body']) . ';'
        . '--fg-font-heading: ' . $stack($t['font_heading']) . ';'
        . "--fg-radius: {$t['radius']}px;";
    if ($t['logo_url']) {
        $root .= "--fg-logo: url({$t['logo_url']});--fg-logo-dark: url({$t['logo_dark_url']});";
    }

    return "@import url(\"https://fonts.googleapis.com/css2?$families&display=swap\");\n"
        . ":root{{$root}}\n"
        . ':root[data-theme="dark"]{' . $block($t['dark']) . "}\n";
}

const FG_APPEARANCE_URL = 'https://api.frugaldomain.site/api/site/appearance';
const FG_APPEARANCE_TTL = 60;
const FG_APPEARANCE_FAILURE_TTL = 15;
const FG_APPEARANCE_TIMEOUT = 2.5;

/** The api's appearance payload (theme, home_sections), cached on disk; a failing api serves the stale copy, then []. */
function fg_fetch_appearance(): array
{
    $cacheFile = sys_get_temp_dir() . '/fruga-appearance.json';
    $raw = @file_get_contents($cacheFile);
    $cached = $raw === false ? null : json_decode($raw, true);
    $payload = is_array($cached['theme'] ?? null) ? ['theme' => $cached['theme'], 'home_sections' => is_array($cached['home_sections'] ?? null) ? $cached['home_sections'] : []] : [];
    $mtime = $raw === false ? 0 : (int) @filemtime($cacheFile);
    // A failure marker, a missing theme or a corrupt file is only trusted for the short window.
    $ttl = $payload && empty($cached['failed']) ? FG_APPEARANCE_TTL : FG_APPEARANCE_FAILURE_TTL;

    if (! $mtime || time() - $mtime >= $ttl) {
        $context = stream_context_create([
            'http' => ['timeout' => FG_APPEARANCE_TIMEOUT, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $body = @file_get_contents(FG_APPEARANCE_URL, false, $context);
        $data = $body ? json_decode($body, true) : null;
        if (is_array($data['theme'] ?? null)) {
            $payload = ['theme' => $data['theme'], 'home_sections' => is_array($data['home_sections'] ?? null) ? $data['home_sections'] : []];
            @file_put_contents($cacheFile, json_encode($payload));
        } else {
            // Keep the stale payload (or none) and do not refetch for a few seconds.
            @file_put_contents($cacheFile, json_encode($payload + ['theme' => null, 'failed' => true]));
        }
    }

    return $payload;
}

/** Visible sections as crawlable text, in $lang with English as the fallback; no English headline, no section. */
function fg_home_html(array $sections, string $lang): string
{
    $e = fn ($s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $pick = function (array $texts, array $keys) {
        foreach ($keys as $k) {
            if (isset($texts[$k]) && is_string($texts[$k]) && $texts[$k] !== '') {
                return $texts[$k];
            }
        }

        return null;
    };
    $out = '';
    foreach ($sections as $sec) {
        $i18n = is_array($sec['i18n'] ?? null) ? $sec['i18n'] : [];
        if (! is_array($sec) || empty($sec['visible']) || ! is_array($i18n['en'] ?? null)) {
            continue;
        }
        $texts = is_array($i18n[$lang] ?? null) ? $i18n[$lang] : $i18n['en'];
        $heading = $pick($texts, ['headline', 'heading', 'title']);
        if ($heading === null) {
            $texts = $i18n['en'];
            $heading = $pick($texts, ['headline', 'heading', 'title']);
        }
        if ($heading === null) {
            continue;
        }
        $para = $pick($texts, ['subtitle', 'text', 'description']);
        $out .= '<section><h2>' . $e($heading) . '</h2>' . ($para === null ? '' : '<p>' . $e($para) . '</p>') . '</section>';
    }

    return $out;
}

function fg_home_json(array $sections): string
{
    return (string) json_encode($sections, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
