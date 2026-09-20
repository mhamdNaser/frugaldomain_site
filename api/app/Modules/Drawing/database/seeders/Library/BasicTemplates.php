<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Basics category: the raw material - arrows, bubbles, shapes, patterns,
 * dividers and layout guides.
 *
 * These are meant to be taken apart. Where the other categories ship finished
 * artwork, a basics template is a sheet of parts: six arrows, one of which you
 * keep; a pattern you scale and clip; a guide you delete before exporting.
 * That is why most of them are sets rather than single objects.
 */
final class BasicTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['straight', 'midnight', 'Arrow set - straight'],
            ['curved', 'ocean', 'Arrow set - curved'],
            ['bent', 'forest', 'Arrow set - elbow'],
            ['outline', 'slate', 'Arrow set - outline'],
            ['block', 'ember', 'Arrow set - block'],
            ['circular', 'plum', 'Arrow set - circular'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::arrows($title, $kind, $palette);
        }

        foreach ([
            ['round', 'ocean', 'Speech bubbles - rounded'],
            ['square', 'slate', 'Speech bubbles - square'],
            ['thought', 'plum', 'Speech bubbles - thought'],
            ['shout', 'coral', 'Speech bubbles - shout'],
            ['chat', 'mint', 'Speech bubbles - chat thread'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::bubbles($title, $kind, $palette);
        }

        foreach ([
            ['basic', 'indigo', 'Shape sampler - basics'],
            ['polygons', 'forest', 'Shape sampler - polygons'],
            ['stars', 'gold', 'Shape sampler - stars'],
            ['organic', 'coral', 'Shape sampler - organic'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::shapes($title, $kind, $palette);
        }

        foreach ([
            ['dots', 'slate', 'Pattern tile - dots'],
            ['grid', 'slate', 'Pattern tile - grid'],
            ['isometric', 'indigo', 'Pattern tile - isometric'],
            ['hatch', 'sand', 'Pattern tile - diagonal hatch'],
            ['waves', 'ocean', 'Pattern tile - waves'],
            ['confetti', 'berry', 'Pattern tile - confetti'],
            ['triangles', 'mint', 'Pattern tile - triangles'],
            ['rings', 'plum', 'Pattern tile - rings'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::pattern($title, $kind, $palette);
        }

        foreach ([
            ['flat', 'ember', 'Banner - flat strip'],
            ['folded', 'forest', 'Banner - folded ends'],
            ['curved', 'berry', 'Banner - curved'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::banner($title, $kind, $palette);
        }

        foreach ([
            ['rule', 'slate', 'Divider - rules'],
            ['wave', 'ocean', 'Divider - waves'],
            ['zigzag', 'coral', 'Divider - zigzag'],
            ['dots', 'midnight', 'Divider - dotted'],
            ['ornament', 'gold', 'Divider - ornamental'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::divider($title, $kind, $palette);
        }

        foreach ([
            ['gold', 'Star set - five to twelve points'],
            ['coral', 'Burst set - four bursts'],
            ['indigo', 'Sparkle set - four-point stars'],
            ['mint', 'Flower set - petal rosettes'],
        ] as $i => [$palette, $title]) {
            $out[] = self::starSet($title, $i, $palette);
        }

        foreach ([
            ['berry', 'Heart set - four weights'],
            ['coral', 'Blob set - four organic shapes'],
            ['mint', 'Leaf set - four leaves'],
            ['ocean', 'Drop set - four droplets'],
        ] as $i => [$palette, $title]) {
            $out[] = self::organicSet($title, $i, $palette);
        }

        foreach ([
            ['bracket', 'slate', 'Bracket set - square and curly'],
            ['quote', 'midnight', 'Quote marks - four styles'],
            ['underline', 'coral', 'Underline set - six highlights'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::typographic($title, $kind, $palette);
        }

        foreach ([
            ['ticks', 'forest', 'Tick and cross set'],
            ['plus', 'ocean', 'Plus and minus set'],
            ['chevrons', 'slate', 'Chevron set - four directions'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::marks($title, $kind, $palette);
        }

        foreach ([
            ['dashed', 'slate', 'Connector set - dashed lines'],
            ['elbow', 'ocean', 'Connector set - elbows'],
            ['curve', 'plum', 'Connector set - curves'],
            ['dotted', 'midnight', 'Connector set - dotted with nodes'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::connectors($title, $kind, $palette);
        }

        foreach ([
            ['pin', 'coral', 'Callout set - pins'],
            ['label', 'ocean', 'Callout set - leader lines'],
            ['number', 'indigo', 'Callout set - numbered markers'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::callouts($title, $kind, $palette);
        }

        foreach ([
            ['thirds', 'slate', 'Guide - rule of thirds'],
            ['golden', 'sand', 'Guide - golden ratio'],
            ['columns', 'midnight', 'Guide - twelve column grid'],
            ['baseline', 'slate', 'Guide - baseline rhythm'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::guide($title, $kind, $palette);
        }

        foreach ([
            ['sun', 'gold', 'Weather set - sun and cloud'],
            ['mountain', 'forest', 'Landscape set - hills and trees'],
            ['city', 'midnight', 'Landscape set - skyline'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::scenery($title, $kind, $palette);
        }

        return $out;
    }

    /* ================================================================== */
    /* Builders                                                            */
    /* ================================================================== */

    private static function arrows(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        $shapes = match ($kind) {
            'straight' => [
                K::poly(K::arrow(60, 100, 300, 100, 10, 40), K::filled($accent)),
                K::poly(K::arrow(340, 100, 580, 100, 6, 30), K::filled($second)),
                K::poly(K::arrow(60, 240, 300, 240, 16, 52), K::filled($ink)),
                K::poly(K::arrow(340, 300, 580, 200, 10, 40), K::filled($accent, ['opacity' => 0.75])),
                K::line(60, 380, 280, 380, K::outlined($ink, 8)),
                K::poly(K::arrow(250, 380, 300, 380, 2, 30), K::filled($ink)),
                K::poly(K::arrow(580, 400, 340, 400, 10, 40), K::filled($second)),
            ],
            'curved' => [
                K::path('M60 300 C160 140 320 140 420 300', K::outlined($accent, 10)),
                K::poly(K::arrow(400, 260, 424, 306, 3, 34), K::filled($accent)),
                K::path('M60 420 C200 420 220 200 360 200', K::outlined($second, 8)),
                K::poly(K::arrow(320, 202, 366, 199, 3, 28), K::filled($second)),
                K::path('M480 120 C600 200 600 320 480 400', K::outlined($ink, 8)),
                K::poly(K::arrow(510, 375, 478, 404, 3, 28), K::filled($ink)),
            ],
            'bent' => [
                K::path('M60 120 H300 V300', K::outlined($accent, 9)),
                K::poly(K::arrow(300, 266, 300, 310, 2.5, 30), K::filled($accent)),
                K::path('M360 340 V160 H560', K::outlined($second, 9)),
                K::poly(K::arrow(524, 160, 570, 160, 2.5, 30), K::filled($second)),
                K::path('M60 420 H180 V360 H320 V420 H440', K::outlined($ink, 7)),
                K::poly(K::arrow(406, 420, 450, 420, 2.5, 28), K::filled($ink)),
            ],
            'outline' => [
                K::poly(K::arrow(60, 120, 300, 120, 14, 46), K::outlined($ink, 5)),
                K::poly(K::arrow(340, 120, 580, 120, 14, 46), K::outlined($accent, 5, ['strokeDasharray' => '14 10'])),
                K::poly(K::arrow(60, 300, 300, 300, 20, 56), K::both($light, $ink, 5)),
                K::poly(K::arrow(340, 300, 580, 300, 20, 56), K::both($light, $accent, 5)),
            ],
            'block' => [
                K::poly('60,140 220,140 220,90 320,170 220,250 220,200 60,200', K::filled($accent)),
                K::poly('360,170 460,90 460,140 620,140 620,200 460,200 460,250', K::filled($second)),
                K::poly('120,300 220,300 220,380 280,380 170,470 60,380 120,380', K::filled($ink)),
                K::poly('400,470 400,390 340,390 450,300 560,390 500,390 500,470', K::filled($accent, ['opacity' => 0.7])),
            ],
            default => [
                K::path(K::arc(200, 260, 120, 140, 400), K::outlined($accent, 14)),
                K::poly(K::arrow(...array_merge(K::onCircle(200, 260, 120, 30), K::onCircle(200, 260, 120, 44), [3, 38])), K::filled($accent)),
                K::path(K::arc(480, 260, 120, 320, 580), K::outlined($second, 14)),
                K::poly(K::arrow(...array_merge(K::onCircle(480, 260, 120, 210), K::onCircle(480, 260, 120, 224), [3, 38])), K::filled($second)),
                K::circle(200, 260, 40, K::filled($light, ['opacity' => 0.6])),
                K::circle(480, 260, 40, K::filled($light, ['opacity' => 0.6])),
            ],
        };

        return K::make(
            $title,
            'A sheet of arrows. Keep the one you want and delete the rest - each is a single editable shape.',
            $kind === 'circular' ? 700 : 660,
            $kind === 'circular' ? 460 : 500,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'arrow', $kind, $palette]
        );
    }

    private static function bubbles(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = match ($kind) {
            'round' => [
                K::path(K::bubble(40, 40, 300, 160, 40, 40), K::both($light, $ink, 4)),
                K::path(K::bubble(400, 40, 240, 140, 40, 30), K::both($accent, $ink, 4)),
                K::path(K::bubble(40, 300, 260, 150, 44, 36), K::both($second, $ink, 4)),
                K::path(K::bubble(360, 300, 280, 150, 44, 36), K::outlined($ink, 4)),
            ],
            'square' => [
                K::path(K::bubble(40, 40, 300, 160, 8, 40), K::both($light, $ink, 4)),
                K::path(K::bubble(400, 40, 240, 140, 8, 30), K::both($accent, $ink, 4)),
                K::path(K::bubble(40, 300, 260, 150, 4, 36), K::outlined($ink, 4)),
                K::path(K::bubble(360, 300, 280, 150, 4, 36), K::both($second, $ink, 4)),
            ],
            'thought' => array_merge(
                [
                    K::path(K::blob(220, 150, 130, 9, 0.12, 2), K::both($light, $ink, 4)),
                    K::circle(120, 300, 26, K::both($light, $ink, 4)),
                    K::circle(80, 360, 15, K::both($light, $ink, 4)),
                    K::path(K::blob(500, 160, 110, 9, 0.14, 6), K::both($accent, $ink, 4)),
                    K::circle(580, 300, 22, K::both($accent, $ink, 4)),
                    K::circle(618, 352, 13, K::both($accent, $ink, 4)),
                ],
                []
            ),
            'shout' => [
                K::poly(K::starPoints(14, 220, 190, 170, 0.78), K::both($accent, $ink, 4)),
                K::poly('180,320 150,420 250,340', K::both($accent, $ink, 4)),
                K::poly(K::starPoints(12, 520, 200, 130, 0.76), K::both($second, $ink, 4)),
                K::poly('560,310 600,400 500,330', K::both($second, $ink, 4)),
            ],
            default => [
                K::path(K::bubble(40, 40, 320, 110, 26, 26), K::both($light, $ink, 3)),
                K::rect(300, 190, 320, 110, 26, K::filled($accent)),
                K::poly('600,290 640,340 560,300', K::filled($accent)),
                K::path(K::bubble(40, 340, 280, 100, 26, 24), K::both($light, $ink, 3)),
                K::rect(340, 470, 280, 100, 26, K::filled($accent)),
                K::poly('600,560 640,610 560,570', K::filled($accent)),
            ],
        };

        return K::make(
            $title,
            'Speech bubbles as editable paths - the tail is part of the outline, so resizing keeps it attached.',
            680,
            $kind === 'chat' ? 640 : 500,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'speech-bubble', $kind, $palette]
        );
    }

    private static function shapes(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $fill = fn(int $i) => K::filled([$accent, $second, $light, $ink][$i % 4], ['opacity' => $i % 4 === 2 ? 0.9 : 1]);

        $shapes = match ($kind) {
            'basic' => [
                K::rect(50, 60, 150, 150, 0, $fill(0)),
                K::rect(240, 60, 150, 150, 28, $fill(1)),
                K::circle(505, 135, 75, $fill(2)),
                K::ellipse(695, 135, 95, 62, $fill(3)),
                K::poly('125,420 50,290 200,290', $fill(1)),
                K::poly('315,260 390,350 315,440 240,350', $fill(0)),
                K::poly(K::starPoints(5, 505, 350, 80, 0.42), $fill(3)),
                K::path(K::squircle(695, 350, 80), $fill(2)),
            ],
            'polygons' => array_map(
                fn($i) => K::poly(
                    K::polygonPoints($i + 3, 110 + ($i % 4) * 190, 140 + intdiv($i, 4) * 220, 82),
                    K::filled([$accent, $second, $light, $ink][$i % 4])
                ),
                range(0, 7)
            ),
            'stars' => array_map(
                fn($i) => K::poly(
                    K::starPoints(4 + $i, 110 + ($i % 4) * 190, 140 + intdiv($i, 4) * 220, 84, 0.3 + $i * 0.05),
                    K::filled([$accent, $second, $light, $ink][$i % 4])
                ),
                range(0, 7)
            ),
            default => array_map(
                fn($i) => K::path(
                    K::blob(110 + ($i % 4) * 190, 140 + intdiv($i, 4) * 220, 84, 6 + $i, 0.12 + $i * 0.03, $i + 1),
                    K::filled([$accent, $second, $light, $ink][$i % 4])
                ),
                range(0, 7)
            ),
        };

        return K::make(
            $title,
            'A sheet of shapes to pull apart. Every one is a native element, so the node editor and boolean tools all work on it.',
            800,
            500,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'shapes', $kind, $palette]
        );
    }

    private static function pattern(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [K::rect(0, 0, 600, 600, 0, K::filled($ground))];

        $shapes = match ($kind) {
            'dots' => array_merge($shapes, K::dotGrid(40, 40, 520, 520, 40, 7, $accent, 0.8)),
            'grid' => array_merge(
                $shapes,
                array_map(fn($i) => K::line(40 + $i * 52, 40, 40 + $i * 52, 560, K::outlined($accent, 2, ['opacity' => 0.6])), range(0, 10)),
                array_map(fn($i) => K::line(40, 40 + $i * 52, 560, 40 + $i * 52, K::outlined($accent, 2, ['opacity' => 0.6])), range(0, 10))
            ),
            'isometric' => array_merge(
                $shapes,
                array_map(fn($i) => K::line(-200 + $i * 60, 620, 400 + $i * 60, -20, K::outlined($accent, 2, ['opacity' => 0.5])), range(0, 16)),
                array_map(fn($i) => K::line(-200 + $i * 60, -20, 400 + $i * 60, 620, K::outlined($second, 2, ['opacity' => 0.5])), range(0, 16)),
                array_map(fn($i) => K::line(0, 60 + $i * 60, 600, 60 + $i * 60, K::outlined($ink, 1.5, ['opacity' => 0.18])), range(0, 8))
            ),
            'hatch' => array_merge($shapes, K::hatch(0, 0, 600, 600, 26, $accent, 5, 0.55)),
            'waves' => array_merge(
                $shapes,
                array_map(fn($i) => K::path(K::wave(-20, 70 + $i * 70, 640, 22, 4), K::outlined($i % 2 ? $second : $accent, 6, ['opacity' => 0.75])), range(0, 7))
            ),
            'confetti' => array_merge(
                $shapes,
                array_map(function ($i) use ($accent, $second, $ink, $light) {
                    $x = 50 + (($i * 97) % 520);
                    $y = 50 + (($i * 61) % 520);
                    $colour = [$accent, $second, $ink, $light][$i % 4];

                    return $i % 3 === 0
                        ? K::circle($x, $y, 9, K::filled($colour))
                        : K::rect($x, $y, 24, 10, 5, K::filled($colour, ['rotation' => ($i * 37) % 180]));
                }, range(0, 47))
            ),
            'triangles' => array_merge(
                $shapes,
                array_map(function ($i) use ($accent, $second) {
                    $col = $i % 6;
                    $row = intdiv($i, 6);
                    $x = 20 + $col * 95;
                    $y = 20 + $row * 95;
                    $up = ($col + $row) % 2 === 0;

                    return K::poly(
                        $up ? "{$x},{$y} " . ($x + 95) . ",{$y} " . ($x + 47) . ',' . ($y + 95)
                            : "{$x}," . ($y + 95) . ' ' . ($x + 95) . ',' . ($y + 95) . ' ' . ($x + 47) . ",{$y}",
                        K::filled($up ? $accent : $second, ['opacity' => 0.85])
                    );
                }, range(0, 35))
            ),
            default => array_merge(
                $shapes,
                array_map(function ($i) use ($accent, $second) {
                    $x = 100 + ($i % 3) * 200;
                    $y = 100 + intdiv($i, 3) * 200;

                    return K::circle($x, $y, 70 - ($i % 3) * 8, K::outlined($i % 2 ? $second : $accent, 8, ['opacity' => 0.8]));
                }, range(0, 8))
            ),
        };

        return K::make(
            $title,
            'A pattern tile. Select it all, group it and scale it, or clip it to a shape for a textured fill.',
            600,
            600,
            $shapes,
            [],
            $ground,
            ['basic', 'pattern', $kind, $palette]
        );
    }

    private static function banner(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = match ($kind) {
            'flat' => [
                K::rect(40, 80, 560, 110, 10, K::filled($accent)),
                K::rect(40, 80, 560, 20, 0, K::filled($ground, ['opacity' => 0.18])),
                K::rect(70, 210, 500, 70, 8, K::filled($second, ['opacity' => 0.9])),
            ],
            'folded' => [
                K::poly('20,120 90,60 90,200 20,200', K::filled($second)),
                K::poly('620,120 550,60 550,200 620,200', K::filled($second)),
                K::rect(90, 60, 460, 140, 8, K::filled($accent)),
                K::poly('90,200 140,200 90,250', K::filled($ink, ['opacity' => 0.35])),
                K::poly('550,200 500,200 550,250', K::filled($ink, ['opacity' => 0.35])),
            ],
            default => [
                K::path('M40 120 C200 40 440 40 600 120 L600 230 C440 150 200 150 40 230 Z', K::filled($accent)),
                K::path('M40 120 C200 40 440 40 600 120', K::outlined($ground, 4, ['opacity' => 0.5])),
                K::poly('40,120 10,180 40,230', K::filled($second)),
                K::poly('600,120 630,180 600,230', K::filled($second)),
            ],
        };

        return K::make(
            $title,
            'A banner shape with room for a word. The body and the tails are separate, so either end can go.',
            660,
            $kind === 'flat' ? 340 : 320,
            $shapes,
            [
                K::text('YOUR TEXT', 320, $kind === 'flat' ? 152 : 150, ['fill' => $ground, 'fontSize' => 44, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['basic', 'banner', $kind, $palette]
        );
    }

    private static function divider(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);
        $shapes = [];

        for ($row = 0; $row < 4; $row++) {
            $y = 80 + $row * 100;
            $colour = $row % 2 === 0 ? $ink : $accent;

            $shapes = array_merge($shapes, match ($kind) {
                'rule' => [
                    K::line(60, $y, 740, $y, K::outlined($colour, 2 + $row * 2, ['opacity' => 1 - $row * 0.15])),
                ],
                'wave' => [
                    K::path(K::wave(60, $y, 680, 10 + $row * 5, 6 - $row), K::outlined($colour, 4)),
                ],
                'zigzag' => [
                    K::poly(implode(' ', array_map(
                        fn($i) => (60 + $i * 40) . ',' . ($y + ($i % 2 ? 16 : -16)),
                        range(0, 17)
                    )), K::outlined($colour, 4)),
                ],
                'dots' => array_map(
                    fn($i) => K::circle(60 + $i * (40 + $row * 6), $y, 4 + $row, K::filled($colour)),
                    range(0, (int) (680 / (40 + $row * 6)))
                ),
                default => [
                    K::line(60, $y, 320, $y, K::outlined($colour, 2)),
                    K::line(480, $y, 740, $y, K::outlined($colour, 2)),
                    K::poly(K::starPoints(4, 400, $y, 22 + $row * 3, 0.3), K::filled($colour)),
                    K::circle(350, $y, 5, K::filled($second)),
                    K::circle(450, $y, 5, K::filled($second)),
                ],
            });
        }

        return K::make(
            $title,
            'Four dividers on one sheet. Take the one that suits the page and delete the others.',
            800,
            480,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'divider', $kind, $palette]
        );
    }

    private static function starSet(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);
        $colours = [$accent, $second, $ink, $light];

        $shapes = match ($variant) {
            0 => array_map(
                fn($i) => K::poly(K::starPoints(5 + $i, 120 + ($i % 4) * 180, 150 + intdiv($i, 4) * 200, 78, 0.42), K::filled($colours[$i % 4])),
                range(0, 7)
            ),
            1 => array_map(
                fn($i) => K::poly(K::starPoints(10 + $i * 4, 120 + ($i % 4) * 180, 150 + intdiv($i, 4) * 200, 80, 0.66 + $i * 0.03), K::filled($colours[$i % 4])),
                range(0, 7)
            ),
            2 => array_map(function ($i) use ($colours) {
                $cx = 120 + ($i % 4) * 180;
                $cy = 150 + intdiv($i, 4) * 200;
                $r = 50 + $i * 5;

                return K::path(
                    'M' . $cx . ' ' . ($cy - $r) . 'Q' . $cx . ' ' . $cy . ' ' . ($cx + $r) . ' ' . $cy
                    . 'Q' . $cx . ' ' . $cy . ' ' . $cx . ' ' . ($cy + $r)
                    . 'Q' . $cx . ' ' . $cy . ' ' . ($cx - $r) . ' ' . $cy
                    . 'Q' . $cx . ' ' . $cy . ' ' . $cx . ' ' . ($cy - $r) . 'Z',
                    K::filled($colours[$i % 4])
                );
            }, range(0, 7)),
            default => array_merge(...array_map(function ($i) use ($colours) {
                $cx = 120 + ($i % 4) * 180;
                $cy = 150 + intdiv($i, 4) * 200;
                $petals = 5 + $i;
                $out = [];
                for ($p = 0; $p < $petals; $p++) {
                    $angle = ($p / $petals) * 360;
                    [$x, $y] = K::onCircle($cx, $cy, 42, $angle);
                    $out[] = K::ellipse($x, $y, 34, 18, K::filled($colours[$i % 4], ['opacity' => 0.85, 'rotation' => $angle]));
                }
                $out[] = K::circle($cx, $cy, 20, K::filled($colours[($i + 2) % 4]));

                return $out;
            }, range(0, 7))),
        };

        return K::make(
            $title,
            'Eight variations on one shape family, so the closest starting point is always on the sheet.',
            800,
            460,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'stars', 'decoration', $palette]
        );
    }

    private static function organicSet(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);
        $colours = [$accent, $second, $light, $ink];

        $shapes = match ($variant) {
            0 => array_map(function ($i) use ($colours) {
                $cx = 120 + ($i % 4) * 180;
                $cy = 150 + intdiv($i, 4) * 200;
                $style = $i % 2 === 0
                    ? K::filled($colours[$i % 4])
                    : K::outlined($colours[$i % 4], 8 + $i);

                return K::path(K::heart($cx, $cy, 130 - ($i % 3) * 14), $style);
            }, range(0, 7)),
            1 => array_map(
                fn($i) => K::path(K::blob(120 + ($i % 4) * 180, 150 + intdiv($i, 4) * 200, 76, 5 + $i, 0.1 + $i * 0.04, $i * 3 + 1), K::filled($colours[$i % 4])),
                range(0, 7)
            ),
            2 => array_map(function ($i) use ($colours) {
                $cx = 120 + ($i % 4) * 180;
                $cy = 150 + intdiv($i, 4) * 200;
                $r = 70 - ($i % 3) * 8;

                return K::path(
                    'M' . $cx . ' ' . ($cy - $r) . 'C' . ($cx + $r) . ' ' . ($cy - $r) . ' ' . ($cx + $r) . ' ' . ($cy + $r * 0.4) . ' ' . $cx . ' ' . ($cy + $r)
                    . 'C' . ($cx - $r) . ' ' . ($cy + $r * 0.4) . ' ' . ($cx - $r) . ' ' . ($cy - $r) . ' ' . $cx . ' ' . ($cy - $r) . 'Z',
                    K::filled($colours[$i % 4])
                );
            }, range(0, 7)),
            default => array_map(function ($i) use ($colours) {
                $cx = 120 + ($i % 4) * 180;
                $cy = 150 + intdiv($i, 4) * 200;
                $r = 62 - ($i % 3) * 6;

                return K::path(
                    'M' . $cx . ' ' . ($cy - $r * 1.5)
                    . 'C' . ($cx + $r * 1.1) . ' ' . ($cy - $r * 0.2) . ' ' . ($cx + $r) . ' ' . ($cy + $r) . ' ' . $cx . ' ' . ($cy + $r)
                    . 'C' . ($cx - $r) . ' ' . ($cy + $r) . ' ' . ($cx - $r * 1.1) . ' ' . ($cy - $r * 0.2) . ' ' . $cx . ' ' . ($cy - $r * 1.5) . 'Z',
                    K::filled($colours[$i % 4])
                );
            }, range(0, 7)),
        };

        return K::make(
            $title,
            'Organic shapes drawn as curves, so every one can be pulled about with the node editor.',
            800,
            460,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'organic', 'shapes', $palette]
        );
    }

    private static function typographic(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);

        if ($kind === 'bracket') {
            $shapes = [
                K::path('M140 60 H80 V300 H140', K::outlined($ink, 8)),
                K::path('M260 60 H320 V300 H260', K::outlined($ink, 8)),
                K::path('M480 60 C440 60 460 170 420 180 C460 190 440 300 480 300', K::outlined($accent, 8)),
                K::path('M600 60 C640 60 620 170 660 180 C620 190 640 300 600 300', K::outlined($accent, 8)),
                K::path('M140 360 C100 360 120 410 80 420 C120 430 100 480 140 480', K::outlined($second, 6)),
                K::path('M260 360 C300 360 280 410 320 420 C280 430 300 480 260 480', K::outlined($second, 6)),
                K::path('M420 420 H680', K::outlined($ink, 4)),
                K::path('M420 400 V440 M680 400 V440', K::outlined($ink, 4)),
            ];
        } elseif ($kind === 'quote') {
            $shapes = [
                K::path('M80 200 C80 120 130 90 180 90 L180 140 C150 140 130 160 130 200 L180 200 L180 280 L80 280 Z', K::filled($ink)),
                K::path('M220 200 C220 120 270 90 320 90 L320 140 C290 140 270 160 270 200 L320 200 L320 280 L220 280 Z', K::filled($ink)),
                K::circle(460, 160, 42, K::filled($accent)),
                K::circle(560, 160, 42, K::filled($accent)),
                K::poly('440,200 480,200 450,270', K::filled($accent)),
                K::poly('540,200 580,200 550,270', K::filled($accent)),
                K::path('M80 400 L140 340 M140 400 L200 340', K::outlined($second, 12)),
                K::path('M440 340 L440 420 M520 340 L520 420', K::outlined($second, 12)),
            ];
        } else {
            $shapes = [];
            $styles = [
                fn($y, $c) => K::rect(80, $y - 14, 420, 26, 13, K::filled($c, ['opacity' => 0.45])),
                fn($y, $c) => K::line(80, $y, 500, $y, K::outlined($c, 10)),
                fn($y, $c) => K::path(K::wave(80, $y, 420, 8, 6), K::outlined($c, 7)),
                fn($y, $c) => K::path('M80 ' . $y . ' C200 ' . ($y - 22) . ' 380 ' . ($y + 14) . ' 500 ' . ($y - 6), K::outlined($c, 9)),
                fn($y, $c) => K::poly('80,' . ($y + 10) . ' 500,' . ($y - 6) . ' 500,' . ($y + 14) . ' 80,' . ($y + 22), K::filled($c, ['opacity' => 0.55])),
                fn($y, $c) => K::path('M80 ' . $y . ' H500 M96 ' . ($y + 14) . ' H484', K::outlined($c, 5)),
            ];
            foreach ($styles as $i => $style) {
                $shapes[] = $style(90 + $i * 90, $i % 2 === 0 ? $accent : $second);
            }
        }

        return K::make(
            $title,
            'Typographic furniture - the marks that sit around words rather than inside them.',
            760,
            $kind === 'underline' ? 620 : 540,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'typographic', $kind, $palette]
        );
    }

    private static function marks(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = match ($kind) {
            'ticks' => [
                K::circle(130, 140, 74, K::filled($accent)),
                K::path(K::tick(130, 140, 74), K::outlined($ground, 14)),
                K::circle(330, 140, 74, K::outlined($accent, 8)),
                K::path(K::tick(330, 140, 66), K::outlined($accent, 12)),
                K::circle(530, 140, 74, K::filled($second)),
                K::path('M500 110 L560 170 M560 110 L500 170', K::outlined($ground, 14)),
                K::circle(730, 140, 74, K::outlined($second, 8)),
                K::path('M702 112 L758 168 M758 112 L702 168', K::outlined($second, 12)),
                K::path(K::tick(130, 340, 100), K::outlined($ink, 18)),
                K::path('M290 300 L390 400 M390 300 L290 400', K::outlined($ink, 18)),
                K::poly(K::starPoints(12, 560, 350, 74, 0.8), K::filled($accent)),
                K::path(K::tick(560, 350, 66), K::outlined($ground, 12)),
                K::path('M700 350 H800', K::outlined($ink, 18)),
            ],
            'plus' => [
                K::circle(130, 140, 70, K::filled($accent)),
                K::path('M130 96 V184 M86 140 H174', K::outlined($ground, 14)),
                K::circle(330, 140, 70, K::filled($second)),
                K::path('M286 140 H374', K::outlined($ground, 14)),
                K::circle(530, 140, 70, K::outlined($accent, 8)),
                K::path('M530 96 V184 M486 140 H574', K::outlined($accent, 12)),
                K::rect(660, 70, 140, 140, 28, K::filled($ink)),
                K::path('M730 106 V174 M696 140 H764', K::outlined($ground, 14)),
                K::poly('110,280 150,280 150,320 190,320 190,360 150,360 150,400 110,400 110,360 70,360 70,320 110,320', K::filled($accent)),
                K::poly('310,280 350,280 350,320 390,320 390,360 350,360 350,400 310,400 310,360 270,360 270,320 310,320', K::outlined($ink, 6)),
                K::rect(470, 320, 140, 40, 20, K::filled($second)),
                K::rect(660, 300, 140, 80, 12, K::both($light, $ink, 5)),
                K::path('M700 340 H760', K::outlined($ink, 8)),
            ],
            default => array_merge(...array_map(function ($i) use ($accent, $second, $ink, $light) {
                $cx = 130 + ($i % 4) * 200;
                $cy = 140 + intdiv($i, 4) * 200;
                $rotation = ($i % 4) * 90;
                // Deliberately not the palette's light step: a pale chevron on
                // a white canvas is invisible, which is not a useful variant.
                $colour = [$accent, $second, $ink, $second][$i % 4];
                $weight = 10 + intdiv($i, 4) * 6;

                return [
                    K::circle($cx, $cy, 72, K::filled($colour, ['opacity' => intdiv($i, 4) === 0 ? 0.14 : 0.08])),
                    K::path(
                        'M' . ($cx - 20) . ' ' . ($cy - 34) . 'L' . ($cx + 20) . ' ' . $cy . 'L' . ($cx - 20) . ' ' . ($cy + 34),
                        K::outlined($colour, $weight, ['rotation' => $rotation])
                    ),
                ];
            }, range(0, 7))),
        };

        return K::make(
            $title,
            'Status and direction marks in filled and outlined weights, so one sheet covers both a light and a dark layout.',
            860,
            $kind === 'chevrons' ? 460 : 480,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'marks', $kind, $palette]
        );
    }

    private static function connectors(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light] = K::palette($palette);
        $shapes = [];

        for ($i = 0; $i < 4; $i++) {
            $y = 90 + $i * 110;
            $colour = $i % 2 === 0 ? $accent : $second;

            $shapes = array_merge($shapes, match ($kind) {
                'dashed' => [
                    K::line(80, $y, 700, $y, K::outlined($colour, 3 + $i, ['strokeDasharray' => [1 => '14 10', 2 => '4 10', 3 => '20 8 4 8'][$i] ?? '10 8'])),
                    K::circle(80, $y, 9, K::filled($colour)),
                    K::circle(700, $y, 9, K::filled($colour)),
                ],
                'elbow' => [
                    K::path('M80 ' . $y . ' H' . (300 + $i * 60) . ' V' . ($y + 50) . ' H700', K::outlined($colour, 4)),
                    K::circle(80, $y, 9, K::filled($colour)),
                    K::poly(K::arrow(660, $y + 50, 704, $y + 50, 2, 22), K::filled($colour)),
                ],
                'curve' => [
                    K::path('M80 ' . $y . ' C' . (260 + $i * 40) . ' ' . ($y - 60) . ' ' . (500 - $i * 40) . ' ' . ($y + 70) . ' 700 ' . $y, K::outlined($colour, 4)),
                    K::circle(80, $y, 9, K::filled($colour)),
                    K::circle(700, $y, 9, K::filled($colour)),
                ],
                default => array_merge(
                    [K::line(80, $y, 700, $y, K::outlined($colour, 3, ['strokeDasharray' => '2 12']))],
                    array_map(fn($n) => K::circle(80 + $n * 155, $y, 12 - $i, K::both('#ffffff', $colour, 4)), range(0, 4))
                ),
            });
        }

        return K::make(
            $title,
            'Connector lines in four weights and styles, with endpoints already drawn. Drag an end to attach it.',
            780,
            520,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'connector', $kind, $palette]
        );
    }

    private static function callouts(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];

        if ($kind === 'pin') {
            foreach ([[140, 160, $accent], [380, 200, $second], [620, 150, $ink]] as $i => [$x, $y, $colour]) {
                $shapes[] = K::path('M' . $x . ' ' . ($y + 90) . 'C' . ($x - 52) . ' ' . ($y + 20) . ' ' . ($x - 56) . ' ' . ($y - 40) . ' ' . $x . ' ' . ($y - 40)
                    . 'C' . ($x + 56) . ' ' . ($y - 40) . ' ' . ($x + 52) . ' ' . ($y + 20) . ' ' . $x . ' ' . ($y + 90) . 'Z', K::filled($colour));
                $shapes[] = K::circle($x, $y + 4, 22, K::filled($ground));
                $texts[] = K::text((string) ($i + 1), $x, $y + 12, ['fill' => $colour, 'fontSize' => 24, 'fontWeight' => 'bold']);
            }
            $shapes[] = K::ellipse(380, 400, 300, 40, K::filled($light, ['opacity' => 0.4]));
        } elseif ($kind === 'label') {
            foreach ([[120, 120, 320, 90], [120, 300, 320, 330], [620, 200, 420, 210]] as $i => [$px, $py, $lx, $ly]) {
                $colour = $i % 2 === 0 ? $accent : $second;
                $shapes[] = K::circle($px, $py, 12, K::filled($colour));
                $shapes[] = K::path('M' . $px . ' ' . $py . ' L' . (($px + $lx) / 2) . ' ' . $py . ' L' . $lx . ' ' . $ly, K::outlined($colour, 3));
                $shapes[] = K::rect($lx - ($i === 2 ? 240 : 0), $ly - 26, 240, 52, 10, K::both($ground, $colour, 3));
                $texts[] = K::text('Label ' . ($i + 1), $lx + ($i === 2 ? -120 : 120), $ly + 7, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold']);
            }
        } else {
            foreach (range(0, 5) as $i) {
                $x = 120 + ($i % 3) * 240;
                $y = 140 + intdiv($i, 3) * 200;
                $colour = [$accent, $second, $ink][$i % 3];
                $shapes[] = K::circle($x, $y, 52, K::filled($colour, ['opacity' => $i < 3 ? 1 : 0.16]));
                if ($i >= 3) {
                    $shapes[] = K::circle($x, $y, 52, K::outlined($colour, 5));
                }
                $texts[] = K::text((string) ($i + 1), $x, $y + 15, ['fill' => $i < 3 ? $ground : $colour, 'fontSize' => 36, 'fontWeight' => 'bold']);
            }
        }

        return K::make(
            $title,
            'Callout markers for annotating a screenshot or a photograph - numbered, so the caption list matches.',
            760,
            $kind === 'number' ? 400 : 480,
            $shapes,
            $texts,
            '#ffffff',
            ['basic', 'callout', $kind, $palette]
        );
    }

    private static function guide(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $w = 1200;
        $h = 800;
        $shapes = [K::rect(0, 0, $w, $h, 0, K::filled($ground))];

        $shapes = match ($kind) {
            'thirds' => array_merge($shapes, [
                K::line($w / 3, 0, $w / 3, $h, K::outlined($accent, 2)),
                K::line(2 * $w / 3, 0, 2 * $w / 3, $h, K::outlined($accent, 2)),
                K::line(0, $h / 3, $w, $h / 3, K::outlined($accent, 2)),
                K::line(0, 2 * $h / 3, $w, 2 * $h / 3, K::outlined($accent, 2)),
                K::circle($w / 3, $h / 3, 9, K::filled($second)),
                K::circle(2 * $w / 3, $h / 3, 9, K::filled($second)),
                K::circle($w / 3, 2 * $h / 3, 9, K::filled($second)),
                K::circle(2 * $w / 3, 2 * $h / 3, 9, K::filled($second)),
            ]),
            'golden' => array_merge($shapes, [
                K::rect(0, 0, 742, 800, 0, K::outlined($accent, 2)),
                K::rect(742, 0, 458, 800, 0, K::outlined($accent, 2)),
                K::rect(742, 0, 458, 458, 0, K::outlined($second, 2)),
                K::rect(742, 458, 283, 342, 0, K::outlined($second, 2)),
                K::path(K::arc(742, 800, 742, 270, 360), K::outlined($ink, 3)),
                K::path(K::arc(1200, 458, 458, 90, 180), K::outlined($ink, 3)),
                K::path(K::arc(742, 458, 283, 0, 90), K::outlined($ink, 3)),
            ]),
            'columns' => array_merge(
                $shapes,
                array_map(function ($i) use ($accent) {
                    $x = 60 + $i * 90;

                    return K::rect($x, 60, 70, 680, 0, K::filled($accent, ['opacity' => 0.16]));
                }, range(0, 11)),
                [
                    K::rect(60, 60, 1080, 680, 0, K::outlined($ink, 2, ['opacity' => 0.4])),
                    K::line(60, 200, 1140, 200, K::outlined($second, 2, ['strokeDasharray' => '10 10'])),
                    K::line(60, 600, 1140, 600, K::outlined($second, 2, ['strokeDasharray' => '10 10'])),
                ]
            ),
            default => array_merge(
                $shapes,
                array_map(fn($i) => K::line(80, 80 + $i * 32, 1120, 80 + $i * 32, K::outlined($accent, 1, ['opacity' => 0.45])), range(0, 21)),
                [
                    K::line(80, 80, 1120, 80, K::outlined($ink, 2)),
                    K::line(80, 720, 1120, 720, K::outlined($ink, 2)),
                    K::rect(80, 80, 1040, 640, 0, K::outlined($ink, 2, ['opacity' => 0.4])),
                ]
            ),
        };

        return K::make(
            $title,
            'A layout guide. Compose against it, then delete the layer before exporting - it is a scaffold, not artwork.',
            $w,
            $h,
            $shapes,
            [
                K::text('GUIDE LAYER - DELETE BEFORE EXPORT', $w / 2, $h - 22, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold', 'opacity' => 0.45]),
            ],
            $ground,
            ['basic', 'guide', $kind, $palette]
        );
    }

    private static function scenery(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = match ($kind) {
            'sun' => array_merge(
                [K::rect(0, 0, 800, 500, 0, array_merge(K::filled($light), K::gradient($light, $ground, 180)))],
                array_map(function ($i) use ($accent) {
                    [$x1, $y1] = K::onCircle(240, 190, 86, $i * 30);
                    [$x2, $y2] = K::onCircle(240, 190, 122, $i * 30);

                    return K::line($x1, $y1, $x2, $y2, K::outlined($accent, 7));
                }, range(0, 11)),
                [
                    K::circle(240, 190, 70, K::filled($accent)),
                    K::path('M470 300 A56 56 0 0 1 526 244 A72 72 0 0 1 664 244 A50 50 0 0 1 664 344 H510 A44 44 0 0 1 470 300 Z', K::filled($ground)),
                    K::path('M470 300 A56 56 0 0 1 526 244 A72 72 0 0 1 664 244 A50 50 0 0 1 664 344 H510 A44 44 0 0 1 470 300 Z', K::outlined($ink, 4)),
                    K::path('M300 400 A44 44 0 0 1 344 356 A56 56 0 0 1 452 356 A40 40 0 0 1 452 434 H332 A34 34 0 0 1 300 400 Z', K::filled($ground, ['opacity' => 0.9])),
                ]
            ),
            'mountain' => [
                K::rect(0, 0, 900, 520, 0, array_merge(K::filled($light), K::gradient($light, $ground, 180))),
                K::circle(700, 130, 58, K::filled($accent, ['opacity' => 0.8])),
                K::poly('0,420 200,180 360,420', K::filled($second)),
                K::poly('160,180 200,180 240,420 120,420', K::filled($second, ['opacity' => 0.6])),
                K::poly('220,420 430,150 640,420', K::filled($ink)),
                K::poly('380,215 430,150 480,215 430,250', K::filled($ground, ['opacity' => 0.85])),
                K::poly('520,420 700,240 880,420', K::filled($second, ['opacity' => 0.85])),
                K::rect(0, 420, 900, 100, 0, K::filled($ink, ['opacity' => 0.9])),
                K::poly('120,420 150,330 180,420', K::filled($ink)),
                K::poly('760,420 790,340 820,420', K::filled($ink)),
                K::path(K::wave(0, 470, 900, 8, 6), K::outlined($ground, 3, ['opacity' => 0.4])),
            ],
            default => array_merge(
                [K::rect(0, 0, 900, 520, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 180)))],
                array_map(function ($i) use ($light, $ground) {
                    $heights = [180, 260, 140, 320, 220, 380, 200, 300, 160];
                    $x = 40 + $i * 96;
                    $h = $heights[$i];

                    return K::rect($x, 460 - $h, 76, $h, 6, K::filled($light, ['opacity' => 0.9 - $i * 0.04]));
                }, range(0, 8)),
                array_merge(...array_map(function ($i) use ($ground) {
                    $out = [];
                    for ($row = 0; $row < 3; $row++) {
                        for ($col = 0; $col < 2; $col++) {
                            $out[] = K::rect(56 + $i * 96 + $col * 30, 340 + $row * 40, 18, 24, 3, K::filled($ground, ['opacity' => ($i + $row + $col) % 3 === 0 ? 0.85 : 0.25]));
                        }
                    }

                    return $out;
                }, range(0, 8))),
                [
                    K::rect(0, 460, 900, 60, 0, K::filled($ground, ['opacity' => 0.14])),
                    K::circle(760, 110, 46, K::filled($ground, ['opacity' => 0.9])),
                    K::circle(742, 96, 40, K::filled($ink, ['opacity' => 0.95])),
                ]
            ),
        };

        return K::make(
            $title,
            'A simple scene built from primitives - a starting point for an illustration rather than a finished one.',
            $kind === 'sun' ? 800 : 900,
            520,
            $shapes,
            [],
            '#ffffff',
            ['basic', 'illustration', $kind, $palette]
        );
    }
}
