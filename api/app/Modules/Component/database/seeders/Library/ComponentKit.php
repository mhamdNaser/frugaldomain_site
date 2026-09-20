<?php

namespace App\Modules\Component\database\seeders\Library;

/**
 * The design system every seeded component is built from.
 *
 * A component in this catalogue is a single self-contained file: the preview
 * frame loads it straight from public/components/<slug>/template.html and the
 * "view code" tab hands the same text over. That rules out a shared
 * stylesheet, a framework or a CDN font, so the look has to travel inside each
 * file - which is what this class provides.
 *
 * Everything is tokenised: one accent per component, one set of neutrals, one
 * radius scale, one shadow. Two hundred files built from the same tokens read
 * as one library rather than two hundred unrelated snippets, and anyone can
 * retheme a file by editing the variables at the top of it.
 *
 * No external asset is ever referenced - no image host, no icon CDN, no
 * webfont - because a preview that needs the network is a preview that breaks
 * offline and behind a strict CSP. Pictures are inline SVG or CSS gradients.
 */
final class ComponentKit
{
    /**
     * Matches the dashboard's own stack, so previews look native on any OS.
     *
     * The Arabic faces are listed after the Latin ones: a browser falls
     * through the stack per character, so Arabic text picks up a face that
     * actually has the glyphs instead of rendering in a fallback that breaks
     * the joining.
     */
    public const FONT = "system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,"
        . "'Noto Sans','Noto Sans Arabic','Segoe UI Historic','Geeza Pro','Dubai',Tahoma,sans-serif";

    /** Avatar and accent colours, picked to stay legible on both themes. */
    public const PALETTE = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#db2777', '#0891b2', '#dc2626', '#4f46e5'];

    /**
     * Categorical series colours, in a fixed order that must not be shuffled.
     *
     * A chart with three series always uses slots 1-3, so adding a fourth never
     * repaints the first three. The order is chosen so that every *adjacent*
     * pair stays apart under deuteranopia and protanopia as well as under
     * normal vision - blue next to violet, the obvious-looking choice, is
     * exactly the pair that collapses for a red-green colourblind reader.
     *
     * Dark is the same eight hues re-stepped for a dark surface rather than a
     * different palette, so a series keeps its identity across themes.
     */
    public const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    public const SERIES_DARK = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'];

    /**
     * One-hue ramp for magnitude - heatmap cells, choropleths, intensity.
     *
     * Sequential encoding is one hue going light to dark. A rainbow ramp
     * invents category boundaries where the data has none.
     */
    public const RAMP = ['#cde2fb', '#b7d3f6', '#9ec5f4', '#86b6ef', '#6da7ec', '#5598e7', '#3987e5', '#2a78d6', '#256abf', '#1c5cab', '#184f95', '#104281', '#0d366b'];

    /** The nth series colour, for the given theme. */
    public static function series(int $index, bool $dark = false): string
    {
        $slots = $dark ? self::SERIES_DARK : self::SERIES;

        return $slots[$index % count($slots)];
    }

    /** A ramp step for a 0-1 magnitude. */
    public static function ramp(float $magnitude): string
    {
        $steps = self::RAMP;
        $i = (int) round(max(0, min(1, $magnitude)) * (count($steps) - 1));

        return $steps[$i];
    }

    /* ------------------------------------------------------------------ */
    /* Document                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Wraps a body in a complete document.
     *
     * @param array{name:string,accent?:string,theme?:string,max?:int,css?:string,js?:string,body:string} $spec
     */
    public static function page(array $spec): string
    {
        $title = self::esc($spec['name']);
        $accent = $spec['accent'] ?? '#2563eb';
        $theme = $spec['theme'] ?? 'light';
        $max = (int) ($spec['max'] ?? 960);

        $base = self::logical(self::base($accent, $theme, $max));

        // Per-component CSS goes through the same logical-property rewrite as
        // the base sheet, so a template written with `padding-left` still
        // mirrors correctly when the document direction is right-to-left.
        $extra = self::logical(trim($spec['css'] ?? ''));
        $extra = $extra === '' ? '' : "\n" . $extra;

        $body = trim($spec['body']);
        $js = trim($spec['js'] ?? '');
        $script = $js === '' ? '' : "\n<script>\n" . $js . "\n</script>";

        $bootstrap = self::bootstrap();

        return <<<HTML
<!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<style>
{$base}{$extra}
</style>
</head>
<body>
{$body}
<script>
{$bootstrap}
</script>{$script}
</body>
</html>

HTML;
    }

