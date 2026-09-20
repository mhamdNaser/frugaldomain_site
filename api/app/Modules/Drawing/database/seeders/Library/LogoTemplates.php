<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Logos category: marks, monograms, emblems and app icons.
 *
 * Each family below is a builder plus a table of variants, which is how fifty
 * templates fit in a readable file - and, more usefully, how a designer adds a
 * fifty-first: add a row, not a new block of geometry.
 *
 * Every mark is built from real shapes rather than from one traced path, so
 * the ring can be recoloured without touching the letter, and the letter can
 * be replaced without rebuilding the mark.
 */
final class LogoTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['A', 'midnight', 'Circle monogram - midnight'],
            ['B', 'ember', 'Circle monogram - ember'],
            ['M', 'forest', 'Circle monogram - forest'],
            ['S', 'plum', 'Circle monogram - plum'],
            ['R', 'ocean', 'Circle monogram - ocean'],
            ['K', 'berry', 'Circle monogram - berry'],
        ] as [$letter, $palette, $title]) {
            $out[] = self::monogramCircle($title, $letter, $palette);
        }

        foreach ([
            ['LOGO', 'midnight', 'Hexagon badge - midnight'],
            ['NORTH', 'forest', 'Hexagon badge - forest'],
            ['APEX', 'ember', 'Hexagon badge - ember'],
            ['HIVE', 'gold', 'Hexagon badge - gold'],
        ] as [$word, $palette, $title]) {
            $out[] = self::hexBadge($title, $word, $palette);
        }

        foreach ([
            ['tick', 'forest', 'Shield mark - secure'],
            ['star', 'indigo', 'Shield mark - trusted'],
            ['bolt', 'gold', 'Shield mark - power'],
            ['cross', 'coral', 'Shield mark - care'],
        ] as [$glyph, $palette, $title]) {
            $out[] = self::shieldMark($title, $glyph, $palette);
        }

        foreach ([
            ['spark', 'indigo', 'App icon - spark'],
            ['layers', 'ocean', 'App icon - layers'],
            ['leaf', 'mint', 'App icon - leaf'],
            ['chat', 'berry', 'App icon - chat'],
            ['play', 'coral', 'App icon - play'],
            ['pen', 'slate', 'App icon - pen'],
        ] as [$glyph, $palette, $title]) {
            $out[] = self::appIcon($title, $glyph, $palette);
        }

        foreach ([
            ['ocean', 'Overlapping circles - ocean'],
            ['coral', 'Overlapping circles - coral'],
            ['mint', 'Overlapping circles - mint'],
            ['plum', 'Overlapping circles - plum'],
        ] as [$palette, $title]) {
            $out[] = self::overlapCircles($title, $palette);
        }

        foreach ([
            ['midnight', 'Triangle stack - midnight'],
            ['ember', 'Triangle stack - ember'],
            ['forest', 'Triangle stack - forest'],
        ] as [$palette, $title]) {
            $out[] = self::triangleStack($title, $palette);
        }

        foreach ([
            ['ocean', 'Wave mark - ocean'],
            ['mint', 'Wave mark - mint'],
            ['indigo', 'Wave mark - indigo'],
            ['coral', 'Wave mark - sunset'],
        ] as [$palette, $title]) {
            $out[] = self::waveMark($title, $palette);
        }

        foreach ([
            ['forest', 'Leaf mark - forest'],
            ['mint', 'Leaf mark - mint'],
            ['gold', 'Leaf mark - harvest'],
        ] as [$palette, $title]) {
            $out[] = self::leafMark($title, $palette);
        }

        foreach ([
            ['STUDIO', 'slate', 'Lettermark - studio'],
            ['ATELIER', 'sand', 'Lettermark - atelier'],
            ['NORTHWIND', 'midnight', 'Lettermark - northwind'],
            ['MARJAN', 'berry', 'Lettermark - marjan'],
        ] as [$word, $palette, $title]) {
            $out[] = self::lettermark($title, $word, $palette);
        }

        foreach ([
            ['EST. 2026', 'COFFEE ROASTERS', 'sand', 'Ring emblem - roasters'],
            ['SINCE 1998', 'MOUNTAIN CLUB', 'forest', 'Ring emblem - mountain club'],
            ['HANDMADE', 'CERAMIC STUDIO', 'slate', 'Ring emblem - ceramic studio'],
            ['ORIGINAL', 'SURF SOCIETY', 'ocean', 'Ring emblem - surf society'],
        ] as [$top, $bottom, $palette, $title]) {
            $out[] = self::ringEmblem($title, $top, $bottom, $palette);
        }

        foreach ([
            ['indigo', 'Isometric cube - indigo'],
            ['ember', 'Isometric cube - ember'],
            ['ocean', 'Isometric cube - ocean'],
        ] as [$palette, $title]) {
            $out[] = self::isoCube($title, $palette);
        }

        foreach ([
            ['ocean', 'Arrow mark - forward'],
            ['forest', 'Arrow mark - growth'],
            ['plum', 'Arrow mark - pivot'],
        ] as [$palette, $title]) {
            $out[] = self::arrowMark($title, $palette);
        }

        foreach ([
            ['gold', 'Burst mark - gold'],
            ['coral', 'Burst mark - coral'],
            ['indigo', 'Burst mark - indigo'],
        ] as [$palette, $title]) {
            $out[] = self::burstMark($title, $palette);
        }

        foreach ([
            ['midnight', 'Crest mark - midnight'],
            ['gold', 'Crest mark - gold'],
            ['forest', 'Crest mark - forest'],
        ] as [$palette, $title]) {
            $out[] = self::crest($title, $palette);
        }

        foreach ([
            ['slate', 'Line mark - minimal square'],
            ['midnight', 'Line mark - minimal circle'],
            ['sand', 'Line mark - minimal arch'],
        ] as $i => [$palette, $title]) {
            $out[] = self::lineMark($title, $palette, $i);
        }

        return $out;
    }

    /* ================================================================== */
    /* Builders                                                            */
    /* ================================================================== */

    /** A letter inside a ringed disc - the workhorse monogram. */
    private static function monogramCircle(string $title, string $letter, string $palette): array
    {
        [$ink, $accent, , $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A ringed disc with a single letter. Swap the letter, recolour the ring, and the mark is yours.',
            400,
            400,
            [
                K::circle(200, 200, 150, array_merge(K::filled($ink), K::gradient($ink, $accent, 135))),
                K::circle(200, 200, 126, K::outlined($accent, 4, ['opacity' => 0.9])),
                K::circle(200, 200, 150, K::outlined($light, 2, ['opacity' => 0.35])),
            ],
            [
                K::text($letter, 200, 244, ['fill' => $ground, 'fontSize' => 132, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'monogram', 'circle', $palette]
        );
    }

    /** A hexagon with an inner outline and a word across the middle. */
    private static function hexBadge(string $title, string $word, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A hexagon badge with an inner keyline. Drag any point of either hexagon to reshape the mark.',
            400,
            400,
            [
                K::poly(K::polygonPoints(6, 200, 200, 156), array_merge(K::filled($accent), K::gradient($accent, $second, 150))),
                K::poly(K::polygonPoints(6, 200, 200, 126), K::outlined($ground, 5, ['opacity' => 0.9])),
                K::poly(K::polygonPoints(6, 200, 200, 156), K::outlined($ink, 4)),
                K::line(120, 236, 280, 236, K::outlined($light, 3, ['opacity' => 0.8])),
            ],
            [
                K::text($word, 200, 218, ['fill' => $ground, 'fontSize' => strlen($word) > 4 ? 38 : 48, 'fontWeight' => 'bold']),
                K::text('EST 2026', 200, 268, ['fill' => $light, 'fontSize' => 18, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'badge', 'hexagon', $palette]
        );
    }

    /** A shield carrying one of four simple glyphs. */
    private static function shieldMark(string $title, string $glyph, string $palette): array
    {
        [$ink, $accent, $second, , $ground] = K::palette($palette);

        $inner = match ($glyph) {
            'tick' => K::path(K::tick(200, 200, 120), K::outlined($ground, 18)),
            'star' => K::poly(K::starPoints(5, 200, 196, 66, 0.42), K::filled($ground)),
            'bolt' => K::path('M214 132 L158 212 H196 L186 268 L244 186 H204 Z', K::filled($ground)),
            default => K::path('M200 142 V262 M140 202 H260', K::outlined($ground, 20)),
        };

        return K::make(
            $title,
            'A shield built as a path, so every corner is a node you can pull. The glyph inside is a separate shape.',
            400,
            400,
            [
                K::path(K::shield(200, 48, 260, 310), array_merge(K::filled($accent), K::gradient($accent, $second, 160))),
                K::path(K::shield(200, 48, 260, 310), K::outlined($ink, 6)),
                K::path(K::shield(200, 70, 214, 262), K::outlined($ground, 2, ['opacity' => 0.4])),
                $inner,
            ],
            [],
            '#ffffff',
            ['logo', 'shield', 'security', $palette]
        );
    }

    /** A squircle app icon with a glyph - the shape iOS actually cuts to. */
    private static function appIcon(string $title, string $glyph, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $mark = match ($glyph) {
            'spark' => [K::path('M200 108 L228 180 L300 200 L228 220 L200 292 L172 220 L100 200 L172 180 Z', K::filled($ground))],
            'layers' => [
                K::poly('200,112 300,168 200,224 100,168', K::filled($ground)),
                K::poly('200,192 300,248 200,304 100,248', K::filled($ground, ['opacity' => 0.55])),
            ],
            'leaf' => [K::path('M292 108C292 236 244 300 152 300C152 196 200 128 292 108Z', K::filled($ground)),
                K::path('M262 138C216 176 176 228 158 292', K::outlined($accent, 7))],
            'chat' => [K::path(K::bubble(104, 116, 192, 132, 30, 30), K::filled($ground))],
            'play' => [K::poly('168,132 290,200 168,268', K::filled($ground))],
            default => [K::path('M128 272 L152 272 L282 142 A24 24 0 0 0 248 108 L118 238 Z', K::filled($ground))],
        };

        return K::make(
            $title,
            'An app icon on a squircle, the shape phone launchers actually mask to. The glyph is separate geometry.',
            400,
            400,
            array_merge(
                [
                    K::path(K::squircle(200, 200, 176), array_merge(K::filled($accent), K::gradient($second, $ink, 150), K::shadow(14, 26, $ink, 0.3))),
                    K::circle(120, 116, 90, K::filled($light, ['opacity' => 0.16])),
                ],
                $mark
            ),
            [],
            '#ffffff',
            ['logo', 'app-icon', 'squircle', $palette]
        );
    }

    /** Three overlapping discs - unity, collaboration, the usual. */
    private static function overlapCircles(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        return K::make(
            $title,
            'Three discs with multiply-style overlap done the honest way: two solid, one translucent.',
            400,
            400,
            [
                K::circle(154, 168, 92, K::filled($accent, ['opacity' => 0.9])),
                K::circle(246, 168, 92, K::filled($second, ['opacity' => 0.75])),
                K::circle(200, 250, 92, K::filled($ink, ['opacity' => 0.72])),
                K::circle(200, 200, 168, K::outlined($light, 2, ['opacity' => 0.6, 'strokeDasharray' => '6 10'])),
            ],
            [],
            '#ffffff',
            ['logo', 'abstract', 'circles', $palette]
        );
    }

    /** Stacked triangles - a mountain, a peak, a growth mark. */
    private static function triangleStack(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'Two peaks and a sun. Every element is a polygon or a circle, so nothing is welded together.',
            400,
            400,
            [
                K::circle(272, 132, 42, K::filled($second, ['opacity' => 0.9])),
                K::poly('120,300 196,148 272,300', K::filled($accent)),
                K::poly('216,300 268,204 320,300', K::filled($ink, ['opacity' => 0.85])),
                K::line(92, 300, 328, 300, K::outlined($ink, 8)),
                K::poly('160,232 196,148 232,232', K::filled($ground, ['opacity' => 0.35])),
                K::circle(200, 200, 170, K::outlined($light, 3, ['opacity' => 0.5])),
            ],
            [],
            '#ffffff',
            ['logo', 'mountain', 'peak', $palette]
        );
    }

    /** Layered waves - water, sound, motion. */
    private static function waveMark(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        return K::make(
            $title,
            'Three stacked waves, each an editable curve. Drag a node to change the swell.',
            400,
            400,
            [
                K::circle(200, 200, 160, K::filled($light, ['opacity' => 0.45])),
                K::path(K::wave(66, 170, 268, 30, 2), K::outlined($ink, 14)),
                K::path(K::wave(66, 210, 268, 30, 2), K::outlined($accent, 14)),
                K::path(K::wave(66, 250, 268, 30, 2), K::outlined($second, 14)),
                K::circle(200, 200, 160, K::outlined($ink, 5)),
            ],
            [],
            '#ffffff',
            ['logo', 'wave', 'water', $palette]
        );
    }

    /** A leaf with a vein - organic, wellness, sustainability. */
    private static function leafMark(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A leaf drawn as two curves with a vein through it. The circle behind is a separate shape.',
            400,
            400,
            [
                K::circle(200, 200, 154, array_merge(K::filled($light), K::gradient($light, $ground, 135))),
                K::path('M296 96C296 232 240 302 132 306C132 196 190 118 296 96Z', array_merge(K::filled($accent), K::gradient($accent, $second, 140))),
                K::path('M268 128C216 166 168 224 146 298', K::outlined($ground, 8)),
                K::path('M244 168C226 176 210 190 198 208M218 216C204 226 192 242 184 262', K::outlined($ground, 5, ['opacity' => 0.7])),
                K::circle(200, 200, 154, K::outlined($ink, 4)),
            ],
            [],
            '#ffffff',
            ['logo', 'leaf', 'nature', $palette]
        );
    }

    /** A wordmark with a rule and a tagline - the type-only logo. */
    private static function lettermark(string $title, string $word, string $palette): array
    {
        [$ink, $accent, , $light] = K::palette($palette);
        $size = max(30, (int) round(560 / max(6, strlen($word))));

        return K::make(
            $title,
            'A wordmark with a rule beneath it and space for a tagline. Type-only marks live or die on the spacing.',
            600,
            300,
            [
                K::line(140, 176, 460, 176, K::outlined($accent, 6)),
                K::circle(120, 176, 9, K::filled($accent)),
                K::circle(480, 176, 9, K::filled($accent)),
                K::rect(40, 40, 520, 220, 0, K::outlined($light, 2, ['opacity' => 0.7, 'strokeDasharray' => '10 12'])),
            ],
            [
                K::text($word, 300, 158, ['fill' => $ink, 'fontSize' => $size, 'fontWeight' => 'bold']),
                K::text('DESIGN & MAKE', 300, 214, ['fill' => $accent, 'fontSize' => 20, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'wordmark', 'type', $palette]
        );
    }

    /** A circular emblem with text top and bottom - the badge logo. */
    private static function ringEmblem(string $title, string $top, string $bottom, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A double-ring emblem with a rule across the middle. Both rings and every star are separate shapes.',
            420,
            420,
            array_merge(
                [
                    K::circle(210, 210, 190, K::filled($ink)),
                    K::circle(210, 210, 170, K::outlined($ground, 3, ['opacity' => 0.85])),
                    K::circle(210, 210, 132, K::outlined($accent, 6)),
                    K::line(96, 210, 324, 210, K::outlined($accent, 4, ['opacity' => 0.8])),
                    K::poly(K::starPoints(5, 128, 210, 13, 0.42), K::filled($second)),
                    K::poly(K::starPoints(5, 292, 210, 13, 0.42), K::filled($second)),
                ],
                K::dotRing(210, 210, 182, 36, 3, $light, 0.55)
            ),
            [
                K::text($bottom, 210, 190, ['fill' => $ground, 'fontSize' => 30, 'fontWeight' => 'bold']),
                K::text($top, 210, 252, ['fill' => $accent, 'fontSize' => 20, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'emblem', 'vintage', $palette]
        );
    }

    /** An isometric cube - three rhombi, three tones. */
    private static function isoCube(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        return K::make(
            $title,
            'A cube made of three rhombi, each its own polygon - recolour a face without touching the others.',
            400,
            400,
            [
                K::circle(200, 200, 160, K::filled($light, ['opacity' => 0.4])),
                K::poly('200,80 320,150 200,220 80,150', K::filled($accent)),
                K::poly('80,150 200,220 200,340 80,270', K::filled($second, ['opacity' => 0.92])),
                K::poly('320,150 320,270 200,340 200,220', K::filled($ink, ['opacity' => 0.88])),
                K::poly('200,80 320,150 200,220 80,150', K::outlined($ink, 3, ['opacity' => 0.6])),
            ],
            [],
            '#ffffff',
            ['logo', 'isometric', 'cube', $palette]
        );
    }

    /** An arrow inside a ring - forward, delivery, momentum. */
    private static function arrowMark(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        return K::make(
            $title,
            'An arrow through an open ring. The ring is an arc, so its ends can be dragged anywhere.',
            400,
            400,
            [
                K::circle(200, 200, 156, K::filled($light, ['opacity' => 0.4])),
                // 120 to 420 rather than 120 to 60, so the arc takes the long
                // way round and reads as an open ring rather than a smile.
                K::path(K::arc(200, 200, 132, 120, 420), K::outlined($ink, 22, ['strokeLinecap' => 'round'])),
                K::poly(K::arrow(120, 244, 288, 150, 13, 46), K::filled($accent)),
                K::circle(120, 244, 14, K::filled($second)),
            ],
            [],
            '#ffffff',
            ['logo', 'arrow', 'motion', $palette]
        );
    }

    /** A starburst behind a disc - energy, launch, offers. */
    private static function burstMark(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A sixteen-point burst behind a disc. The burst is one polygon - drag any spike to reshape it.',
            400,
            400,
            [
                K::poly(K::starPoints(16, 200, 200, 172, 0.74), K::filled($second, ['opacity' => 0.85])),
                K::poly(K::starPoints(16, 200, 200, 150, 0.78), K::filled($accent)),
                K::circle(200, 200, 104, array_merge(K::filled($ink), K::shadow(6, 14, $ink, 0.35))),
                K::circle(200, 200, 88, K::outlined($light, 3, ['opacity' => 0.7])),
            ],
            [
                K::text('GO', 200, 222, ['fill' => $ground, 'fontSize' => 54, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'burst', 'energy', $palette]
        );
    }

    /** A crest with a banner - heraldic without the clip art. */
    private static function crest(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A crest with a ribbon banner. Shield, banner and wings are separate, so any one can be dropped.',
            420,
            460,
            [
                K::path(K::shield(210, 54, 250, 300), array_merge(K::filled($ink), K::gradient($ink, $accent, 160))),
                K::path(K::shield(210, 74, 206, 254), K::outlined($ground, 3, ['opacity' => 0.55])),
                K::line(96, 178, 324, 178, K::outlined($ground, 4, ['opacity' => 0.7])),
                K::poly(K::starPoints(5, 210, 138, 38, 0.44), K::filled($second)),
                K::poly('58,352 210,318 362,352 362,404 210,372 58,404', K::filled($accent)),
                K::poly('58,352 58,404 30,378', K::filled($second)),
                K::poly('362,352 362,404 390,378', K::filled($second)),
            ],
            [
                K::text('SINCE 2026', 210, 260, ['fill' => $light, 'fontSize' => 22, 'fontWeight' => 'bold']),
                K::text('THE CREST', 210, 384, ['fill' => $ground, 'fontSize' => 30, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['logo', 'crest', 'heraldic', $palette]
        );
    }

    /** A single-weight line mark - the quiet end of the category. */
    private static function lineMark(string $title, string $palette, int $variant): array
    {
        [$ink, $accent, , $light] = K::palette($palette);

        $shape = match ($variant) {
            0 => [
                K::rect(96, 96, 208, 208, 12, K::outlined($ink, 10)),
                K::rect(148, 148, 104, 104, 8, K::outlined($accent, 10)),
                K::line(96, 200, 148, 200, K::outlined($light, 10)),
            ],
            1 => [
                K::circle(200, 200, 112, K::outlined($ink, 10)),
                K::path(K::arc(200, 200, 112, -90, 90), K::outlined($accent, 10)),
                K::circle(200, 88, 12, K::filled($accent)),
            ],
            default => [
                K::path('M96 300 V180 A104 104 0 0 1 304 180 V300', K::outlined($ink, 12)),
                K::path('M148 300 V186 A52 52 0 0 1 252 186 V300', K::outlined($accent, 12)),
            ],
        };

        return K::make(
            $title,
            'A single-weight line mark. Nothing is filled, so it prints, embroiders and engraves cleanly.',
            400,
            400,
            $shape,
            [],
            '#ffffff',
            ['logo', 'line', 'minimal', $palette]
        );
    }
}
