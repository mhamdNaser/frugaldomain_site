<?php

namespace App\Modules\Drawing\database\seeders\Library;

/**
 * Geometry and styling helpers for the seeded drawing templates.
 *
 * Everything here returns the editor's own element shape - `rect`, `circle`,
 * `ellipse`, `line`, `polygon`, `path` and text items - so a template arrives
 * on the canvas as ordinary editable geometry. Nothing is a flattened image
 * or a raw SVG blob: each element can be selected, resized, recoloured,
 * clipped, node-edited and run through every boolean operation, which is the
 * entire point of shipping templates rather than pictures.
 *
 * The palettes are deliberate. A decorative template is not a chart, so these
 * are chosen for how the colours sit together at poster scale rather than for
 * categorical separation - but each one still pairs a dark ink with a light
 * ground, so text placed on either stays readable.
 */
final class DrawKit
{
    /* ------------------------------------------------------------------ */
    /* Palettes                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Named palettes. Each is [ink, accent, second, light, ground].
     *
     * `ink` is the darkest, `ground` the lightest; `accent` carries the
     * design. Building every template from a named palette rather than from
     * loose hex values is what makes three hundred templates look like one
     * library.
     */
    public const PALETTES = [
        'midnight' => ['#0f172a', '#38bdf8', '#818cf8', '#bae6fd', '#f8fafc'],
        'ember' => ['#431407', '#f97316', '#dc2626', '#fed7aa', '#fff7ed'],
        'forest' => ['#052e16', '#16a34a', '#0d9488', '#bbf7d0', '#f0fdf4'],
        'plum' => ['#2e1065', '#8b5cf6', '#d946ef', '#e9d5ff', '#faf5ff'],
        'coral' => ['#4c0519', '#f43f5e', '#fb923c', '#fecdd3', '#fff1f2'],
        'ocean' => ['#083344', '#0891b2', '#2563eb', '#a5f3fc', '#ecfeff'],
        'gold' => ['#422006', '#eab308', '#f59e0b', '#fef08a', '#fefce8'],
        'slate' => ['#0f172a', '#475569', '#94a3b8', '#e2e8f0', '#f8fafc'],
        'mint' => ['#022c22', '#10b981', '#34d399', '#a7f3d0', '#ecfdf5'],
        'berry' => ['#500724', '#db2777', '#a21caf', '#fbcfe8', '#fdf2f8'],
        'sand' => ['#3f2d1b', '#c2703b', '#8c6239', '#e7d8c2', '#fdfaf5'],
        'indigo' => ['#1e1b4b', '#4f46e5', '#6366f1', '#c7d2fe', '#eef2ff'],
    ];

    /** @return array{0:string,1:string,2:string,3:string,4:string} */
    public static function palette(string $name): array
    {
        return self::PALETTES[$name] ?? self::PALETTES['midnight'];
    }

    /** Every palette name, for generators that walk the whole set. */
    public static function paletteNames(): array
    {
        return array_keys(self::PALETTES);
    }

    /* ------------------------------------------------------------------ */
    /* Style                                                               */
    /* ------------------------------------------------------------------ */

    /** The defaults every element in the editor carries. */
    public static function style(array $overrides = []): array
    {
        return array_merge([
            'fill' => 'none',
            'stroke' => '#111111',
            'strokeWidth' => 2,
            'opacity' => 1,
            'strokeDasharray' => '',
            'strokeLinecap' => 'round',
            'strokeLinejoin' => 'round',
        ], $overrides);
    }

    /** A solid fill with no outline. */
    public static function filled(string $colour, array $extra = []): array
    {
        return self::style(array_merge([
            'fill' => $colour,
            'stroke' => 'none',
            'strokeWidth' => 0,
        ], $extra));
    }

    /** An outline with no fill. */
    public static function outlined(string $colour, float $width = 3, array $extra = []): array
    {
        return self::style(array_merge([
            'fill' => 'none',
            'stroke' => $colour,
            'strokeWidth' => $width,
        ], $extra));
    }