    /**
     * Reads `?dir=rtl` and `?theme=dark` off the URL.
     *
     * Every template is a static file, so there is no server to negotiate
     * with: this is what lets the gallery show the same component in Arabic
     * and in English, and in either theme, without generating four copies of
     * each file. Delete it if the template is being used in one direction.
     */
    private static function bootstrap(): string
    {
        return "(function(){\n"
            . "  var q=new URLSearchParams(location.search);\n"
            . "  var dir=q.get('dir');\n"
            . "  if(dir==='rtl'||dir==='ltr')document.documentElement.dir=dir;\n"
            . "  if(dir==='rtl')document.documentElement.lang='ar';\n"
            . "  var theme=q.get('theme');\n"
            . "  if(theme==='dark'||theme==='light')document.documentElement.dataset.theme=theme;\n"
            . '})();';
    }

    /**
     * Rewrites direction-bound CSS into its logical equivalent.
     *
     * Mechanical, and deliberately narrow. `padding-left` always means the
     * same thing as `padding-inline-start` in a left-to-right document, so
     * the rewrite is lossless there and correct in the other direction.
     *
     * Two cases need care rather than a blind substitution:
     *
     *  - `translateX` in pixels is almost always a slide - a switch knob, a
     *    drawer - and has to flip. `translateX(-50%)` is almost always
     *    centring and must not. So only pixel values are multiplied by the
     *    direction sign.
     *  - `border-radius` with four values names physical corners, which swap
     *    in the mirror. Expanding it to the four logical corners keeps a
     *    speech bubble's tail on the correct side.
     */
    private static function logical(string $css): string
    {
        if ($css === '') {
            return '';
        }

        $map = [
            '/\btext-align\s*:\s*left\b/i' => 'text-align:start',
            '/\btext-align\s*:\s*right\b/i' => 'text-align:end',
            '/\bpadding-left\s*:/i' => 'padding-inline-start:',
            '/\bpadding-right\s*:/i' => 'padding-inline-end:',
            '/\bmargin-left\s*:/i' => 'margin-inline-start:',
            '/\bmargin-right\s*:/i' => 'margin-inline-end:',
            '/\bborder-left\s*:/i' => 'border-inline-start:',
            '/\bborder-right\s*:/i' => 'border-inline-end:',
            '/\bborder-left-(color|width|style)\s*:/i' => 'border-inline-start-$1:',
            '/\bborder-right-(color|width|style)\s*:/i' => 'border-inline-end-$1:',
        ];

        foreach ($map as $pattern => $replacement) {
            $css = preg_replace($pattern, $replacement, $css);
        }

        // `left:`/`right:` only where they begin a declaration, so a value
        // that merely contains the word is left alone.
        $css = preg_replace('/(^|[{;\s])left\s*:/i', '$1inset-inline-start:', $css);
        $css = preg_replace('/(^|[{;\s])right\s*:/i', '$1inset-inline-end:', $css);

        // A pixel slide flips with the direction; a percentage one is centring.
        $css = preg_replace_callback(
            '/translateX\(\s*(-?[\d.]+)px\s*\)/i',
            fn($m) => 'translateX(calc(' . $m[1] . 'px * var(--dir)))',
            $css
        );

        // border-radius: TL TR BR BL  ->  the four logical corners.
        $css = preg_replace_callback(
            '/\bborder-radius\s*:\s*([\d.]+(?:px|%|em|rem))\s+([\d.]+(?:px|%|em|rem))\s+([\d.]+(?:px|%|em|rem))\s+([\d.]+(?:px|%|em|rem))/i',
            fn($m) => 'border-start-start-radius:' . $m[1]
                . ';border-start-end-radius:' . $m[2]
                . ';border-end-end-radius:' . $m[3]
                . ';border-end-start-radius:' . $m[4],
            $css
        );

        return $css;
    }

    /* ------------------------------------------------------------------ */
    /* Tokens and the shared stylesheet                                    */
    /* ------------------------------------------------------------------ */

