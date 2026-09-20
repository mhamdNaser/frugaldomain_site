<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Frames category: borders, photo masks, mockups and collage grids.
 *
 * Two kinds of template live here, and they are used differently:
 *
 *   - a **frame** sits on top of artwork, so it is mostly outline with a
 *     hollow middle;
 *   - a **mask** sits over a photo and is filled, because clipping needs a
 *     solid shape to cut against - put the photo underneath, select both and
 *     clip.
 *
 * Both are ordinary geometry, so a frame can be recoloured, thickened or
 * node-edited, and a mask can be reshaped before it is used to cut.
 */
final class FrameTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['circle', 'slate', 'Photo mask - circle'],
            ['squircle', 'midnight', 'Photo mask - squircle'],
            ['hexagon', 'forest', 'Photo mask - hexagon'],
            ['arch', 'sand', 'Photo mask - arch'],
            ['blob', 'coral', 'Photo mask - blob'],
            ['diamond', 'indigo', 'Photo mask - diamond'],
            ['star', 'gold', 'Photo mask - star'],
            ['heart', 'berry', 'Photo mask - heart'],
        ] as [$shape, $palette, $title]) {
            $out[] = self::photoMask($title, $shape, $palette);
        }

        foreach ([
            ['slate', 'square', 'Corner brackets - square'],
            ['midnight', 'thin', 'Corner brackets - thin'],
            ['gold', 'serif', 'Corner brackets - serif'],
        ] as [$palette, $style, $title]) {
            $out[] = self::cornerBrackets($title, $style, $palette);
        }

        foreach ([
            ['slate', 'Double line frame - slate'],
            ['gold', 'Double line frame - gold'],
            ['forest', 'Double line frame - forest'],
        ] as [$palette, $title]) {
            $out[] = self::doubleLine($title, $palette);
        }

        foreach ([
            ['gold', 'Art deco frame - gold'],
            ['midnight', 'Art deco frame - midnight'],
            ['berry', 'Art deco frame - berry'],
        ] as [$palette, $title]) {
            $out[] = self::artDeco($title, $palette);
        }

        foreach ([
            ['slate', 'square', 'Polaroid frame - square'],
            ['sand', 'portrait', 'Polaroid frame - portrait'],
            ['midnight', 'landscape', 'Polaroid frame - landscape'],
        ] as [$palette, $ratio, $title]) {
            $out[] = self::polaroid($title, $ratio, $palette);
        }

        foreach ([
            ['midnight', 4, 'Film strip - four frames'],
            ['slate', 3, 'Film strip - three frames'],
        ] as [$palette, $count, $title]) {
            $out[] = self::filmStrip($title, $count, $palette);
        }

        foreach ([
            ['gold', 'CERTIFICATE OF ACHIEVEMENT', 'Certificate border - gold'],
            ['midnight', 'CERTIFICATE OF COMPLETION', 'Certificate border - midnight'],
            ['forest', 'CERTIFICATE OF EXCELLENCE', 'Certificate border - forest'],
            ['berry', 'AWARD OF RECOGNITION', 'Certificate border - recognition'],
        ] as [$palette, $heading, $title]) {
            $out[] = self::certificate($title, $heading, $palette);
        }

        foreach ([
            ['sand', 'Taped photo frame - sand'],
            ['slate', 'Taped photo frame - slate'],
            ['coral', 'Taped photo frame - coral'],
        ] as [$palette, $title]) {
            $out[] = self::tapedFrame($title, $palette);
        }

        foreach ([
            ['ember', 'ADMIT ONE', 'Ticket frame - admit one'],
            ['ocean', 'BOARDING PASS', 'Ticket frame - boarding pass'],
        ] as [$palette, $word, $title]) {
            $out[] = self::ticket($title, $word, $palette);
        }

        foreach ([
            ['phone', 'midnight', 'Device frame - phone'],
            ['tablet', 'slate', 'Device frame - tablet'],
            ['laptop', 'midnight', 'Device frame - laptop'],
            ['browser', 'slate', 'Device frame - browser window'],
            ['watch', 'midnight', 'Device frame - watch'],
        ] as [$device, $palette, $title]) {
            $out[] = self::device($title, $device, $palette);
        }

        foreach ([
            ['gold', 'Ring frame - laurel ring'],
            ['ocean', 'Ring frame - dotted ring'],
            ['plum', 'Ring frame - double ring'],
        ] as $i => [$palette, $title]) {
            $out[] = self::ringFrame($title, $i, $palette);
        }

        foreach ([
            ['sand', 'Ornament corners - botanical'],
            ['midnight', 'Ornament corners - geometric'],
            ['gold', 'Ornament corners - scroll'],
        ] as $i => [$palette, $title]) {
            $out[] = self::ornament($title, $i, $palette);
        }

        foreach ([
            ['slate', 'grid4', 'Collage grid - four up'],
            ['midnight', 'feature', 'Collage grid - one feature and three'],
            ['sand', 'strip', 'Collage grid - vertical strip'],
            ['forest', 'mosaic', 'Collage grid - mosaic six'],
        ] as [$palette, $layout, $title]) {
            $out[] = self::collage($title, $layout, $palette);
        }

        foreach ([
            ['midnight', 'Story safe-area frame'],
            ['slate', 'Post safe-area frame'],
        ] as $i => [$palette, $title]) {
            $out[] = self::safeArea($title, $i, $palette);
        }

        foreach ([
            ['coral', 'wave', 'Edge frame - wave bottom'],
            ['ocean', 'torn', 'Edge frame - torn paper'],
            ['forest', 'zigzag', 'Edge frame - zigzag'],
            ['gold', 'scallop', 'Edge frame - scallop'],
        ] as [$palette, $edge, $title]) {
            $out[] = self::edgeFrame($title, $edge, $palette);
        }

        foreach ([
            ['indigo', 'Gradient border frame - indigo'],
            ['coral', 'Gradient border frame - coral'],
            ['mint', 'Gradient border frame - mint'],
        ] as [$palette, $title]) {
            $out[] = self::gradientBorder($title, $palette);
        }

        return $out;
    }

    /* ================================================================== */
    /* Builders                                                            */
    /* ================================================================== */

    /** A filled shape for clipping a photograph to. */
    private static function photoMask(string $title, string $shape, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);
        $fill = array_merge(K::filled($accent), K::gradient($accent, $second, 145));

        $mask = match ($shape) {
            'circle' => K::circle(300, 300, 250, $fill),
            'squircle' => K::path(K::squircle(300, 300, 250), $fill),
            'hexagon' => K::poly(K::polygonPoints(6, 300, 300, 258, 0), $fill),
            'arch' => K::path('M60 560 V240 A240 240 0 0 1 540 240 V560 Z', $fill),
            'blob' => K::path(K::blob(300, 300, 240, 8, 0.2, 4), $fill),
            'diamond' => K::poly('300,40 560,300 300,560 40,300', $fill),
            'star' => K::poly(K::starPoints(6, 300, 300, 260, 0.56), $fill),
            default => K::path(K::heart(300, 320, 520), $fill),
        };

        return K::make(
            $title,
            'A solid shape for clipping. Put a photo underneath, select both, and clip - the photo takes this outline.',
            600,
            600,
            [
                K::rect(20, 20, 560, 560, 18, K::outlined($light, 2, ['opacity' => 0.8, 'strokeDasharray' => '12 14'])),
                $mask,
            ],
            [
                K::text('CLIP A PHOTO TO THIS SHAPE', 300, 588, ['fill' => $ink, 'fontSize' => 17, 'fontWeight' => 'bold', 'opacity' => 0.5]),
            ],
            '#ffffff',
            ['frame', 'mask', 'clip', $shape]
        );
    }

    /** Four corner marks - a frame that does not box the artwork in. */
    private static function cornerBrackets(string $title, string $style, string $palette): array
    {
        [$ink, $accent, , $light] = K::palette($palette);
        $weight = $style === 'thin' ? 3 : ($style === 'serif' ? 8 : 6);
        $arm = $style === 'thin' ? 110 : 80;

        $marks = [
            K::path('M40 ' . (40 + $arm) . 'V40H' . (40 + $arm), K::outlined($ink, $weight)),
            K::path('M' . (760 - $arm) . ' 40H760V' . (40 + $arm), K::outlined($ink, $weight)),
            K::path('M760 ' . (560 - $arm) . 'V560H' . (760 - $arm), K::outlined($ink, $weight)),
            K::path('M' . (40 + $arm) . ' 560H40V' . (560 - $arm), K::outlined($ink, $weight)),
        ];

        if ($style === 'serif') {
            $marks[] = K::circle(40, 40, 10, K::filled($accent));
            $marks[] = K::circle(760, 40, 10, K::filled($accent));
            $marks[] = K::circle(760, 560, 10, K::filled($accent));
            $marks[] = K::circle(40, 560, 10, K::filled($accent));
        }

        return K::make(
            $title,
            'Corner brackets rather than a closed border - the artwork breathes and the frame still holds it.',
            800,
            600,
            array_merge(
                [K::rect(40, 40, 720, 520, 0, K::outlined($light, 2, ['opacity' => 0.4, 'strokeDasharray' => '10 12']))],
                $marks
            ),
            [],
            '#ffffff',
            ['frame', 'corners', 'minimal', $palette]
        );
    }

    /** A classic double keyline with a tinted ground. */
    private static function doubleLine(string $title, string $palette): array
    {
        [$ink, $accent, , $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A double keyline frame with a tinted mat. Both lines are separate rectangles, so either can go.',
            800,
            600,
            [
                K::rect(0, 0, 800, 600, 0, K::filled($ground)),
                K::rect(34, 34, 732, 532, 0, K::outlined($ink, 8)),
                K::rect(54, 54, 692, 492, 0, K::outlined($accent, 2)),
                K::rect(78, 78, 644, 444, 0, K::filled($light, ['opacity' => 0.55])),
                K::rect(78, 78, 644, 444, 0, K::outlined($ink, 1.5, ['opacity' => 0.4])),
            ],
            [],
            $ground,
            ['frame', 'border', 'classic', $palette]
        );
    }

    /** A deco frame - stepped corners and a fan motif. */
    private static function artDeco(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $fan = function (float $cx, float $cy, float $r, int $rays) use ($accent) {
            $out = [];
            for ($i = 0; $i <= $rays; $i++) {
                $angle = 180 + ($i / $rays) * 180;
                [$x, $y] = K::onCircle($cx, $cy, $r, $angle);
                $out[] = K::line($cx, $cy, $x, $y, K::outlined($accent, 3));
            }

            return $out;
        };

        return K::make(
            $title,
            'An art-deco frame: stepped corners, a keyline and two fans. Every ray is a separate line.',
            700,
            900,
            array_merge(
                [
                    K::rect(0, 0, 700, 900, 0, K::filled($ink)),
                    K::path('M60 140 V100 H100 V60 H600 V100 H640 V140 V760 V800 H600 V840 H100 V800 H60 V760 Z', K::outlined($accent, 6)),
                    K::rect(84, 124, 532, 652, 0, K::outlined($light, 2, ['opacity' => 0.55])),
                    K::poly('350,180 386,216 350,252 314,216', K::filled($second)),
                    K::poly('350,648 386,684 350,720 314,684', K::filled($second)),
                    K::line(120, 216, 300, 216, K::outlined($accent, 3)),
                    K::line(400, 216, 580, 216, K::outlined($accent, 3)),
                ],
                $fan(350, 400, 110, 9),
                [K::circle(350, 400, 34, K::filled($ink)), K::circle(350, 400, 34, K::outlined($accent, 3))]
            ),
            [],
            $ink,
            ['frame', 'art-deco', 'ornate', $palette]
        );
    }

    /** A polaroid with a caption strip along the bottom. */
    private static function polaroid(string $title, string $ratio, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        [$w, $h, $imgH] = match ($ratio) {
            'portrait' => [520, 660, 480],
            'landscape' => [660, 520, 340],
            default => [560, 640, 460],
        };

        return K::make(
            $title,
            'A polaroid: white card, photo well and a caption strip. Clip a photo into the well.',
            $w + 80,
            $h + 80,
            [
                K::rect(40, 40, $w, $h, 6, array_merge(K::filled($ground), K::shadow(14, 26, $ink, 0.25))),
                K::rect(74, 74, $w - 68, $imgH, 2, K::filled($light)),
                K::rect(74, 74, $w - 68, $imgH, 2, K::outlined($ink, 2, ['opacity' => 0.25])),
                K::path(K::blob(40 + $w / 2, 74 + $imgH / 2, min($w, $imgH) * 0.3, 7, 0.16, 6), K::filled($accent, ['opacity' => 0.45])),
                K::circle(40 + $w * 0.72, 74 + $imgH * 0.28, 42, K::filled($second, ['opacity' => 0.5])),
            ],
            [
                K::text('Riyadh, September 2026', 40 + $w / 2, 74 + $imgH + 68, ['fill' => $ink, 'fontSize' => 26, 'opacity' => 0.7]),
            ],
            '#e2e8f0',
            ['frame', 'polaroid', 'photo', $palette]
        );
    }

    /** A strip of film with sprocket holes. */
    private static function filmStrip(string $title, int $count, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $width = 120 + $count * 260;
        $shapes = [
            K::rect(0, 60, $width, 420, 0, K::filled($ink)),
        ];

        for ($x = 30; $x < $width - 20; $x += 56) {
            $shapes[] = K::rect($x, 86, 30, 30, 5, K::filled($ground, ['opacity' => 0.9]));
            $shapes[] = K::rect($x, 424, 30, 30, 5, K::filled($ground, ['opacity' => 0.9]));
        }

        for ($i = 0; $i < $count; $i++) {
            $x = 60 + $i * 260;
            $shapes[] = K::rect($x, 140, 220, 260, 4, K::filled($light));
            $shapes[] = K::path(K::blob($x + 110, 270, 76, 7, 0.18, $i + 2), K::filled($i % 2 ? $second : $accent, ['opacity' => 0.55]));
        }

        return K::make(
            $title,
            'A film strip with sprocket holes and empty frames. Clip a photo into each frame.',
            $width,
            540,
            $shapes,
            [],
            '#ffffff',
            ['frame', 'film', 'strip', $palette]
        );
    }

    /** A certificate border with a seal and signature rules. */
    private static function certificate(string $title, string $heading, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A landscape certificate: border, seal, and two signature rules. Replace the words and print it.',
            1120,
            800,
            array_merge(
                [
                    K::rect(0, 0, 1120, 800, 0, K::filled($ground)),
                    K::rect(36, 36, 1048, 728, 0, K::outlined($accent, 10)),
                    K::rect(58, 58, 1004, 684, 0, K::outlined($ink, 2)),
                    K::rect(78, 78, 964, 644, 0, K::outlined($accent, 1.5, ['opacity' => 0.5, 'strokeDasharray' => '4 8'])),
                    K::line(400, 300, 720, 300, K::outlined($accent, 3)),
                    K::line(200, 640, 440, 640, K::outlined($ink, 2)),
                    K::line(680, 640, 920, 640, K::outlined($ink, 2)),
                    K::poly(K::starPoints(32, 930, 560, 74, 0.9), K::filled($accent)),
                    K::circle(930, 560, 58, K::filled($ink)),
                    K::poly(K::starPoints(5, 930, 556, 26, 0.42), K::filled($accent)),
                ],
                K::dotRing(930, 560, 58, 20, 2.5, $light, 0.6)
            ),
            [
                K::text($heading, 560, 250, ['fill' => $ink, 'fontSize' => 44, 'fontWeight' => 'bold']),
                K::text('This is presented to', 560, 360, ['fill' => $ink, 'fontSize' => 24, 'opacity' => 0.7]),
                K::text('RECIPIENT NAME', 560, 440, ['fill' => $accent, 'fontSize' => 62, 'fontWeight' => 'bold']),
                K::text('for outstanding work completed in September 2026', 560, 500, ['fill' => $ink, 'fontSize' => 24, 'opacity' => 0.7]),
                K::text('DATE', 320, 676, ['fill' => $ink, 'fontSize' => 20, 'opacity' => 0.6]),
                K::text('SIGNATURE', 800, 676, ['fill' => $ink, 'fontSize' => 20, 'opacity' => 0.6]),
            ],
            $ground,
            ['frame', 'certificate', 'award', $palette]
        );
    }

    /** A photo held on by two pieces of tape. */
    private static function tapedFrame(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A photo card held by two strips of tape. Rotate either strip, or move them to the corners.',
            700,
            620,
            [
                K::rect(90, 90, 520, 440, 4, array_merge(K::filled($ground), K::shadow(12, 22, $ink, 0.2))),
                K::rect(118, 118, 464, 384, 2, K::filled($light)),
                K::path(K::blob(350, 310, 130, 7, 0.18, 9), K::filled($accent, ['opacity' => 0.45])),
                K::rect(60, 58, 160, 52, 2, K::filled($second, ['opacity' => 0.6, 'rotation' => -18])),
                K::rect(480, 508, 160, 52, 2, K::filled($second, ['opacity' => 0.6, 'rotation' => -12])),
                K::line(70, 84, 210, 84, K::outlined($ground, 2, ['opacity' => 0.4, 'rotation' => -18])),
            ],
            [],
            '#f1f5f9',
            ['frame', 'tape', 'scrapbook', $palette]
        );
    }

    /** A ticket with a perforation and notches. */
    private static function ticket(string $title, string $word, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A ticket with notches and a perforated stub. Every notch is a circle you can move or remove.',
            900,
            380,
            array_merge(
                [
                    K::rect(40, 40, 820, 300, 24, array_merge(K::filled($accent), K::gradient($accent, $second, 150))),
                    K::rect(40, 40, 820, 300, 24, K::outlined($ink, 5)),
                    K::circle(620, 40, 34, K::filled($ground)),
                    K::circle(620, 340, 34, K::filled($ground)),
                    K::line(620, 92, 620, 288, K::outlined($ground, 4, ['opacity' => 0.8, 'strokeDasharray' => '10 14'])),
                    K::rect(80, 80, 480, 6, 3, K::filled($ground, ['opacity' => 0.45])),
                ],
                K::dotGrid(660, 120, 160, 140, 34, 4, $ground, 0.4)
            ),
            [
                K::text($word, 320, 200, ['fill' => $ground, 'fontSize' => 56, 'fontWeight' => 'bold']),
                K::text('ROW 12 · SEAT 4 · 14 NOV 2026', 320, 256, ['fill' => $light, 'fontSize' => 22, 'fontWeight' => 'bold']),
                K::text('No. 0042', 740, 300, ['fill' => $ground, 'fontSize' => 22, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['frame', 'ticket', 'stub', $palette]
        );
    }

    /** A device mockup to drop a screenshot into. */
    private static function device(string $title, string $device, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $screen = K::filled($light, ['opacity' => 0.9]);

        return match ($device) {
            'phone' => K::make(
                $title,
                'A phone mockup with a notch and a home bar. Clip a screenshot to the screen rectangle.',
                480,
                900,
                [
                    K::rect(60, 40, 360, 820, 54, array_merge(K::filled($ink), K::shadow(16, 30, $ink, 0.35))),
                    K::rect(74, 54, 332, 792, 46, K::filled($ground, ['opacity' => 0.12])),
                    K::rect(86, 66, 308, 768, 40, $screen),
                    K::rect(186, 66, 108, 26, 13, K::filled($ink)),
                    K::rect(190, 812, 100, 6, 3, K::filled($ink, ['opacity' => 0.5])),
                    K::rect(432, 240, 6, 70, 3, K::filled($ink)),
                    K::rect(42, 220, 6, 46, 3, K::filled($ink)),
                    K::rect(42, 290, 6, 80, 3, K::filled($ink)),
                    K::rect(86, 66, 308, 190, 0, K::filled($accent, ['opacity' => 0.5])),
                ],
                [],
                '#ffffff',
                ['frame', 'mockup', 'phone', $palette]
            ),
            'tablet' => K::make(
                $title,
                'A tablet mockup in landscape, with a camera dot and an even bezel.',
                900,
                660,
                [
                    K::rect(40, 40, 820, 580, 34, array_merge(K::filled($ink), K::shadow(16, 30, $ink, 0.32))),
                    K::rect(84, 84, 732, 492, 12, $screen),
                    K::circle(62, 330, 7, K::filled($ground, ['opacity' => 0.6])),
                    K::rect(84, 84, 732, 120, 0, K::filled($accent, ['opacity' => 0.5])),
                    K::rect(120, 240, 300, 18, 9, K::filled($ink, ['opacity' => 0.18])),
                    K::rect(120, 286, 480, 18, 9, K::filled($ink, ['opacity' => 0.12])),
                ],
                [],
                '#ffffff',
                ['frame', 'mockup', 'tablet', $palette]
            ),
            'laptop' => K::make(
                $title,
                'A laptop mockup: lid, screen and base. Clip the screenshot into the screen rectangle.',
                1000,
                680,
                [
                    K::rect(120, 40, 760, 500, 20, K::filled($ink)),
                    K::rect(146, 66, 708, 448, 6, $screen),
                    K::rect(146, 66, 708, 110, 0, K::filled($accent, ['opacity' => 0.5])),
                    K::circle(500, 54, 5, K::filled($ground, ['opacity' => 0.6])),
                    K::poly('60,560 940,560 1000,620 0,620', K::filled($ink)),
                    K::rect(430, 574, 140, 10, 5, K::filled($ground, ['opacity' => 0.22])),
                ],
                [],
                '#ffffff',
                ['frame', 'mockup', 'laptop', $palette]
            ),
            'watch' => K::make(
                $title,
                'A watch mockup with a strap drawn as two tapered polygons.',
                420,
                700,
                [
                    K::poly('150,20 270,20 250,180 170,180', K::filled($ink, ['opacity' => 0.9])),
                    K::poly('170,520 250,520 270,680 150,680', K::filled($ink, ['opacity' => 0.9])),
                    K::rect(100, 160, 220, 380, 58, array_merge(K::filled($ink), K::shadow(12, 24, $ink, 0.35))),
                    K::rect(118, 178, 184, 344, 46, $screen),
                    K::rect(320, 260, 10, 54, 5, K::filled($ink)),
                    K::circle(210, 350, 66, K::outlined($accent, 12, ['opacity' => 0.85])),
                ],
                [],
                '#ffffff',
                ['frame', 'mockup', 'watch', $palette]
            ),
            default => K::make(
                $title,
                'A browser window with a title bar, traffic lights and an address field.',
                1000,
                700,
                [
                    K::rect(40, 40, 920, 620, 18, array_merge(K::filled($ground), K::shadow(14, 28, $ink, 0.24))),
                    K::rect(40, 40, 920, 64, 18, K::filled($light)),
                    K::rect(40, 86, 920, 18, 0, K::filled($light)),
                    K::circle(78, 72, 9, K::filled($second)),
                    K::circle(106, 72, 9, K::filled($accent)),
                    K::circle(134, 72, 9, K::filled($ink, ['opacity' => 0.4])),
                    K::rect(170, 56, 620, 32, 16, K::filled($ground)),
                    K::rect(40, 104, 920, 556, 0, K::filled($ground)),
                    K::rect(40, 104, 920, 200, 0, K::filled($accent, ['opacity' => 0.16])),
                    K::rect(90, 350, 340, 20, 10, K::filled($ink, ['opacity' => 0.12])),
                    K::rect(90, 392, 540, 20, 10, K::filled($ink, ['opacity' => 0.08])),
                    K::line(40, 104, 960, 104, K::outlined($ink, 2, ['opacity' => 0.15])),
                ],
                [
                    K::text('frugaldomain.site', 480, 80, ['fill' => $ink, 'fontSize' => 20, 'opacity' => 0.55]),
                ],
                '#ffffff',
                ['frame', 'mockup', 'browser', $palette]
            ),
        };
    }

    /** A circular frame for a portrait or a logo. */
    private static function ringFrame(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        $rings = match ($variant) {
            0 => array_merge(
                [K::circle(300, 300, 230, K::outlined($accent, 10))],
                array_map(function ($i) use ($accent) {
                    $angle = 150 + ($i / 7) * 60;
                    [$x, $y] = K::onCircle(300, 300, 252, $angle);

                    return K::ellipse($x, $y, 22, 9, K::filled($accent, ['rotation' => $angle + 90]));
                }, range(0, 7)),
                array_map(function ($i) use ($accent) {
                    $angle = 150 + ($i / 7) * 60;
                    [$x, $y] = K::onCircle(300, 300, 252, $angle);

                    return K::ellipse(600 - $x, $y, 22, 9, K::filled($accent, ['rotation' => -($angle + 90)]));
                }, range(0, 7))
            ),
            1 => array_merge(
                [K::circle(300, 300, 236, K::outlined($ink, 4, ['strokeDasharray' => '2 14']))],
                K::dotRing(300, 300, 262, 28, 6, $accent, 0.9),
                [K::circle(300, 300, 208, K::outlined($accent, 6))]
            ),
            default => [
                K::circle(300, 300, 252, K::outlined($ink, 3)),
                K::circle(300, 300, 232, K::outlined($accent, 12)),
                K::circle(300, 300, 206, K::outlined($second, 3)),
            ],
        };

        return K::make(
            $title,
            'A ring frame for a portrait or a mark. Drop a photo behind it, or clip one to the inner circle.',
            600,
            600,
            array_merge(
                [K::circle(300, 300, 196, K::filled($light, ['opacity' => 0.5]))],
                $rings
            ),
            [],
            '#ffffff',
            ['frame', 'ring', 'portrait', $palette]
        );
    }

    /** Decorative corner ornaments on an otherwise plain frame. */
    private static function ornament(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        $corner = function (float $x, float $y, float $sx, float $sy) use ($variant, $accent, $second) {
            $s = fn(float $dx, float $dy) => [$x + $dx * $sx, $y + $dy * $sy];

            return match ($variant) {
                0 => [
                    K::path('M' . implode(' ', $s(0, 90)) . ' C' . implode(' ', $s(0, 30)) . ' ' . implode(' ', $s(30, 0)) . ' ' . implode(' ', $s(90, 0)), K::outlined($accent, 5)),
                    K::ellipse(...array_merge($s(28, 28), [16, 9]), ...[K::filled($second, ['rotation' => 45 * ($sx * $sy > 0 ? 1 : -1)])]),
                    K::circle(...array_merge($s(58, 14), [7]), ...[K::filled($accent)]),
                ],
                1 => [
                    K::path('M' . implode(' ', $s(0, 100)) . ' V' . $s(0, 0)[1] . ' H' . $s(100, 0)[0], K::outlined($accent, 6)),
                    K::path('M' . implode(' ', $s(22, 70)) . ' V' . $s(22, 22)[1] . ' H' . $s(70, 22)[0], K::outlined($second, 3)),
                    K::poly(implode(' ', [
                        implode(',', $s(40, 40)), implode(',', $s(60, 40)), implode(',', $s(40, 60)),
                    ]), K::filled($accent)),
                ],
                default => [
                    K::path('M' . implode(' ', $s(0, 110)) . ' C' . implode(' ', $s(0, 40)) . ' ' . implode(' ', $s(40, 0)) . ' ' . implode(' ', $s(110, 0)), K::outlined($accent, 6)),
                    K::path('M' . implode(' ', $s(20, 96)) . ' C' . implode(' ', $s(26, 52)) . ' ' . implode(' ', $s(52, 26)) . ' ' . implode(' ', $s(96, 20)), K::outlined($second, 3)),
                    K::circle(...array_merge($s(72, 72), [9]), ...[K::filled($second)]),
                ],
            };
        };

        return K::make(
            $title,
            'A plain keyline with ornamented corners. Each ornament is its own group of curves, so three can go.',
            800,
            600,
            K::flatten(
                [K::rect(46, 46, 708, 508, 0, K::outlined($ink, 2, ['opacity' => 0.55]))],
                $corner(46, 46, 1, 1),
                $corner(754, 46, -1, 1),
                $corner(754, 554, -1, -1),
                $corner(46, 554, 1, -1),
                [K::rect(92, 92, 616, 416, 0, K::outlined($light, 2, ['opacity' => 0.5, 'strokeDasharray' => '8 10']))]
            ),
            [],
            '#ffffff',
            ['frame', 'ornament', 'corners', $palette]
        );
    }

    /** A collage grid - several wells to clip photos into. */
    private static function collage(string $title, string $layout, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $gap = 16;

        $wells = match ($layout) {
            'grid4' => [[40, 40, 460, 340], [516, 40, 460, 340], [40, 396, 460, 340], [516, 396, 460, 340]],
            'feature' => [[40, 40, 620, 696], [676, 40, 300, 220], [676, 278, 300, 220], [676, 516, 300, 220]],
            'strip' => [[40, 40, 936, 200], [40, 256, 936, 200], [40, 472, 936, 264]],
            default => [[40, 40, 300, 340], [356, 40, 300, 160], [672, 40, 304, 160], [356, 216, 620, 164], [40, 396, 460, 340], [516, 396, 460, 340]],
        };

        $shapes = [K::rect(0, 0, 1016, 776, 0, K::filled($ground))];
        foreach ($wells as $index => [$x, $y, $w, $h]) {
            $tint = [$accent, $second, $light][$index % 3];
            $shapes[] = K::rect($x, $y, $w, $h, 14, K::filled($tint, ['opacity' => 0.35]));
            $shapes[] = K::rect($x, $y, $w, $h, 14, K::outlined($ink, 2, ['opacity' => 0.4]));
            $shapes[] = K::path(K::blob($x + $w / 2, $y + $h / 2, min($w, $h) * 0.24, 7, 0.16, $index + 3), K::filled($tint, ['opacity' => 0.5]));
        }

        return K::make(
            $title,
            'A collage grid of wells. Clip one photo into each - the gaps are even, so nothing needs nudging.',
            1016,
            776,
            $shapes,
            [],
            $ground,
            ['frame', 'collage', 'grid', $palette]
        );
    }

    /** A guide frame showing where platform chrome will cover the artwork. */
    private static function safeArea(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        [$w, $h] = $variant === 0 ? [1080, 1920] : [1080, 1080];
        $top = $variant === 0 ? 250 : 120;
        $bottom = $variant === 0 ? 320 : 120;

        return K::make(
            $title,
            'A guide frame marking the area platform chrome covers. Design inside the middle box and delete this layer before export.',
            $w,
            $h,
            [
                K::rect(0, 0, $w, $top, 0, K::filled($second, ['opacity' => 0.22])),
                K::rect(0, $h - $bottom, $w, $bottom, 0, K::filled($second, ['opacity' => 0.22])),
                K::rect(60, $top, $w - 120, $h - $top - $bottom, 0, K::outlined($accent, 4, ['strokeDasharray' => '18 14'])),
                K::line(0, $top, $w, $top, K::outlined($accent, 2, ['opacity' => 0.6])),
                K::line(0, $h - $bottom, $w, $h - $bottom, K::outlined($accent, 2, ['opacity' => 0.6])),
                K::line($w / 2, 0, $w / 2, $h, K::outlined($ink, 1.5, ['opacity' => 0.25, 'strokeDasharray' => '10 12'])),
                K::line(0, $h / 2, $w, $h / 2, K::outlined($ink, 1.5, ['opacity' => 0.25, 'strokeDasharray' => '10 12'])),
            ],
            [
                K::text('KEEP TYPE INSIDE THIS BOX', $w / 2, $top + 60, ['fill' => $accent, 'fontSize' => 34, 'fontWeight' => 'bold']),
                K::text('COVERED BY THE INTERFACE', $w / 2, $top - 60, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold', 'opacity' => 0.6]),
                K::text('COVERED BY THE INTERFACE', $w / 2, $h - $bottom + 70, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold', 'opacity' => 0.6]),
            ],
            $ground,
            ['frame', 'guide', 'safe-area', $palette]
        );
    }

    /** A frame whose bottom edge is a shaped band. */
    private static function edgeFrame(string $title, string $edge, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $band = match ($edge) {
            'wave' => [K::path(K::wave(-20, 600, 840, 42, 3) . ' L820 760 L-20 760 Z', K::filled($accent))],
            'torn' => [K::poly('-20,620 60,596 140,632 220,600 300,636 380,604 460,640 540,606 620,642 700,608 780,638 820,612 820,760 -20,760', K::filled($accent))],
            'zigzag' => [K::poly('-20,600 60,656 140,600 220,656 300,600 380,656 460,600 540,656 620,600 700,656 780,600 820,640 820,760 -20,760', K::filled($accent))],
            default => array_merge(
                [K::rect(-20, 636, 840, 124, 0, K::filled($accent))],
                array_map(fn($i) => K::circle(-20 + $i * 80, 636, 40, K::filled($accent)), range(0, 10))
            ),
        };

        return K::make(
            $title,
            'A frame with a shaped bottom edge. Clip a photo to the area above it, or use the band as a caption strip.',
            800,
            760,
            array_merge(
                [
                    K::rect(0, 0, 800, 760, 0, K::filled($ground)),
                    K::rect(40, 40, 720, 620, 12, K::filled($light, ['opacity' => 0.6])),
                    K::rect(40, 40, 720, 620, 12, K::outlined($ink, 3, ['opacity' => 0.5])),
                    K::path(K::blob(400, 320, 160, 8, 0.18, 11), K::filled($second, ['opacity' => 0.4])),
                ],
                $band
            ),
            [
                K::text('CAPTION GOES HERE', 400, 712, ['fill' => $ground, 'fontSize' => 30, 'fontWeight' => 'bold']),
            ],
            $ground,
            ['frame', 'edge', $edge, $palette]
        );
    }

    /** A thick gradient border around an empty well. */
    private static function gradientBorder(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A gradient border made from one filled rectangle with a hollow one on top - no masking needed.',
            760,
            760,
            [
                K::rect(30, 30, 700, 700, 36, array_merge(K::filled($accent), K::gradient($accent, $second, 135))),
                K::rect(54, 54, 652, 652, 24, K::filled($ground)),
                K::rect(54, 54, 652, 652, 24, K::outlined($light, 2, ['opacity' => 0.7])),
                K::circle(380, 380, 120, K::outlined($accent, 4, ['opacity' => 0.5, 'strokeDasharray' => '10 12'])),
            ],
            [
                K::text('DROP ARTWORK HERE', 380, 392, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold', 'opacity' => 0.45]),
            ],
            '#ffffff',
            ['frame', 'gradient', 'border', $palette]
        );
    }
}