    /** Filled and outlined at once. */
    public static function both(string $fill, string $stroke, float $width = 3, array $extra = []): array
    {
        return self::style(array_merge([
            'fill' => $fill,
            'stroke' => $stroke,
            'strokeWidth' => $width,
        ], $extra));
    }

    /**
     * A linear gradient, in the editor's own effect shape.
     *
     * Angles follow CSS intuition: 0 points right, 90 points down.
     */
    public static function gradient(string $from, string $to, int $angle = 135, float $toOpacity = 1): array
    {
        return [
            'gradient' => [
                'type' => 'linear',
                'angle' => $angle,
                'stops' => [
                    ['offset' => 0, 'color' => $from, 'opacity' => 1],
                    ['offset' => 1, 'color' => $to, 'opacity' => $toOpacity],
                ],
            ],
        ];
    }

    /** A radial gradient - use for glows and spotlight grounds. */
    public static function radial(string $from, string $to, float $toOpacity = 1): array
    {
        return [
            'gradient' => [
                'type' => 'radial',
                'angle' => 0,
                'stops' => [
                    ['offset' => 0, 'color' => $from, 'opacity' => 1],
                    ['offset' => 1, 'color' => $to, 'opacity' => $toOpacity],
                ],
            ],
        ];
    }

    /** A drop shadow. Keep these soft - a hard shadow reads as a mistake. */
    public static function shadow(float $dy = 8, float $blur = 12, string $colour = '#0f172a', float $opacity = 0.28): array
    {
        return ['shadow' => ['dx' => 0, 'dy' => $dy, 'blur' => $blur, 'color' => $colour, 'opacity' => $opacity]];
    }

    /* ------------------------------------------------------------------ */
    /* Elements                                                            */
    /* ------------------------------------------------------------------ */

    public static function rect(float $x, float $y, float $w, float $h, float $rx = 0, array $style = []): array
    {
        $element = ['type' => 'rect', 'x' => $x, 'y' => $y, 'width' => $w, 'height' => $h];
        if ($rx > 0) {
            $element['rx'] = $rx;
            $element['ry'] = $rx;
        }

        return array_merge($element, self::style([]), $style);
    }

    public static function circle(float $cx, float $cy, float $r, array $style = []): array
    {
        return array_merge(['type' => 'circle', 'cx' => $cx, 'cy' => $cy, 'r' => $r], self::style([]), $style);
    }

    public static function ellipse(float $cx, float $cy, float $rx, float $ry, array $style = []): array
    {
        return array_merge(['type' => 'ellipse', 'cx' => $cx, 'cy' => $cy, 'rx' => $rx, 'ry' => $ry], self::style([]), $style);
    }

    public static function line(float $x1, float $y1, float $x2, float $y2, array $style = []): array
    {
        return array_merge(['type' => 'line', 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2], self::style([]), $style);
    }

    public static function poly(string $points, array $style = []): array
    {
        return array_merge(['type' => 'polygon', 'points' => $points], self::style([]), $style);
    }

    public static function path(string $d, array $style = []): array
    {
        return array_merge(['type' => 'path', 'd' => $d], self::style([]), $style);
    }