    /**
     * The token block plus the utilities shared by every component.
     *
     * Kept deliberately small: a component that needs more brings its own
     * rules rather than growing this, so no file carries CSS it never uses.
     */
    public static function base(string $accent, string $theme, int $max): string
    {
        $light = self::tokens($accent, false);
        $dark = self::tokens($accent, true);

        // `auto` follows the visitor's system setting; the other two pin the
        // preview, for a component that only reads on one background.
        $scheme = match ($theme) {
            'dark' => ":root{\n{$dark}\n}",
            'light' => ":root{\n{$light}\n}",
            default => ":root{\n{$light}\n}\n@media (prefers-color-scheme:dark){:root{\n{$dark}\n}}",
        };

        $font = self::FONT;

        // Everything below is written with logical properties, and `--dir`
        // carries the direction sign for the few places that need it (a
        // switch knob sliding, an arrow pointing). One file, both directions:
        // add dir="rtl" to the html element, or open it with ?dir=rtl.
        return <<<CSS
{$scheme}
:root{--dir:1}
[dir="rtl"]{--dir:-1}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;padding:26px 18px;background:var(--bg);color:var(--ink);font:14px/1.55 {$font};display:flex;justify-content:center;align-items:flex-start;-webkit-font-smoothing:antialiased}
.wrap{width:100%;max-width:{$max}px}
.card{background:var(--card);border:1px solid var(--bd);border-radius:var(--r);box-shadow:var(--sh)}
.pad{padding:18px 20px}
.hd{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 20px;border-bottom:1px solid var(--bd);flex-wrap:wrap}
.ft{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 20px;border-top:1px solid var(--bd);font-size:12.5px;color:var(--mut);flex-wrap:wrap}
h1,h2,h3,h4{margin:0;line-height:1.25;letter-spacing:-.011em}
h1{font-size:20px;font-weight:680}
h2{font-size:16px;font-weight:660}
h3{font-size:14px;font-weight:650}
h4{font-size:12.5px;font-weight:650}
p{margin:0}
.sub{font-size:12.5px;color:var(--mut);margin-top:3px}
.mut{color:var(--mut)}
.sm{font-size:12.5px}
.xs{font-size:11.5px}
.bold{font-weight:650}
.num{font-variant-numeric:tabular-nums}
.row{display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border:1px solid var(--bd);border-radius:10px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:background .16s,border-color .16s,color .16s,transform .16s}
.btn:hover{border-color:var(--acc);color:var(--acc)}
.btn:active{transform:translateY(1px)}
.btn:focus-visible{outline:2px solid var(--acc);outline-offset:2px}
.btn.pri{background:var(--acc);border-color:var(--acc);color:#fff}
.btn.pri:hover{background:var(--acc-dk);border-color:var(--acc-dk);color:#fff}
.btn.gh{background:transparent;border-color:transparent;color:var(--mut)}
.btn.gh:hover{background:var(--soft);color:var(--ink)}
.btn.tiny{padding:6px 11px;font-size:12px;border-radius:9px}
.btn[disabled]{opacity:.45;cursor:not-allowed}
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:650;line-height:1.6;white-space:nowrap}
.pill.ok{background:var(--ok-bg);color:var(--ok)}
.pill.warn{background:var(--warn-bg);color:var(--warn)}
.pill.bad{background:var(--bad-bg);color:var(--bad)}
.pill.info{background:var(--acc-soft);color:var(--acc)}
.pill.neutral{background:var(--soft);color:var(--mut)}
.in{width:100%;padding:10px 12px;border:1px solid var(--bd);border-radius:10px;background:var(--card);color:var(--ink);font:inherit;font-size:13.5px;transition:border-color .16s,box-shadow .16s}
.in::placeholder{color:var(--faint)}
.in:focus{outline:none;border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}
textarea.in{resize:vertical;min-height:88px}
select.in{appearance:none;background-image:linear-gradient(45deg,transparent 50%,var(--mut) 50%),linear-gradient(135deg,var(--mut) 50%,transparent 50%);background-position:calc(100% - 17px) 50%,calc(100% - 12px) 50%;background-size:5px 5px;background-repeat:no-repeat;padding-inline-end:34px}
[dir="rtl"] select.in{background-position:17px 50%,12px 50%}
.lb{display:block;font-size:12.5px;font-weight:600;margin-bottom:6px}
.hint{font-size:11.5px;color:var(--mut);margin-top:5px}
.err{font-size:11.5px;color:var(--bad);margin-top:5px}
table{width:100%;border-collapse:collapse}
th{padding:10px 16px;text-align:left;font-size:11px;font-weight:650;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);background:var(--soft);border-bottom:1px solid var(--bd);white-space:nowrap}
td{padding:12px 16px;border-bottom:1px solid var(--bd);font-size:13.5px;vertical-align:middle}
tbody tr:last-child td{border-bottom:0}
tbody tr{transition:background .14s}
.right{text-align:right}
a{color:var(--acc);text-decoration:none}
a:hover{text-decoration:underline}
CSS;
    }

    /** One theme's variables. */
    private static function tokens(string $accent, bool $dark): string
    {
        $soft = self::fade($accent, $dark ? 0.22 : 0.1);
        $deep = self::shade($accent, $dark ? 0.12 : -0.12);

        if ($dark) {
            return implode("\n", [
                "  --acc:{$accent};--acc-dk:{$deep};--acc-soft:{$soft};",
                '  --bg:#0b1120;--card:#121a2b;--soft:#182235;--bd:#243049;',
                '  --ink:#e8edf7;--mut:#94a3b8;--faint:#64748b;',
                '  --ok:#34d399;--ok-bg:rgba(52,211,153,.14);',
                '  --warn:#fbbf24;--warn-bg:rgba(251,191,36,.14);',
                '  --bad:#f87171;--bad-bg:rgba(248,113,113,.14);',
                '  --r:14px;--sh:0 1px 2px rgba(0,0,0,.4),0 18px 40px -24px rgba(0,0,0,.8);',
            ]);
        }

        return implode("\n", [
            "  --acc:{$accent};--acc-dk:{$deep};--acc-soft:{$soft};",
            '  --bg:#f5f7fa;--card:#ffffff;--soft:#f8fafc;--bd:#e5e8ef;',
            '  --ink:#101828;--mut:#667085;--faint:#98a2b3;',
            '  --ok:#047857;--ok-bg:#ecfdf5;',
            '  --warn:#b45309;--warn-bg:#fffbeb;',
            '  --bad:#be123c;--bad-bg:#fff1f2;',
            '  --r:14px;--sh:0 1px 2px rgba(16,24,40,.05),0 12px 32px -16px rgba(16,24,40,.22);',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Pieces                                                              */
    /* ------------------------------------------------------------------ */

    /** A status chip. Tone is one of ok / warn / bad / info / neutral. */
    public static function pill(string $label, string $tone = 'neutral', bool $dot = false): string
    {
        $mark = $dot ? '<span style="width:6px;height:6px;border-radius:50%;background:currentColor"></span>' : '';

        return '<span class="pill ' . $tone . '">' . $mark . self::esc($label) . '</span>';
    }

    /**
     * An avatar, drawn rather than fetched.
     *
     * Initials on a tinted disc: no request, no broken image, and it stays
     * sharp at any size - which a 32px JPEG would not.
     */
    public static function avatar(string $name, int $size = 32, ?string $colour = null): string
    {
        $colour ??= self::PALETTE[abs(crc32($name)) % count(self::PALETTE)];
        $initials = self::esc(self::initials($name));
        $font = round($size * 0.4, 1);
        $tint = self::fade($colour, 0.16);

        return '<span aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;flex:none;'
            . "width:{$size}px;height:{$size}px;border-radius:50%;background:{$tint};color:{$colour};"
            . "font-size:{$font}px;font-weight:700;letter-spacing:.02em\">{$initials}</span>";
    }

    /** Overlapping avatars - a team, a set of assignees. */
    public static function avatarStack(array $names, int $size = 28): string
    {
        $out = '<span style="display:inline-flex;align-items:center">';
        foreach (array_values($names) as $i => $name) {
            $shift = $i === 0 ? '0' : '-8px';
            $out .= '<span style="margin-left:' . $shift . ';border-radius:50%;box-shadow:0 0 0 2px var(--card)">'
                . self::avatar($name, $size) . '</span>';
        }

        return $out . '</span>';
    }

    /** A rounded square holding an inline icon - the standard tile mark. */
    public static function iconTile(string $icon, string $colour, int $size = 38): string
    {
        $tint = self::fade($colour, 0.13);
        $radius = round($size * 0.31);

        return '<span aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;flex:none;'
            . "width:{$size}px;height:{$size}px;border-radius:{$radius}px;background:{$tint};color:{$colour}\">"
            . self::icon($icon, (int) round($size * 0.5)) . '</span>';
    }

    /**
     * A line chart.
     *
     * Drawn as a path with a soft area beneath it. The gradient id is derived
     * from the data so two sparklines in one document cannot collide.
     */
    public static function spark(array $values, string $colour, int $w = 140, int $h = 44, bool $area = true): string
    {
        $points = self::points($values, $w, $h, 4);
        $line = 'M' . implode(' L', array_map(fn($p) => $p[0] . ',' . $p[1], $points));
        $id = 'sp' . substr(md5($line . $colour), 0, 6);

        $fill = '';
        if ($area) {
            $last = end($points);
            $first = $points[0];
            $fill = '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1">'
                . '<stop offset="0" stop-color="' . $colour . '" stop-opacity=".28"/>'
                . '<stop offset="1" stop-color="' . $colour . '" stop-opacity="0"/></linearGradient></defs>'
                . '<path d="' . $line . " L{$last[0]},{$h} L{$first[0]},{$h} Z\" fill=\"url(#{$id})\"/>";
        }

        return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" fill="none" aria-hidden="true" style="display:block">'
            . $fill
            . '<path d="' . $line . '" stroke="' . $colour . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }

    /** A bar chart. `$highlight` dims every bar but one, for a "this month" read. */
    public static function bars(array $values, string $colour, int $w = 160, int $h = 48, ?int $highlight = null): string
    {
        $max = max($values) ?: 1;
        $count = max(1, count($values));
        $gap = 3;
        $bar = max(2, ($w - $gap * ($count - 1)) / $count);
        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" aria-hidden="true" style="display:block">';

        foreach (array_values($values) as $i => $value) {
            $height = max(2, round($value / $max * ($h - 2), 1));
            $x = round($i * ($bar + $gap), 2);
            $y = round($h - $height, 2);
            $opacity = $highlight === null || $highlight === $i ? '1' : '.28';
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . round($bar, 2) . '" height="' . $height
                . '" rx="' . min(3, round($bar / 2, 1)) . '" fill="' . $colour . '" opacity="' . $opacity . '"/>';
        }

        return $svg . '</svg>';
    }

    /**
     * A donut, as stroked arcs on one circle.
     *
     * @param array<int,array{0:float,1:string}> $slices value and colour pairs
     */
    public static function donut(array $slices, int $size = 132, string $centre = '', string $caption = ''): string
    {
        $stroke = round($size * 0.13);
        $radius = ($size - $stroke) / 2;
        $circumference = 2 * M_PI * $radius;
        $total = array_sum(array_column($slices, 0)) ?: 1;
        $offset = 0;
        $mid = $size / 2;

        $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '" aria-hidden="true" style="display:block">'
            . '<circle cx="' . $mid . '" cy="' . $mid . '" r="' . $radius . '" fill="none" stroke="var(--soft)" stroke-width="' . $stroke . '"/>';

        foreach ($slices as [$value, $colour]) {
            $length = $value / $total * $circumference;
            $svg .= '<circle cx="' . $mid . '" cy="' . $mid . '" r="' . $radius . '" fill="none"'
                . ' stroke="' . $colour . '" stroke-width="' . $stroke . '" stroke-linecap="round"'
                . ' stroke-dasharray="' . round(max(0, $length - 2), 2) . ' ' . round($circumference - $length + 2, 2) . '"'
                . ' stroke-dashoffset="' . round(-$offset, 2) . '"'
                . ' transform="rotate(-90 ' . $mid . ' ' . $mid . ')"/>';
            $offset += $length;
        }

        if ($centre !== '') {
            $svg .= '<text x="50%" y="' . ($caption === '' ? '54%' : '48%') . '" text-anchor="middle" fill="var(--ink)"'
                . ' font-size="' . round($size * 0.18) . '" font-weight="700" font-family="' . self::FONT . '">' . self::esc($centre) . '</text>';
        }
        if ($caption !== '') {
            $svg .= '<text x="50%" y="64%" text-anchor="middle" fill="var(--mut)" font-size="' . round($size * 0.095)
                . '" font-family="' . self::FONT . '">' . self::esc($caption) . '</text>';
        }

        return $svg . '</svg>';
    }

    /** A progress ring - one value against a track. */
    public static function ring(float $percent, string $colour, int $size = 86, string $label = ''): string
    {
        $stroke = round($size * 0.11);
        $radius = ($size - $stroke) / 2;
        $circumference = 2 * M_PI * $radius;
        $done = round($circumference * min(100, max(0, $percent)) / 100, 2);
        $mid = $size / 2;

        return '<svg viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '" aria-hidden="true" style="display:block">'
            . '<circle cx="' . $mid . '" cy="' . $mid . '" r="' . $radius . '" fill="none" stroke="var(--soft)" stroke-width="' . $stroke . '"/>'
            . '<circle cx="' . $mid . '" cy="' . $mid . '" r="' . $radius . '" fill="none" stroke="' . $colour . '"'
            . ' stroke-width="' . $stroke . '" stroke-linecap="round" stroke-dasharray="' . $done . ' ' . round($circumference - $done, 2) . '"'
            . ' transform="rotate(-90 ' . $mid . ' ' . $mid . ')"/>'
            . '<text x="50%" y="55%" text-anchor="middle" fill="var(--ink)" font-size="' . round($size * 0.24)
            . '" font-weight="700" font-family="' . self::FONT . '">' . self::esc($label !== '' ? $label : round($percent) . '%') . '</text>'
            . '</svg>';
    }

    /** A horizontal meter. */
    public static function meter(float $percent, string $colour, int $height = 7): string
    {
        $width = round(min(100, max(0, $percent)), 1);

        return '<span style="display:block;height:' . $height . 'px;border-radius:999px;background:var(--soft);overflow:hidden">'
            . '<span style="display:block;height:100%;width:' . $width . '%;border-radius:999px;background:' . $colour . '"></span></span>';
    }

    /** A star rating, filled to the nearest half. */
    public static function stars(float $score, int $size = 14, string $colour = '#f59e0b'): string
    {
        $out = '<span class="row" style="gap:2px;color:' . $colour . '">';
        for ($i = 1; $i <= 5; $i++) {
            $filled = $score >= $i - 0.25;
            $out .= '<span style="' . ($filled ? '' : 'opacity:.28') . '">'
                . '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="currentColor" aria-hidden="true" style="display:block">'
                . '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/></svg></span>';
        }

        return $out . '</span>';
    }

    /**
     * A stroked icon from a small, hand-picked set.
     *
     * Inline rather than a font or a sprite: one element, no extra request,
     * and it inherits `currentColor` so it always matches its text.
     */
    public static function icon(string $name, int $size = 18, float $weight = 1.8): string
    {
        $paths = self::iconPaths();
        $d = $paths[$name] ?? $paths['circle'];

        return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor"'
            . ' stroke-width="' . $weight . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"'
            . ' style="display:block;flex:none">' . $d . '</svg>';
    }

    /** @return array<string,string> */
    private static function iconPaths(): array
    {
        return [
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'minus' => '<path d="M5 12h14"/>',
            'check' => '<path d="m20 6-11 11-5-5"/>',
            'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'chevron-up' => '<path d="m6 15 6-6 6 6"/>',
            'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
            'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
            'arrow-up' => '<path d="M12 19V5M5 12l7-7 7 7"/>',
            'arrow-down' => '<path d="M12 5v14M19 12l-7 7-7-7"/>',
            'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'trend-up' => '<path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/>',
            'trend-down' => '<path d="M22 17 13.5 8.5 8.5 13.5 2 7"/><path d="M16 17h6v-6"/>',
            'filter' => '<path d="M3 5h18l-7 8v6l-4 2v-8Z"/>',
            'download' => '<path d="M12 3v12M7 11l5 5 5-5M4 20h16"/>',
            'upload' => '<path d="M12 21V9M7 13l5-5 5 5M4 4h16"/>',
            'trash' => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
            'edit' => '<path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16Z"/>',
            'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'bell' => '<path d="M18 16V11a6 6 0 1 0-12 0v5l-2 3h16Z"/><path d="M10 22h4"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><path d="M17 5.2a3.5 3.5 0 0 1 0 6.6M18 20h4a5.4 5.4 0 0 0-3-4.9"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
            'mail' => '<rect x="2" y="5" width="20" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
            'lock' => '<rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>',
            'heart' => '<path d="M12 20s-8-4.7-8-10a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.3-8 10-8 10Z"/>',
            'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
            'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'pie' => '<path d="M12 3a9 9 0 1 0 9 9h-9Z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15Z"/>',
            'cart' => '<circle cx="9.5" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 11.3a2 2 0 0 0 2 1.7h7.9a2 2 0 0 0 2-1.6L21 7H6"/>',
            'card' => '<rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20"/>',
            'box' => '<path d="m12 3 8 4.2v9.6L12 21l-8-4.2V7.2Z"/><path d="M4 7.2 12 11l8-3.8M12 11v10"/>',
            'tag' => '<path d="M3 12V4h8l10 10-8 8Z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
            'flag' => '<path d="M5 21V4h14l-3 4.5L19 13H5"/>',
            'file' => '<path d="M6 2h8l4 4v16H6Z"/><path d="M14 2v5h4"/>',
            'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
            'image' => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m4 18 5-5 4 4 3-2 4 3"/>',
            'play' => '<path d="M8 5.5v13l11-6.5Z"/>',
            'pause' => '<path d="M9 5v14M15 5v14"/>',
            'more' => '<circle cx="5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="19" cy="12" r="1.4"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'grid' => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/>',
            'list' => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'alert' => '<path d="M12 4 2.5 20h19Z"/><path d="M12 10v4M12 17h.01"/>',
            'shield' => '<path d="m12 3 8 3v6c0 5-3.4 8-8 9-4.6-1-8-4-8-9V6Z"/><path d="m9 12 2 2 4-4"/>',
            'zap' => '<path d="M13 2 4 14h7l-1 8 9-12h-7Z"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18"/>',
            'send' => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4Z"/>',
            'link' => '<path d="M10 13a4 4 0 0 0 5.7 0l3-3A4 4 0 0 0 13 4.3l-1.7 1.7"/><path d="M14 11a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 19.7l1.7-1.7"/>',
            'refresh' => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/>',
            'phone' => '<path d="M5 3h4l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 5.2 2 2 0 0 1 5 3Z"/>',
            'map-pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
            'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>',
            'circle' => '<circle cx="12" cy="12" r="9"/>',
            'sliders' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
            'copy' => '<rect x="9" y="9" width="12" height="12" rx="2.5"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
            'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
            'truck' => '<path d="M3 7h11v9H3Z"/><path d="M14 10h4l3 3v3h-7Z"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17.5" cy="18.5" r="1.6"/>',
            'server' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5h.01M7 16.5h.01"/>',
            'database' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
            'code' => '<path d="m9 8-5 4 5 4M15 8l5 4-5 4"/>',
            'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h12v4"/><rect x="3" y="7" width="18" height="13" rx="3"/><circle cx="16.5" cy="13.5" r="1.4"/>',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Small helpers                                                       */
    /* ------------------------------------------------------------------ */

    /** A delta caption: green when up, red when down, arrow to match. */
    public static function delta(string $value, bool $up, ?string $note = null): string
    {
        $tone = $up ? 'ok' : 'bad';
        $icon = self::icon($up ? 'trend-up' : 'trend-down', 13, 2.2);
        $tail = $note ? '<span class="xs mut">' . self::esc($note) . '</span>' : '';

        return '<span class="row" style="gap:6px"><span class="pill ' . $tone . '">' . $icon . self::esc($value) . '</span>' . $tail . '</span>';
    }

    /** Evenly spaced points for a sparkline, normalised to the box. */
    private static function points(array $values, int $w, int $h, int $pad): array
    {
        $min = min($values);
        $max = max($values);
        $span = ($max - $min) ?: 1;
        $count = max(1, count($values) - 1);
        $points = [];

        foreach (array_values($values) as $i => $value) {
            $points[] = [
                round($i / $count * $w, 2),
                round($h - $pad - ($value - $min) / $span * ($h - $pad * 2), 2),
            ];
        }

        return $points;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $second = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

        return mb_strtoupper($first . $second);
    }

    /** #rrggbb to rgba(), so tints always follow the accent. */
    public static function fade(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::rgb($hex);

        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . round($alpha, 3) . ')';
    }

    /** Lighten (positive amount) or darken (negative) a hex colour. */
    public static function shade(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $mix = fn(int $c) => (int) round($amount >= 0 ? $c + (255 - $c) * $amount : $c * (1 + $amount));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
