<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Diagrams category: flows, charts, maps, matrices and wireframes.
 *
 * A diagram template is only useful if it can be rearranged, so nodes and
 * connectors are always separate elements: moving a box never drags a line
 * with it, and deleting a step leaves the rest intact.
 *
 * Nodes are placed on a 20-unit rhythm and connectors meet their edges
 * squarely, which is what stops a diagram looking hand-nudged after the first
 * edit.
 */
final class DiagramTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['linear', 'midnight', 'Flowchart - linear process'],
            ['decision', 'ocean', 'Flowchart - decision branch'],
            ['loop', 'forest', 'Flowchart - retry loop'],
            ['swimlane', 'slate', 'Flowchart - two swimlanes'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::flowchart($title, $kind, $palette);
        }

        foreach ([
            ['three', 'indigo', 'Org chart - three levels'],
            ['wide', 'slate', 'Org chart - one manager, five reports'],
            ['team', 'ocean', 'Org chart - two teams'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::orgChart($title, $kind, $palette);
        }

        foreach ([
            ['plum', 4, 'Mind map - four branches'],
            ['mint', 6, 'Mind map - six branches'],
            ['ember', 8, 'Mind map - eight branches'],
        ] as [$palette, $branches, $title]) {
            $out[] = self::mindMap($title, $branches, $palette);
        }

        foreach ([
            ['ocean', 5, 'Timeline - five milestones'],
            ['gold', 4, 'Timeline - four quarters'],
        ] as [$palette, $steps, $title]) {
            $out[] = self::timelineHorizontal($title, $steps, $palette);
        }

        foreach ([
            ['forest', 'Timeline - vertical history'],
            ['berry', 'Timeline - vertical roadmap'],
        ] as [$palette, $title]) {
            $out[] = self::timelineVertical($title, $palette);
        }

        foreach ([
            [2, 'ocean', 'Venn diagram - two circles'],
            [3, 'plum', 'Venn diagram - three circles'],
        ] as [$circles, $palette, $title]) {
            $out[] = self::venn($title, $circles, $palette);
        }

        foreach ([
            ['coral', 4, 'Funnel - four stages'],
            ['indigo', 5, 'Funnel - five stages'],
            ['forest', 3, 'Funnel - three stages'],
        ] as [$palette, $stages, $title]) {
            $out[] = self::funnel($title, $stages, $palette);
        }

        foreach ([
            ['gold', 3, 'Pyramid - three tiers'],
            ['ocean', 4, 'Pyramid - four tiers'],
            ['berry', 5, 'Pyramid - five tiers'],
        ] as [$palette, $tiers, $title]) {
            $out[] = self::pyramid($title, $tiers, $palette);
        }

        foreach ([
            ['mint', 3, 'Cycle - three steps'],
            ['indigo', 4, 'Cycle - four steps'],
            ['ember', 5, 'Cycle - five steps'],
        ] as [$palette, $steps, $title]) {
            $out[] = self::cycle($title, $steps, $palette);
        }

        foreach ([
            ['priority', 'ocean', 'Matrix - urgent and important'],
            ['swot', 'forest', 'Matrix - SWOT'],
            ['risk', 'coral', 'Matrix - risk and impact'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::matrix($title, $kind, $palette);
        }

        foreach ([
            ['hub', 'indigo', 'Network - hub and spokes'],
            ['mesh', 'slate', 'Network - mesh'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::network($title, $kind, $palette);
        }

        foreach ([
            ['desktop', 'slate', 'Wireframe - desktop page'],
            ['mobile', 'slate', 'Wireframe - mobile screen'],
            ['dashboard', 'midnight', 'Wireframe - dashboard'],
            ['landing', 'slate', 'Wireframe - landing page'],
        ] as [$kind, $palette, $title]) {
            $out[] = self::wireframe($title, $kind, $palette);
        }

        foreach ([
            ['ocean', 3, 'Kanban board - three columns'],
            ['plum', 4, 'Kanban board - four columns'],
        ] as [$palette, $columns, $title]) {
            $out[] = self::kanban($title, $columns, $palette);
        }

        foreach ([
            ['indigo', 'Gantt chart - six tasks'],
            ['forest', 'Gantt chart - sprint plan'],
        ] as [$palette, $title]) {
            $out[] = self::gantt($title, $palette);
        }

        foreach ([
            ['arrows', 'ocean', 3, 'Process steps - three arrows'],
            ['chevrons', 'indigo', 4, 'Process steps - four chevrons'],
            ['numbered', 'forest', 4, 'Process steps - numbered cards'],
            ['arrows', 'ember', 5, 'Process steps - five arrows'],
        ] as [$kind, $palette, $steps, $title]) {
            $out[] = self::processSteps($title, $kind, $steps, $palette);
        }

        foreach ([
            ['midnight', 'Architecture - three tier stack'],
            ['ocean', 'Architecture - client, api and data'],
            ['slate', 'Architecture - microservices'],
        ] as $i => [$palette, $title]) {
            $out[] = self::architecture($title, $i, $palette);
        }

        foreach ([
            ['forest', 'Tree diagram - file hierarchy'],
            ['slate', 'Tree diagram - decision tree'],
        ] as $i => [$palette, $title]) {
            $out[] = self::tree($title, $i, $palette);
        }

        foreach ([
            ['ocean', 3, 'Comparison - three columns'],
            ['berry', 2, 'Comparison - before and after'],
        ] as [$palette, $columns, $title]) {
            $out[] = self::comparison($title, $columns, $palette);
        }

        foreach ([
            ['indigo', 'Roadmap - four quarters'],
            ['mint', 'Roadmap - now, next and later'],
        ] as $i => [$palette, $title]) {
            $out[] = self::roadmap($title, $i, $palette);
        }

        return $out;
    }

    /* ================================================================== */
    /* Shared node helpers                                                 */
    /* ================================================================== */

    /** A rounded node with a label. Returns [shapes, texts]. */
    private static function node(float $x, float $y, float $w, float $h, string $label, string $fill, string $stroke, string $ink, float $size = 20): array
    {
        return [
            [
                K::rect($x, $y, $w, $h, 12, K::filled($fill)),
                K::rect($x, $y, $w, $h, 12, K::outlined($stroke, 3)),
            ],
            [K::text($label, $x + $w / 2, $y + $h / 2 + $size * 0.34, ['fill' => $ink, 'fontSize' => $size, 'fontWeight' => 'bold'])],
        ];
    }

    /** A connector with an arrowhead. */
    private static function connector(float $x1, float $y1, float $x2, float $y2, string $colour): array
    {
        return [
            K::line($x1, $y1, $x2, $y2, K::outlined($colour, 3)),
            K::poly(K::arrow($x1, $y1, $x2, $y2, 1.5, 16), K::filled($colour)),
        ];
    }

    /* ================================================================== */
    /* Builders                                                            */
    /* ================================================================== */

    private static function flowchart(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];

        $push = function (array $pair) use (&$shapes, &$texts) {
            $shapes = array_merge($shapes, $pair[0]);
            $texts = array_merge($texts, $pair[1]);
        };

        if ($kind === 'linear') {
            foreach ([['Start', 60], ['Collect input', 260], ['Validate', 460], ['Save', 660], ['Done', 860]] as $i => [$label, $x]) {
                $push(self::node($x, 180, 160, 80, $label, $i === 0 || $i === 4 ? $light : $ground, $accent, $ink));
                if ($i > 0) {
                    $shapes = array_merge($shapes, self::connector($x - 40, 220, $x - 6, 220, $ink));
                }
            }
        } elseif ($kind === 'decision') {
            $push(self::node(380, 40, 200, 76, 'Start', $light, $accent, $ink));
            $shapes[] = K::poly('480,180 620,280 480,380 340,280', K::filled($ground));
            $shapes[] = K::poly('480,180 620,280 480,380 340,280', K::outlined($accent, 3));
            $texts[] = K::text('Valid?', 480, 288, ['fill' => $ink, 'fontSize' => 22, 'fontWeight' => 'bold']);
            $push(self::node(100, 440, 200, 76, 'Show error', $ground, $second, $ink));
            $push(self::node(660, 440, 200, 76, 'Save record', $ground, $second, $ink));
            $push(self::node(380, 580, 200, 76, 'Finish', $light, $accent, $ink));
            $shapes = array_merge(
                $shapes,
                self::connector(480, 116, 480, 174, $ink),
                self::connector(340, 280, 200, 434, $ink),
                self::connector(620, 280, 760, 434, $ink),
                self::connector(200, 516, 380, 610, $ink),
                self::connector(760, 516, 580, 610, $ink)
            );
            $texts[] = K::text('no', 250, 340, ['fill' => $second, 'fontSize' => 18, 'fontWeight' => 'bold']);
            $texts[] = K::text('yes', 710, 340, ['fill' => $accent, 'fontSize' => 18, 'fontWeight' => 'bold']);
        } elseif ($kind === 'loop') {
            foreach ([['Request', 80], ['Process', 340], ['Respond', 600]] as $i => [$label, $x]) {
                $push(self::node($x, 200, 200, 80, $label, $ground, $accent, $ink));
                if ($i > 0) {
                    $shapes = array_merge($shapes, self::connector($x - 60, 240, $x - 6, 240, $ink));
                }
            }
            $shapes[] = K::path('M440 280 V400 H180 V286', K::outlined($second, 3, ['strokeDasharray' => '10 8']));
            $shapes[] = K::poly(K::arrow(180, 320, 180, 284, 1.5, 16), K::filled($second));
            $texts[] = K::text('retry on failure', 310, 428, ['fill' => $second, 'fontSize' => 18, 'fontWeight' => 'bold']);
        } else {
            $shapes[] = K::rect(40, 80, 920, 180, 14, K::filled($light, ['opacity' => 0.45]));
            $shapes[] = K::rect(40, 280, 920, 180, 14, K::filled($ground));
            $shapes[] = K::rect(40, 80, 920, 380, 14, K::outlined($ink, 2, ['opacity' => 0.4]));
            $shapes[] = K::line(40, 260, 960, 260, K::outlined($ink, 2, ['opacity' => 0.4]));
            $texts[] = K::text('CUSTOMER', 118, 116, ['fill' => $ink, 'fontSize' => 16, 'fontWeight' => 'bold', 'opacity' => 0.6]);
            $texts[] = K::text('SUPPORT', 112, 316, ['fill' => $ink, 'fontSize' => 16, 'fontWeight' => 'bold', 'opacity' => 0.6]);
            foreach ([['Opens ticket', 220, 140], ['Replies', 560, 140]] as [$label, $x, $y]) {
                $push(self::node($x, $y, 190, 74, $label, $ground, $accent, $ink, 18));
            }
            foreach ([['Triages', 400, 340], ['Resolves', 740, 340]] as [$label, $x, $y]) {
                $push(self::node($x, $y, 190, 74, $label, $light, $second, $ink, 18));
            }
            $shapes = array_merge(
                $shapes,
                self::connector(410, 177, 400, 370, $ink),
                self::connector(590, 377, 655, 214, $ink),
                self::connector(750, 177, 835, 334, $ink)
            );
        }

        return K::make(
            $title,
            'A flowchart with nodes and connectors as separate shapes - move a box and the arrows stay put until you move them.',
            1000,
            $kind === 'decision' ? 700 : ($kind === 'swimlane' ? 520 : 460),
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'flowchart', $kind, $palette]
        );
    }

    private static function orgChart(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];
        $push = function (array $pair) use (&$shapes, &$texts) {
            $shapes = array_merge($shapes, $pair[0]);
            $texts = array_merge($texts, $pair[1]);
        };

        if ($kind === 'three') {
            $push(self::node(400, 40, 220, 80, 'Chief executive', $light, $accent, $ink, 18));
            foreach ([['Design', 120], ['Engineering', 400], ['Operations', 680]] as [$label, $x]) {
                $push(self::node($x, 220, 220, 80, $label, $ground, $accent, $ink, 18));
                $shapes[] = K::line($x + 110, 180, $x + 110, 214, K::outlined($ink, 3));
            }
            $shapes[] = K::line(230, 180, 790, 180, K::outlined($ink, 3));
            $shapes[] = K::line(510, 120, 510, 180, K::outlined($ink, 3));
            foreach ([['Brand', 60], ['Product', 260], ['Platform', 460], ['Support', 660], ['Finance', 860]] as [$label, $x]) {
                $push(self::node($x, 400, 160, 70, $label, $ground, $second, $ink, 16));
            }
            foreach ([[230, 60], [230, 260], [510, 460], [510, 660], [790, 860]] as [$from, $to]) {
                $shapes[] = K::path('M' . $from . ' 300 V350 H' . ($to + 80) . ' V394', K::outlined($ink, 2.5, ['opacity' => 0.7]));
            }
        } elseif ($kind === 'wide') {
            $push(self::node(400, 40, 220, 84, 'Head of design', $light, $accent, $ink, 18));
            $shapes[] = K::line(510, 124, 510, 180, K::outlined($ink, 3));
            $shapes[] = K::line(130, 180, 890, 180, K::outlined($ink, 3));
            foreach (['Brand', 'Product', 'Research', 'Content', 'Motion'] as $i => $label) {
                $x = 50 + $i * 190;
                $push(self::node($x, 240, 160, 84, $label, $ground, $second, $ink, 17));
                $shapes[] = K::line($x + 80, 180, $x + 80, 234, K::outlined($ink, 2.5, ['opacity' => 0.75]));
            }
        } else {
            foreach ([['PRODUCT', 40, $accent], ['PLATFORM', 520, $second]] as [$team, $x, $colour]) {
                $shapes[] = K::rect($x, 40, 440, 420, 16, K::filled($light, ['opacity' => 0.4]));
                $shapes[] = K::rect($x, 40, 440, 420, 16, K::outlined($colour, 3));
                $texts[] = K::text($team, $x + 220, 82, ['fill' => $ink, 'fontSize' => 18, 'fontWeight' => 'bold']);
                $push(self::node($x + 130, 110, 180, 74, 'Lead', $ground, $colour, $ink, 17));
                foreach ([0, 1, 2] as $i) {
                    $push(self::node($x + 40 + ($i % 2) * 200, 250 + intdiv($i, 2) * 110, 180, 74, 'Member ' . ($i + 1), $ground, $colour, $ink, 16));
                    $shapes[] = K::line($x + 220, 184, $x + 130 + ($i % 2) * 200, 244, K::outlined($ink, 2, ['opacity' => 0.6]));
                }
            }
        }

        return K::make(
            $title,
            'An org chart with elbow connectors. Every box is a separate rectangle, so a reorganisation is a drag, not a redraw.',
            1020,
            $kind === 'three' ? 520 : ($kind === 'team' ? 500 : 380),
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'org-chart', 'hierarchy', $palette]
        );
    }

    private static function mindMap(string $title, int $branches, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [K::circle(500, 400, 300, K::filled($light, ['opacity' => 0.25]))];
        $texts = [];

        for ($i = 0; $i < $branches; $i++) {
            $angle = ($i / $branches) * 360 - 90;
            [$x, $y] = K::onCircle(500, 400, 280, $angle);
            $colour = $i % 2 === 0 ? $accent : $second;

            $shapes[] = K::path(
                'M500 400 Q' . round(($x + 500) / 2 + 40, 2) . ' ' . round(($y + 400) / 2, 2) . ' ' . $x . ' ' . $y,
                K::outlined($colour, 4)
            );
            $shapes[] = K::rect($x - 90, $y - 30, 180, 60, 14, K::filled($ground));
            $shapes[] = K::rect($x - 90, $y - 30, 180, 60, 14, K::outlined($colour, 3));
            $texts[] = K::text('Branch ' . ($i + 1), $x, $y + 7, ['fill' => $ink, 'fontSize' => 19, 'fontWeight' => 'bold']);
        }

        $shapes[] = K::circle(500, 400, 92, array_merge(K::filled($accent), K::gradient($accent, $second, 140)));
        $shapes[] = K::circle(500, 400, 92, K::outlined($ink, 4));
        $texts[] = K::text('TOPIC', 500, 408, ['fill' => $ground, 'fontSize' => 24, 'fontWeight' => 'bold']);

        return K::make(
            $title,
            'A mind map with curved branches. Each branch is one curve and one box, so a branch can be dragged anywhere.',
            1000,
            800,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'mind-map', 'brainstorm', $palette]
        );
    }

    private static function timelineHorizontal(string $title, int $steps, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [K::line(80, 300, 920, 300, K::outlined($light, 10))];
        $texts = [];
        $gap = 840 / ($steps - 1);

        for ($i = 0; $i < $steps; $i++) {
            $x = 80 + $i * $gap;
            $above = $i % 2 === 0;
            $colour = $i % 2 === 0 ? $accent : $second;

            $shapes[] = K::line(80, 300, $x, 300, K::outlined($accent, 10));
            $shapes[] = K::circle($x, 300, 22, K::filled($ground));
            $shapes[] = K::circle($x, 300, 22, K::outlined($colour, 6));
            $shapes[] = K::line($x, $above ? 278 : 322, $x, $above ? 210 : 390, K::outlined($colour, 3));
            $shapes[] = K::rect($x - 100, $above ? 110 : 390, 200, 96, 14, K::filled($ground));
            $shapes[] = K::rect($x - 100, $above ? 110 : 390, 200, 96, 14, K::outlined($colour, 3));

            $texts[] = K::text('Milestone ' . ($i + 1), $x, ($above ? 110 : 390) + 40, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold']);
            $texts[] = K::text('Q' . ($i % 4 + 1) . ' 2026', $x, ($above ? 110 : 390) + 70, ['fill' => $colour, 'fontSize' => 17, 'fontWeight' => 'bold']);
        }

        return K::make(
            $title,
            'A horizontal timeline with alternating cards, so labels never collide however long they are.',
            1000,
            560,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'timeline', 'milestones', $palette]
        );
    }

    private static function timelineVertical(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [K::line(120, 80, 120, 740, K::outlined($light, 8))];
        $texts = [];

        $items = [
            ['Kick-off', 'Scope agreed and the team assembled', 120],
            ['Design complete', 'Every screen signed off', 280],
            ['Beta release', 'Fifty customers on the new build', 440],
            ['General availability', 'Open to everyone', 600],
        ];

        foreach ($items as $index => [$heading, $detail, $y]) {
            $done = $index < 2;
            $colour = $done ? $accent : $second;

            $shapes[] = K::line(120, 80, 120, $done ? $y + 40 : $y, K::outlined($accent, 8));
            $shapes[] = K::circle(120, $y, 26, K::filled($done ? $accent : $ground));
            $shapes[] = K::circle(120, $y, 26, K::outlined($colour, 5));
            if ($done) {
                $shapes[] = K::path(K::tick(120, $y, 24), K::outlined($ground, 5));
            }
            $shapes[] = K::rect(190, $y - 46, 560, 116, 14, K::filled($ground));
            $shapes[] = K::rect(190, $y - 46, 560, 116, 14, K::outlined($colour, 3));

            $texts[] = K::text($heading, 220, $y - 8, ['fill' => $ink, 'fontSize' => 24, 'fontWeight' => 'bold', 'alignment' => 'left']);
            $texts[] = K::text($detail, 220, $y + 28, ['fill' => $ink, 'fontSize' => 18, 'alignment' => 'left', 'opacity' => 0.7]);
            $texts[] = K::text($done ? 'DONE' : 'PLANNED', 700, $y - 8, ['fill' => $colour, 'fontSize' => 15, 'fontWeight' => 'bold', 'alignment' => 'right']);
        }

        return K::make(
            $title,
            'A vertical timeline where completed steps are filled and future ones outlined - progress is visible without a legend.',
            800,
            780,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'timeline', 'vertical', $palette]
        );
    }

    private static function venn(string $title, int $circles, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = $circles === 2
            ? [
                K::circle(400, 340, 220, K::filled($accent, ['opacity' => 0.55])),
                K::circle(600, 340, 220, K::filled($second, ['opacity' => 0.55])),
                K::circle(400, 340, 220, K::outlined($ink, 3)),
                K::circle(600, 340, 220, K::outlined($ink, 3)),
            ]
            : [
                K::circle(500, 280, 200, K::filled($accent, ['opacity' => 0.5])),
                K::circle(390, 470, 200, K::filled($second, ['opacity' => 0.5])),
                K::circle(610, 470, 200, K::filled($light, ['opacity' => 0.75])),
                K::circle(500, 280, 200, K::outlined($ink, 3)),
                K::circle(390, 470, 200, K::outlined($ink, 3)),
                K::circle(610, 470, 200, K::outlined($ink, 3)),
            ];

        $texts = $circles === 2
            ? [
                K::text('Set A', 300, 348, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold']),
                K::text('Set B', 700, 348, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold']),
                K::text('Both', 500, 348, ['fill' => $ink, 'fontSize' => 22, 'fontWeight' => 'bold']),
            ]
            : [
                K::text('One', 500, 220, ['fill' => $ink, 'fontSize' => 24, 'fontWeight' => 'bold']),
                K::text('Two', 320, 540, ['fill' => $ink, 'fontSize' => 24, 'fontWeight' => 'bold']),
                K::text('Three', 690, 540, ['fill' => $ink, 'fontSize' => 24, 'fontWeight' => 'bold']),
                K::text('All', 500, 430, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold']),
            ];

        return K::make(
            $title,
            'A Venn diagram of translucent circles - the overlaps come from opacity, so no shape has to be cut.',
            1000,
            $circles === 2 ? 680 : 760,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'venn', 'overlap', $palette]
        );
    }

    private static function funnel(string $title, int $stages, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];
        $top = 80;
        $height = 110;
        $topWidth = 760;
        $bottomWidth = 240;

        for ($i = 0; $i < $stages; $i++) {
            $wTop = $topWidth - ($topWidth - $bottomWidth) * ($i / $stages);
            $wBottom = $topWidth - ($topWidth - $bottomWidth) * (($i + 1) / $stages);
            $y = $top + $i * ($height + 12);
            $opacity = 1 - $i * (0.55 / max(1, $stages - 1));

            $shapes[] = K::poly(implode(' ', [
                (500 - $wTop / 2) . ',' . $y,
                (500 + $wTop / 2) . ',' . $y,
                (500 + $wBottom / 2) . ',' . ($y + $height),
                (500 - $wBottom / 2) . ',' . ($y + $height),
            ]), K::filled($accent, ['opacity' => round($opacity, 2)]));

            $texts[] = K::text('Stage ' . ($i + 1), 500, $y + $height / 2 + 2, ['fill' => $ground, 'fontSize' => 24, 'fontWeight' => 'bold']);
            $texts[] = K::text(number_format((int) (10000 / ($i + 1.4))), 500, $y + $height / 2 + 34, ['fill' => $ground, 'fontSize' => 18, 'opacity' => 0.85]);
            if ($i > 0) {
                $texts[] = K::text('-' . (18 + $i * 9) . '%', 840, $y + 14, ['fill' => $second, 'fontSize' => 20, 'fontWeight' => 'bold']);
            }
        }

        return K::make(
            $title,
            'A funnel of separate trapezoids, with the drop-off called out beside each step rather than left to be worked out.',
            1000,
            $top + $stages * ($height + 12) + 60,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'funnel', 'conversion', $palette]
        );
    }

    private static function pyramid(string $title, int $tiers, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];
        $apexY = 80;
        $baseY = 80 + $tiers * 110;
        $halfBase = 400;

        for ($i = 0; $i < $tiers; $i++) {
            $yTop = $apexY + ($baseY - $apexY) * ($i / $tiers);
            $yBottom = $apexY + ($baseY - $apexY) * (($i + 1) / $tiers);
            $wTop = $halfBase * ($i / $tiers);
            $wBottom = $halfBase * (($i + 1) / $tiers);
            $opacity = 0.45 + 0.55 * (($tiers - $i) / $tiers);

            $shapes[] = K::poly(implode(' ', [
                (500 - $wTop) . ',' . $yTop,
                (500 + $wTop) . ',' . $yTop,
                (500 + $wBottom) . ',' . ($yBottom - 8),
                (500 - $wBottom) . ',' . ($yBottom - 8),
            ]), K::filled($i % 2 === 0 ? $accent : $second, ['opacity' => round($opacity, 2)]));

            $texts[] = K::text('Level ' . ($tiers - $i), 500, ($yTop + $yBottom) / 2 + 6, ['fill' => $ground, 'fontSize' => 22, 'fontWeight' => 'bold']);
            $texts[] = K::text('Describe this tier', 940, ($yTop + $yBottom) / 2 + 6, ['fill' => $ink, 'fontSize' => 17, 'alignment' => 'right', 'opacity' => 0.65]);
        }

        return K::make(
            $title,
            'A pyramid of stacked trapezoids with a caption rail on the right - add or remove a tier without redrawing.',
            1000,
            $baseY + 60,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'pyramid', 'hierarchy', $palette]
        );
    }

    private static function cycle(string $title, int $steps, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [K::circle(450, 450, 300, K::outlined($light, 24))];
        $texts = [];

        for ($i = 0; $i < $steps; $i++) {
            $from = ($i / $steps) * 360 - 84;
            $to = (($i + 1) / $steps) * 360 - 96;
            $colour = $i % 2 === 0 ? $accent : $second;

            $shapes[] = K::path(K::arc(450, 450, 300, $from, $to), K::outlined($colour, 24));
            [$hx, $hy] = K::onCircle(450, 450, 300, $to);
            [$px, $py] = K::onCircle(450, 450, 300, $to - 6);
            $shapes[] = K::poly(K::arrow($px, $py, $hx, $hy, 2, 30), K::filled($colour));

            [$nx, $ny] = K::onCircle(450, 450, 300, ($i / $steps) * 360 - 90);
            $shapes[] = K::circle($nx, $ny, 54, K::filled($ground));
            $shapes[] = K::circle($nx, $ny, 54, K::outlined($colour, 5));
            $texts[] = K::text((string) ($i + 1), $nx, $ny + 12, ['fill' => $ink, 'fontSize' => 32, 'fontWeight' => 'bold']);

            [$lx, $ly] = K::onCircle(450, 450, 400, ($i / $steps) * 360 - 90);
            $texts[] = K::text('Step ' . ($i + 1), $lx, $ly + 6, ['fill' => $ink, 'fontSize' => 22, 'fontWeight' => 'bold']);
        }

        $texts[] = K::text('CYCLE', 450, 442, ['fill' => $ink, 'fontSize' => 30, 'fontWeight' => 'bold']);
        $texts[] = K::text('repeat forever', 450, 478, ['fill' => $ink, 'fontSize' => 18, 'opacity' => 0.6]);

        return K::make(
            $title,
            'A cycle built from arcs and arrowheads, with numbered nodes on the ring and labels outside it.',
            900,
            900,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'cycle', 'process', $palette]
        );
    }

    private static function matrix(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        [$labels, $xAxis, $yAxis] = match ($kind) {
            'swot' => [['Strengths', 'Weaknesses', 'Opportunities', 'Threats'], 'INTERNAL / EXTERNAL', 'HELPFUL / HARMFUL'],
            'risk' => [['Monitor', 'Mitigate now', 'Accept', 'Plan for'], 'IMPACT', 'LIKELIHOOD'],
            default => [['Schedule it', 'Do it now', 'Drop it', 'Delegate it'], 'URGENT', 'IMPORTANT'],
        };

        $tints = [$accent, $second, $light, $ground];
        $shapes = [];
        $texts = [];

        foreach ([[80, 80], [520, 80], [80, 460], [520, 460]] as $index => [$x, $y]) {
            $shapes[] = K::rect($x, $y, 400, 360, 16, K::filled($tints[$index], ['opacity' => $index < 2 ? 0.28 : 0.5]));
            $shapes[] = K::rect($x, $y, 400, 360, 16, K::outlined($ink, 3, ['opacity' => 0.55]));
            $texts[] = K::text($labels[$index], $x + 200, $y + 60, ['fill' => $ink, 'fontSize' => 28, 'fontWeight' => 'bold']);
            $texts[] = K::text('Add items here', $x + 200, $y + 110, ['fill' => $ink, 'fontSize' => 18, 'opacity' => 0.55]);
        }

        $shapes[] = K::line(500, 60, 500, 840, K::outlined($ink, 4));
        $shapes[] = K::line(60, 440, 940, 440, K::outlined($ink, 4));

        $texts[] = K::text($xAxis, 500, 40, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold', 'opacity' => 0.7]);
        $texts[] = K::text($yAxis, 500, 880, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold', 'opacity' => 0.7]);

        return K::make(
            $title,
            'A two-by-two matrix with its axes drawn over the quadrants. Retitle the quadrants for any framework.',
            1000,
            900,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'matrix', $kind, $palette]
        );
    }

    private static function network(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];

        if ($kind === 'hub') {
            $nodes = [];
            for ($i = 0; $i < 7; $i++) {
                [$x, $y] = K::onCircle(450, 420, 300, ($i / 7) * 360 - 90);
                $nodes[] = [$x, $y];
                $shapes[] = K::line(450, 420, $x, $y, K::outlined($light, 4));
            }
            foreach ($nodes as $i => [$x, $y]) {
                $shapes[] = K::circle($x, $y, 52, K::filled($ground));
                $shapes[] = K::circle($x, $y, 52, K::outlined($i % 2 ? $second : $accent, 4));
                $texts[] = K::text('N' . ($i + 1), $x, $y + 8, ['fill' => $ink, 'fontSize' => 22, 'fontWeight' => 'bold']);
            }
            $shapes[] = K::circle(450, 420, 82, array_merge(K::filled($accent), K::gradient($accent, $second, 140)));
            $texts[] = K::text('HUB', 450, 430, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']);
        } else {
            $nodes = [[160, 160], [450, 100], [760, 200], [200, 460], [520, 420], [800, 520], [340, 700], [660, 720]];
            $edges = [[0, 1], [1, 2], [0, 3], [1, 4], [2, 5], [3, 4], [4, 5], [3, 6], [4, 6], [5, 7], [6, 7]];
            foreach ($edges as [$a, $b]) {
                $shapes[] = K::line($nodes[$a][0], $nodes[$a][1], $nodes[$b][0], $nodes[$b][1], K::outlined($light, 3));
            }
            foreach ($nodes as $i => [$x, $y]) {
                $size = $i === 4 ? 56 : 40;
                $shapes[] = K::circle($x, $y, $size, K::filled($i === 4 ? $accent : $ground));
                $shapes[] = K::circle($x, $y, $size, K::outlined($i === 4 ? $accent : $second, 4));
                $texts[] = K::text((string) ($i + 1), $x, $y + 7, ['fill' => $i === 4 ? $ground : $ink, 'fontSize' => 20, 'fontWeight' => 'bold']);
            }
        }

        return K::make(
            $title,
            'A network graph of circles and lines. Move a node and reconnect its lines by dragging their endpoints.',
            900,
            $kind === 'hub' ? 800 : 820,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'network', $kind, $palette]
        );
    }

    private static function wireframe(string $title, string $kind, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $box = fn(float $x, float $y, float $w, float $h, float $opacity = 0.16) => K::rect($x, $y, $w, $h, 10, K::filled($ink, ['opacity' => $opacity]));
        $ruleLine = fn(float $x, float $y, float $w) => K::rect($x, $y, $w, 12, 6, K::filled($ink, ['opacity' => 0.2]));

        $shapes = match ($kind) {
            'mobile' => array_merge(
                [
                    K::rect(60, 40, 360, 780, 36, K::outlined($ink, 4)),
                    K::rect(160, 40, 160, 22, 11, K::filled($ink, ['opacity' => 0.25])),
                    $box(84, 96, 312, 44),
                    $box(84, 164, 312, 220, 0.12),
                    $ruleLine(84, 408, 220),
                    $ruleLine(84, 436, 300),
                    $ruleLine(84, 464, 260),
                ],
                array_map(fn($i) => $box(84, 520 + $i * 92, 312, 76, 0.1), range(0, 2)),
                [
                    K::rect(84, 760, 312, 48, 12, K::filled($accent, ['opacity' => 0.5])),
                ]
            ),
            'dashboard' => array_merge(
                [
                    K::rect(40, 40, 920, 620, 14, K::outlined($ink, 3)),
                    $box(40, 40, 920, 60, 0.18),
                    $box(40, 100, 200, 560, 0.1),
                ],
                array_map(fn($i) => $box(268 + $i * 232, 128, 208, 110, 0.14), range(0, 2)),
                [
                    $box(268, 268, 440, 240, 0.12),
                    $box(732, 268, 208, 240, 0.12),
                    $box(268, 536, 672, 96, 0.1),
                ],
                array_map(fn($i) => $ruleLine(66, 140 + $i * 44, 148), range(0, 5))
            ),
            'landing' => array_merge(
                [
                    K::rect(40, 40, 920, 40, 8, K::filled($ink, ['opacity' => 0.18])),
                    $box(40, 110, 920, 300, 0.12),
                    K::rect(120, 200, 340, 26, 13, K::filled($ink, ['opacity' => 0.3])),
                    K::rect(120, 244, 260, 16, 8, K::filled($ink, ['opacity' => 0.2])),
                    K::rect(120, 292, 160, 44, 10, K::filled($accent, ['opacity' => 0.55])),
                    K::rect(560, 150, 360, 220, 12, K::filled($second, ['opacity' => 0.3])),
                ],
                array_map(fn($i) => $box(40 + $i * 312, 450, 280, 170, 0.1), range(0, 2)),
                [
                    $box(40, 660, 920, 120, 0.14),
                ]
            ),
            default => array_merge(
                [
                    K::rect(40, 40, 920, 640, 14, K::outlined($ink, 3)),
                    $box(40, 40, 920, 64, 0.2),
                    K::rect(70, 60, 120, 24, 6, K::filled($accent, ['opacity' => 0.6])),
                    $box(40, 124, 920, 240, 0.1),
                    K::rect(100, 200, 300, 28, 14, K::filled($ink, ['opacity' => 0.3])),
                    K::rect(100, 248, 220, 16, 8, K::filled($ink, ['opacity' => 0.2])),
                ],
                array_map(fn($i) => $box(40 + $i * 236, 400, 200, 150, 0.12), range(0, 3)),
                [$box(40, 580, 920, 100, 0.16)]
            ),
        };

        return K::make(
            $title,
            'A grey-box wireframe. Everything is a plain rectangle at a low opacity, which is exactly what a wireframe should be.',
            $kind === 'mobile' ? 480 : 1000,
            $kind === 'mobile' ? 860 : ($kind === 'landing' ? 820 : 700),
            $shapes,
            [
                K::text('WIREFRAME', $kind === 'mobile' ? 240 : 500, $kind === 'mobile' ? 850 : ($kind === 'landing' ? 810 : 690), ['fill' => $ink, 'fontSize' => 16, 'fontWeight' => 'bold', 'opacity' => 0.4]),
            ],
            '#ffffff',
            ['diagram', 'wireframe', $kind, $palette]
        );
    }

    private static function kanban(string $title, int $columns, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $names = ['Backlog', 'In progress', 'Review', 'Done'];
        $width = 60 + $columns * 280;
        $shapes = [];
        $texts = [];

        for ($c = 0; $c < $columns; $c++) {
            $x = 40 + $c * 280;
            $shapes[] = K::rect($x, 40, 250, 700, 16, K::filled($light, ['opacity' => 0.4]));
            $shapes[] = K::rect($x, 40, 250, 56, 16, K::filled($c === $columns - 1 ? $accent : $second, ['opacity' => 0.85]));
            $texts[] = K::text($names[$c], $x + 125, 76, ['fill' => $ground, 'fontSize' => 20, 'fontWeight' => 'bold']);

            $cards = 4 - $c;
            for ($i = 0; $i < max(1, $cards); $i++) {
                $y = 124 + $i * 130;
                $shapes[] = K::rect($x + 16, $y, 218, 110, 12, array_merge(K::filled($ground), K::shadow(4, 10, $ink, 0.14)));
                $shapes[] = K::rect($x + 32, $y + 16, 60, 8, 4, K::filled($c === 0 ? $second : $accent));
                $shapes[] = K::rect($x + 32, $y + 44, 180, 10, 5, K::filled($ink, ['opacity' => 0.25]));
                $shapes[] = K::rect($x + 32, $y + 64, 130, 10, 5, K::filled($ink, ['opacity' => 0.18]));
                $shapes[] = K::circle($x + 208, $y + 86, 12, K::filled($accent, ['opacity' => 0.5]));
            }
        }

        return K::make(
            $title,
            'A kanban board with real cards in it. Drag a card between columns - nothing is grouped or locked.',
            $width,
            780,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'kanban', 'board', $palette]
        );
    }

    private static function gantt(string $title, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $tasks = [
            ['Discovery', 0, 3], ['Design', 2, 4], ['Build', 5, 6],
            ['Test', 9, 2], ['Docs', 8, 3], ['Launch', 11, 1],
        ];
        $unit = 66;
        $shapes = [K::rect(240, 60, 12 * $unit, 60, 0, K::filled($light, ['opacity' => 0.5]))];
        $texts = [];

        for ($w = 0; $w < 12; $w++) {
            $x = 240 + $w * $unit;
            $shapes[] = K::line($x, 60, $x, 120 + count($tasks) * 62, K::outlined($ink, 1.5, ['opacity' => 0.16]));
            $texts[] = K::text('W' . ($w + 1), $x + $unit / 2, 98, ['fill' => $ink, 'fontSize' => 16, 'fontWeight' => 'bold', 'opacity' => 0.6]);
        }

        foreach ($tasks as $index => [$name, $start, $length]) {
            $y = 140 + $index * 62;
            $shapes[] = K::rect(240, $y - 12, 12 * $unit, 44, 0, K::filled($ink, ['opacity' => $index % 2 ? 0.04 : 0]));
            $shapes[] = K::rect(240 + $start * $unit + 6, $y, $length * $unit - 12, 28, 14, K::filled($index % 2 ? $second : $accent));
            $texts[] = K::text($name, 220, $y + 21, ['fill' => $ink, 'fontSize' => 19, 'fontWeight' => 'bold', 'alignment' => 'right']);
            $texts[] = K::text($length . 'w', 240 + $start * $unit + $length * $unit / 2, $y + 21, ['fill' => $ground, 'fontSize' => 15, 'fontWeight' => 'bold']);
        }

        $shapes[] = K::line(240 + 7 * $unit, 60, 240 + 7 * $unit, 140 + count($tasks) * 62 - 30, K::outlined($accent, 3, ['strokeDasharray' => '10 8']));
        $texts[] = K::text('today', 240 + 7 * $unit, 46, ['fill' => $accent, 'fontSize' => 16, 'fontWeight' => 'bold']);

        return K::make(
            $title,
            'A Gantt chart on a weekly grid, with a today line. Each bar is one rounded rectangle you can drag or stretch.',
            1060,
            140 + count($tasks) * 62 + 40,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'gantt', 'schedule', $palette]
        );
    }

    private static function processSteps(string $title, string $kind, int $steps, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];
        $width = 120 + $steps * 220;

        for ($i = 0; $i < $steps; $i++) {
            $x = 60 + $i * 220;
            $colour = $i % 2 === 0 ? $accent : $second;

            if ($kind === 'chevrons') {
                $shapes[] = K::poly(implode(' ', [
                    $x . ',120', ($x + 160) . ',120', ($x + 200) . ',200',
                    ($x + 160) . ',280', $x . ',280', ($x + 40) . ',200',
                ]), K::filled($colour, ['opacity' => 1 - $i * 0.12]));
                $texts[] = K::text('Step ' . ($i + 1), $x + 110, 208, ['fill' => $ground, 'fontSize' => 22, 'fontWeight' => 'bold']);
            } elseif ($kind === 'numbered') {
                $shapes[] = K::rect($x, 110, 180, 200, 16, K::filled($ground));
                $shapes[] = K::rect($x, 110, 180, 200, 16, K::outlined($colour, 4));
                $shapes[] = K::circle($x + 90, 160, 34, K::filled($colour));
                $texts[] = K::text((string) ($i + 1), $x + 90, 170, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']);
                $texts[] = K::text('Step title', $x + 90, 232, ['fill' => $ink, 'fontSize' => 20, 'fontWeight' => 'bold']);
                $texts[] = K::text('One short line', $x + 90, 264, ['fill' => $ink, 'fontSize' => 16, 'opacity' => 0.6]);
                if ($i < $steps - 1) {
                    $shapes[] = K::poly(K::arrow($x + 188, 210, $x + 212, 210, 2, 16), K::filled($ink, ['opacity' => 0.4]));
                }
            } else {
                $shapes[] = K::poly(implode(' ', [
                    $x . ',130', ($x + 150) . ',130', ($x + 190) . ',200',
                    ($x + 150) . ',270', $x . ',270',
                ]), K::filled($colour));
                $texts[] = K::text('Step ' . ($i + 1), $x + 88, 208, ['fill' => $ground, 'fontSize' => 22, 'fontWeight' => 'bold']);
            }
        }

        return K::make(
            $title,
            'A process strip. Each step is one shape and one label, so the run can be lengthened by copying a pair.',
            $width,
            $kind === 'numbered' ? 400 : 380,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'process', $kind, $palette]
        );
    }

    private static function architecture(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];

        if ($variant === 0) {
            $layers = [['PRESENTATION', 'Web · mobile · CLI'], ['APPLICATION', 'Controllers · services · jobs'], ['DATA', 'Database · cache · object store']];
            foreach ($layers as $i => [$name, $detail]) {
                $y = 80 + $i * 180;
                $shapes[] = K::rect(80, $y, 840, 140, 18, K::filled($i === 1 ? $accent : $second, ['opacity' => 0.85 - $i * 0.12]));
                $shapes[] = K::rect(80, $y, 840, 140, 18, K::outlined($ink, 3));
                $texts[] = K::text($name, 500, $y + 60, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']);
                $texts[] = K::text($detail, 500, $y + 98, ['fill' => $ground, 'fontSize' => 18, 'opacity' => 0.85]);
                if ($i < 2) {
                    $shapes = array_merge($shapes, self::connector(500, $y + 140, 500, $y + 174, $ink));
                }
            }
        } elseif ($variant === 1) {
            $boxes = [['Client', 60, $second], ['API', 400, $accent], ['Database', 740, $second]];
            foreach ($boxes as $i => [$name, $x, $colour]) {
                $shapes[] = K::rect($x, 180, 220, 160, 18, K::filled($colour, ['opacity' => 0.9]));
                $shapes[] = K::rect($x, 180, 220, 160, 18, K::outlined($ink, 3));
                $texts[] = K::text($name, $x + 110, 268, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']);
                if ($i > 0) {
                    $shapes = array_merge($shapes, self::connector($x - 110, 240, $x - 8, 240, $ink));
                    $shapes = array_merge($shapes, self::connector($x + 8, 300, $x - 110, 300, $ink));
                }
            }
            $texts[] = K::text('request', 340, 226, ['fill' => $ink, 'fontSize' => 16, 'opacity' => 0.65]);
            $texts[] = K::text('response', 340, 330, ['fill' => $ink, 'fontSize' => 16, 'opacity' => 0.65]);
            $shapes[] = K::rect(400, 400, 220, 90, 14, K::filled($light, ['opacity' => 0.7]));
            $shapes[] = K::rect(400, 400, 220, 90, 14, K::outlined($ink, 2, ['strokeDasharray' => '8 8']));
            $texts[] = K::text('Cache', 510, 452, ['fill' => $ink, 'fontSize' => 22, 'fontWeight' => 'bold']);
            $shapes[] = K::line(510, 340, 510, 396, K::outlined($ink, 2, ['opacity' => 0.5, 'strokeDasharray' => '6 8']));
        } else {
            $shapes[] = K::rect(60, 60, 880, 120, 16, K::filled($accent, ['opacity' => 0.9]));
            $texts[] = K::text('API GATEWAY', 500, 130, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']);
            foreach (['Accounts', 'Billing', 'Drawings', 'Components'] as $i => $name) {
                $x = 60 + $i * 228;
                $shapes[] = K::rect($x, 280, 200, 130, 14, K::filled($second, ['opacity' => 0.85]));
                $shapes[] = K::rect($x, 280, 200, 130, 14, K::outlined($ink, 3));
                $texts[] = K::text($name, $x + 100, 352, ['fill' => $ground, 'fontSize' => 20, 'fontWeight' => 'bold']);
                $shapes[] = K::line($x + 100, 180, $x + 100, 274, K::outlined($ink, 2.5, ['opacity' => 0.6]));
                $shapes[] = K::rect($x + 30, 470, 140, 90, 12, K::filled($light, ['opacity' => 0.8]));
                $shapes[] = K::rect($x + 30, 470, 140, 90, 12, K::outlined($ink, 2));
                $texts[] = K::text('db', $x + 100, 522, ['fill' => $ink, 'fontSize' => 18, 'fontWeight' => 'bold']);
                $shapes[] = K::line($x + 100, 410, $x + 100, 464, K::outlined($ink, 2, ['opacity' => 0.5, 'strokeDasharray' => '6 8']));
            }
        }

        return K::make(
            $title,
            'A system diagram with every box and every connector separate, so a service can be added without redrawing the rest.',
            1000,
            $variant === 0 ? 660 : ($variant === 1 ? 560 : 640),
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'architecture', 'system', $palette]
        );
    }

    private static function tree(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $shapes = [];
        $texts = [];

        if ($variant === 0) {
            $rows = [
                ['project', 0, 0], ['src', 1, 1], ['components', 2, 2], ['utils', 2, 3],
                ['public', 1, 4], ['images', 2, 5], ['README.md', 1, 6],
            ];
            foreach ($rows as [$name, $depth, $index]) {
                $y = 80 + $index * 84;
                $x = 80 + $depth * 90;
                $shapes[] = K::rect($x, $y, 300, 56, 10, K::filled($depth === 0 ? $accent : $ground, ['opacity' => $depth === 0 ? 0.9 : 1]));
                $shapes[] = K::rect($x, $y, 300, 56, 10, K::outlined($depth === 0 ? $accent : $second, 3));
                $texts[] = K::text($name, $x + 24, $y + 36, ['fill' => $depth === 0 ? $ground : $ink, 'fontSize' => 20, 'fontWeight' => 'bold', 'alignment' => 'left']);
                if ($depth > 0) {
                    $shapes[] = K::path('M' . ($x - 46) . ' ' . ($y - 28) . ' V' . ($y + 28) . ' H' . $x, K::outlined($ink, 2.5, ['opacity' => 0.5]));
                }
            }
        } else {
            $shapes[] = K::rect(340, 40, 280, 80, 14, K::filled($accent));
            $texts[] = K::text('Is it broken?', 480, 88, ['fill' => $ground, 'fontSize' => 22, 'fontWeight' => 'bold']);
            foreach ([['Yes', 120, $second], ['No', 640, $light]] as $i => [$label, $x, $colour]) {
                $shapes[] = K::rect($x, 240, 280, 80, 14, K::filled($colour, ['opacity' => 0.9]));
                $shapes[] = K::rect($x, 240, 280, 80, 14, K::outlined($ink, 3));
                $texts[] = K::text($i === 0 ? 'Did you change it?' : 'Leave it alone', $x + 140, 288, ['fill' => $i === 0 ? $ground : $ink, 'fontSize' => 20, 'fontWeight' => 'bold']);
                $shapes = array_merge($shapes, self::connector(480, 122, $x + 140, 234, $ink));
                $texts[] = K::text($label, ($x + 140 + 480) / 2, 190, ['fill' => $ink, 'fontSize' => 17, 'fontWeight' => 'bold']);
            }
            foreach ([['Revert it', 40], ['Ask who did', 380]] as $i => [$label, $x]) {
                $shapes[] = K::rect($x, 440, 240, 76, 14, K::filled($ground));
                $shapes[] = K::rect($x, 440, 240, 76, 14, K::outlined($second, 3));
                $texts[] = K::text($label, $x + 120, 486, ['fill' => $ink, 'fontSize' => 19, 'fontWeight' => 'bold']);
                $shapes = array_merge($shapes, self::connector(260, 322, $x + 120, 434, $ink));
            }
        }

        return K::make(
            $title,
            'A tree drawn with elbow connectors. Each branch is its own path, so a whole limb can be dragged away.',
            $variant === 0 ? 700 : 960,
            $variant === 0 ? 700 : 580,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'tree', 'hierarchy', $palette]
        );
    }

    private static function comparison(string $title, int $columns, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $names = $columns === 2 ? ['Before', 'After'] : ['Option A', 'Option B', 'Option C'];
        $width = 80 + $columns * 300;
        $shapes = [];
        $texts = [];

        for ($c = 0; $c < $columns; $c++) {
            $x = 40 + $c * 300;
            $highlight = $c === $columns - 1;
            $shapes[] = K::rect($x, 40, 280, 620, 18, K::filled($highlight ? $accent : $ground, ['opacity' => $highlight ? 0.14 : 1]));
            $shapes[] = K::rect($x, 40, 280, 620, 18, K::outlined($highlight ? $accent : $second, $highlight ? 4 : 3));
            $shapes[] = K::rect($x, 40, 280, 80, 18, K::filled($highlight ? $accent : $second, ['opacity' => 0.9]));
            $texts[] = K::text($names[$c], $x + 140, 92, ['fill' => $ground, 'fontSize' => 24, 'fontWeight' => 'bold']);

            for ($r = 0; $r < 5; $r++) {
                $y = 170 + $r * 90;
                $good = $highlight || $r % 2 === 0;
                $shapes[] = K::circle($x + 46, $y, 18, K::filled($good ? $accent : $light, ['opacity' => $good ? 0.9 : 1]));
                if ($good) {
                    $shapes[] = K::path(K::tick($x + 46, $y, 18), K::outlined($ground, 3.5));
                } else {
                    $shapes[] = K::path('M' . ($x + 39) . ' ' . ($y - 7) . ' L' . ($x + 53) . ' ' . ($y + 7)
                        . ' M' . ($x + 53) . ' ' . ($y - 7) . ' L' . ($x + 39) . ' ' . ($y + 7), K::outlined($ink, 3, ['opacity' => 0.5]));
                }
                $shapes[] = K::rect($x + 80, $y - 8, 160, 14, 7, K::filled($ink, ['opacity' => 0.18]));
            }
        }

        return K::make(
            $title,
            'A comparison board with the recommended column ringed. Ticks and crosses are drawn, not typed.',
            $width,
            700,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'comparison', 'options', $palette]
        );
    }

    private static function roadmap(string $title, int $variant, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $columns = $variant === 0 ? ['Q1', 'Q2', 'Q3', 'Q4'] : ['NOW', 'NEXT', 'LATER'];
        $count = count($columns);
        $width = 60 + $count * 300;
        $shapes = [];
        $texts = [];

        foreach ($columns as $c => $name) {
            $x = 40 + $c * 300;
            $colour = $c === 0 ? $accent : $second;
            $shapes[] = K::rect($x, 40, 280, 60, 14, K::filled($colour, ['opacity' => 1 - $c * 0.16]));
            $texts[] = K::text($name, $x + 140, 80, ['fill' => $ground, 'fontSize' => 24, 'fontWeight' => 'bold']);

            $items = $count - $c;
            for ($i = 0; $i < max(2, $items); $i++) {
                $y = 130 + $i * 120;
                $shapes[] = K::rect($x, $y, 280, 100, 14, K::filled($light, ['opacity' => 0.35]));
                $shapes[] = K::rect($x, $y, 280, 100, 14, K::outlined($colour, 2.5));
                $shapes[] = K::rect($x + 20, $y + 20, 72, 10, 5, K::filled($colour));
                $shapes[] = K::rect($x + 20, $y + 46, 200, 12, 6, K::filled($ink, ['opacity' => 0.22]));
                $shapes[] = K::rect($x + 20, $y + 68, 140, 12, 6, K::filled($ink, ['opacity' => 0.14]));
            }
        }

        return K::make(
            $title,
            'A roadmap board with a column per horizon. Cards are plain rectangles, so they move between columns freely.',
            $width,
            620,
            $shapes,
            $texts,
            '#ffffff',
            ['diagram', 'roadmap', 'planning', $palette]
        );
    }
}