    /** A text item, in the editor's own text shape. */
    public static function text(string $content, float $x, float $y, array $overrides = []): array
    {
        return array_merge([
            // Derived from the content and position rather than random, so
            // seeding twice produces an identical document and a re-run
            // changes nothing.
            'id' => 't' . substr(md5($content . '|' . $x . '|' . $y), 0, 16),
            'content' => $content,
            'x' => $x,
            'y' => $y,
            'fill' => '#111111',
            'fontSize' => 24,
            'fontFamily' => 'Arial',
            'fontWeight' => 'normal',
            'fontStyle' => 'normal',
            'alignment' => 'center',
            'opacity' => 1,
            'rotation' => 0,
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */
    /* Point and path generators                                           */
    /* ------------------------------------------------------------------ */

    /** A regular polygon, flat side down when it has an even count. */
    public static function polygonPoints(int $sides, float $cx, float $cy, float $r, float $rotation = -M_PI / 2): string
    {
        $points = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = (2 * M_PI * $i) / $sides + $rotation;
            $points[] = round($cx + $r * cos($angle), 2) . ',' . round($cy + $r * sin($angle), 2);
        }

        return implode(' ', $points);
    }

    /** A star. `innerRatio` near 0.38 gives the classic five-point shape. */
    public static function starPoints(int $spikes, float $cx, float $cy, float $outer, float $innerRatio = 0.4): string
    {
        $points = [];
        $inner = $outer * $innerRatio;
        for ($i = 0; $i < $spikes * 2; $i++) {
            $r = $i % 2 === 0 ? $outer : $inner;
            $angle = (M_PI * $i) / $spikes - M_PI / 2;
            $points[] = round($cx + $r * cos($angle), 2) . ',' . round($cy + $r * sin($angle), 2);
        }

        return implode(' ', $points);
    }

    /** An arc as path data, in degrees, clockwise from three o'clock. */
    public static function arc(float $cx, float $cy, float $r, float $from, float $to): string
    {
        $start = self::onCircle($cx, $cy, $r, $from);
        $end = self::onCircle($cx, $cy, $r, $to);
        $large = abs($to - $from) > 180 ? 1 : 0;

        return 'M' . $start[0] . ' ' . $start[1] . 'A' . $r . ' ' . $r . ' 0 ' . $large . ' 1 ' . $end[0] . ' ' . $end[1];
    }

    /** A point on a circle, in degrees. */
    public static function onCircle(float $cx, float $cy, float $r, float $degrees): array
    {
        $radians = deg2rad($degrees);

        return [round($cx + $r * cos($radians), 2), round($cy + $r * sin($radians), 2)];
    }

    /** A sine wave as a smooth path - dividers, ribbons, water. */
    public static function wave(float $x, float $y, float $width, float $amplitude, int $cycles = 3): string
    {
        $step = $width / ($cycles * 2);
        $d = 'M' . round($x, 2) . ' ' . round($y, 2);
        for ($i = 0; $i < $cycles * 2; $i++) {
            $up = $i % 2 === 0 ? -$amplitude : $amplitude;
            $cx = round($x + $step * ($i + 0.5), 2);
            $ex = round($x + $step * ($i + 1), 2);
            $d .= ' Q' . $cx . ' ' . round($y + $up, 2) . ' ' . $ex . ' ' . round($y, 2);
        }

        return $d;
    }

    /**
     * An organic blob - a closed curve with a wobbling radius.
     *
     * The wobble is derived from the seed rather than randomised, so seeding
     * the library twice produces identical geometry.
     */
    public static function blob(float $cx, float $cy, float $r, int $points = 7, float $wobble = 0.18, int $seed = 1): string
    {
        $coords = [];
        for ($i = 0; $i < $points; $i++) {
            $angle = (2 * M_PI * $i) / $points;
            $variance = 1 + $wobble * sin($seed * 1.7 + $i * 2.3);
            $coords[] = [
                $cx + $r * $variance * cos($angle),
                $cy + $r * $variance * sin($angle),
            ];
        }

        // Smoothed by starting at a midpoint and using each original point as
        // the control of a quadratic that ends at the next midpoint. Starting
        // on a point instead - the obvious version - leaves a corner there.
        $count = count($coords);
        $mid = function (array $a, array $b) {
            return [round(($a[0] + $b[0]) / 2, 2), round(($a[1] + $b[1]) / 2, 2)];
        };

        $start = $mid($coords[$count - 1], $coords[0]);
        $d = 'M' . $start[0] . ' ' . $start[1];

        for ($i = 0; $i < $count; $i++) {
            $control = $coords[$i];
            $end = $mid($coords[$i], $coords[($i + 1) % $count]);
            $d .= ' Q' . round($control[0], 2) . ' ' . round($control[1], 2) . ' ' . $end[0] . ' ' . $end[1];
        }

        return $d . ' Z';
    }

    /** A rounded-corner rectangle as path data, for node editing. */
    public static function roundedPath(float $x, float $y, float $w, float $h, float $r): string
    {
        return 'M' . ($x + $r) . ' ' . $y
            . 'H' . ($x + $w - $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $w) . ' ' . ($y + $r)
            . 'V' . ($y + $h - $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $w - $r) . ' ' . ($y + $h)
            . 'H' . ($x + $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $x . ' ' . ($y + $h - $r)
            . 'V' . ($y + $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $r) . ' ' . $y . 'Z';
    }

    /** A squircle - the shape app icons are actually cut to. */
    public static function squircle(float $cx, float $cy, float $r): string
    {
        $k = $r * 0.72;

        return 'M' . ($cx - $r) . ' ' . $cy
            . 'C' . ($cx - $r) . ' ' . ($cy - $k) . ' ' . ($cx - $k) . ' ' . ($cy - $r) . ' ' . $cx . ' ' . ($cy - $r)
            . 'C' . ($cx + $k) . ' ' . ($cy - $r) . ' ' . ($cx + $r) . ' ' . ($cy - $k) . ' ' . ($cx + $r) . ' ' . $cy
            . 'C' . ($cx + $r) . ' ' . ($cy + $k) . ' ' . ($cx + $k) . ' ' . ($cy + $r) . ' ' . $cx . ' ' . ($cy + $r)
            . 'C' . ($cx - $k) . ' ' . ($cy + $r) . ' ' . ($cx - $r) . ' ' . ($cy + $k) . ' ' . ($cx - $r) . ' ' . $cy . 'Z';
    }

    /** A shield outline. */
    public static function shield(float $cx, float $top, float $w, float $h): string
    {
        $half = $w / 2;

        return 'M' . $cx . ' ' . $top
            . 'L' . ($cx + $half) . ' ' . ($top + $h * 0.16)
            . 'V' . ($top + $h * 0.56)
            . 'C' . ($cx + $half) . ' ' . ($top + $h * 0.82) . ' ' . ($cx + $half * 0.52) . ' ' . ($top + $h * 0.95) . ' ' . $cx . ' ' . ($top + $h)
            . 'C' . ($cx - $half * 0.52) . ' ' . ($top + $h * 0.95) . ' ' . ($cx - $half) . ' ' . ($top + $h * 0.82) . ' ' . ($cx - $half) . ' ' . ($top + $h * 0.56)
            . 'V' . ($top + $h * 0.16) . 'Z';
    }

    /** A heart, drawn from two curves. */
    public static function heart(float $cx, float $cy, float $size): string
    {
        $s = $size / 2;

        return 'M' . $cx . ' ' . ($cy + $s * 0.85)
            . 'C' . ($cx - $s * 1.6) . ' ' . ($cy - $s * 0.2) . ' ' . ($cx - $s * 0.85) . ' ' . ($cy - $s * 1.35) . ' ' . $cx . ' ' . ($cy - $s * 0.55)
            . 'C' . ($cx + $s * 0.85) . ' ' . ($cy - $s * 1.35) . ' ' . ($cx + $s * 1.6) . ' ' . ($cy - $s * 0.2) . ' ' . $cx . ' ' . ($cy + $s * 0.85) . 'Z';
    }

    /** A speech bubble with a tail on the lower left. */
    public static function bubble(float $x, float $y, float $w, float $h, float $r = 22, float $tail = 34): string
    {
        return 'M' . ($x + $r) . ' ' . $y
            . 'H' . ($x + $w - $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $w) . ' ' . ($y + $r)
            . 'V' . ($y + $h - $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $w - $r) . ' ' . ($y + $h)
            . 'H' . ($x + $r * 2 + $tail) . 'L' . ($x + $r * 1.4) . ' ' . ($y + $h + $tail)
            . 'L' . ($x + $r * 1.7) . ' ' . ($y + $h)
            . 'H' . ($x + $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $x . ' ' . ($y + $h - $r)
            . 'V' . ($y + $r) . 'A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $r) . ' ' . $y . 'Z';
    }

    /** A solid arrow as polygon points, from one point to another. */
    public static function arrow(float $x1, float $y1, float $x2, float $y2, float $shaft = 8, float $head = 26): string
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $length = sqrt($dx * $dx + $dy * $dy) ?: 1;
        $ux = $dx / $length;
        $uy = $dy / $length;
        $px = -$uy;
        $py = $ux;
        $baseX = $x2 - $ux * $head;
        $baseY = $y2 - $uy * $head;

        $point = fn(float $bx, float $by, float $offset) => round($bx + $px * $offset, 2) . ',' . round($by + $py * $offset, 2);

        return implode(' ', [
            $point($x1, $y1, $shaft),
            $point($baseX, $baseY, $shaft),
            $point($baseX, $baseY, $head * 0.5),
            round($x2, 2) . ',' . round($y2, 2),
            $point($baseX, $baseY, -$head * 0.5),
            $point($baseX, $baseY, -$shaft),
            $point($x1, $y1, -$shaft),
        ]);
    }

    /** A tick mark, as an open path. */
    public static function tick(float $cx, float $cy, float $size): string
    {
        return 'M' . ($cx - $size * 0.5) . ' ' . $cy
            . 'L' . ($cx - $size * 0.1) . ' ' . ($cy + $size * 0.42)
            . 'L' . ($cx + $size * 0.55) . ' ' . ($cy - $size * 0.42);
    }

    /* ------------------------------------------------------------------ */
    /* Composite pieces                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * A ring of dots - useful as a decorative texture on badges and covers.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function dotRing(float $cx, float $cy, float $r, int $count, float $dot, string $colour, float $opacity = 1): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            [$x, $y] = self::onCircle($cx, $cy, $r, 360 * $i / $count);
            $out[] = self::circle($x, $y, $dot, self::filled($colour, ['opacity' => $opacity]));
        }

        return $out;
    }

    /**
     * A grid of dots covering a box.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function dotGrid(float $x, float $y, float $w, float $h, float $gap, float $dot, string $colour, float $opacity = 0.35): array
    {
        $out = [];
        for ($gx = $x; $gx <= $x + $w; $gx += $gap) {
            for ($gy = $y; $gy <= $y + $h; $gy += $gap) {
                $out[] = self::circle(round($gx, 2), round($gy, 2), $dot, self::filled($colour, ['opacity' => $opacity]));
            }
        }

        return $out;
    }

    /**
     * Evenly spaced parallel lines at 45 degrees - a hatch fill.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function hatch(float $x, float $y, float $w, float $h, float $gap, string $colour, float $width = 2, float $opacity = 0.3): array
    {
        $out = [];
        for ($offset = -$h; $offset < $w; $offset += $gap) {
            $out[] = self::line(
                round($x + $offset, 2),
                $y + $h,
                round($x + $offset + $h, 2),
                $y,
                self::outlined($colour, $width, ['opacity' => $opacity])
            );
        }

        return $out;
    }

    /** A rounded card with a soft shadow - the base of most layouts here. */
    public static function card(float $x, float $y, float $w, float $h, float $r, string $fill, array $extra = []): array
    {
        return self::rect($x, $y, $w, $h, $r, array_merge(self::filled($fill), self::shadow(10, 22, '#0f172a', 0.16), $extra));
    }

    /* ------------------------------------------------------------------ */
    /* Definition assembly                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * One template definition, in the shape the seeder consumes.
     *
     * @param array<int,array<string,mixed>> $paths
     * @param array<int,array<string,mixed>> $textItems
     */
    public static function make(
        string $title,
        string $description,
        int $width,
        int $height,
        array $paths,
        array $textItems = [],
        string $background = '#ffffff',
        array $tags = [],
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'width' => $width,
            'height' => $height,
            'background' => $background,
            'paths' => array_values(array_filter($paths)),
            'textItems' => array_values(array_filter($textItems)),
            'tags' => $tags,
        ];
    }

    /** Flattens nested element groups into one path list. */
    public static function flatten(array ...$groups): array
    {
        $out = [];
        foreach ($groups as $group) {
            foreach ($group as $item) {
                if (isset($item['type'])) {
                    $out[] = $item;
                    continue;
                }
                foreach ($item as $nested) {
                    $out[] = $nested;
                }
            }
        }

        return $out;
    }
}
