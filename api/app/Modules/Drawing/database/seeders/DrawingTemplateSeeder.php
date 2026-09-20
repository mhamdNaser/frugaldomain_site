<?php

namespace App\Modules\Drawing\database\seeders;

use App\Modules\Drawing\Models\DrawingTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The starter templates shipped with the editor.
 *
 * Every template is built from the same element shapes the editor itself
 * produces - `rect`, `circle`, `ellipse`, `polygon`, `path`, `text` - with
 * ordinary `fill`/`stroke` attributes on each one. Nothing here is a
 * flattened image or a raw SVG blob, because the requirement is that a
 * template arrives on the canvas fully editable: selectable, resizable,
 * recolourable, clippable and open to every effect and boolean operation.
 *
 * Re-running the seeder updates the existing rows rather than duplicating
 * them, so it is safe to run after editing a definition.
 */
class DrawingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $index => $template) {
            $document = [
                'paths' => $template['paths'],
                'textItems' => $template['textItems'] ?? [],
                'width' => $template['width'],
                'height' => $template['height'],
                'background' => $template['background'] ?? '#ffffff',
            ];

            DrawingTemplate::updateOrCreate(
                ['slug' => Str::slug($template['title'])],
                [
                    'title' => $template['title'],
                    'description' => $template['description'] ?? null,
                    'category' => $template['category'],
                    'document' => $document,
                    'width' => $template['width'],
                    'height' => $template['height'],
                    'sort_order' => $index,
                    'is_active' => true,
                    'tags' => $template['tags'] ?? null,
                ]
            );
        }
    }

    /** Points of a regular polygon, as the editor's own `points` string. */
    private function polygon(int $sides, float $cx, float $cy, float $r, float $rotation = -M_PI / 2): string
    {
        $points = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = (2 * M_PI * $i) / $sides + $rotation;
            $points[] = round($cx + $r * cos($angle), 2) . ',' . round($cy + $r * sin($angle), 2);
        }

        return implode(' ', $points);
    }

    /** Points of a star, matching the editor's star tool. */
    private function star(int $spikes, float $cx, float $cy, float $outer, float $innerRatio): string
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

    /** A style block with the defaults every editor element carries. */
    private function style(array $overrides = []): array
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

    private function text(string $content, float $x, float $y, array $overrides = []): array
    {
        return array_merge([
            // Derived from the content and position rather than random: a
            // random id would make every re-run rewrite every document, so
            // re-seeding after a deploy would never settle.
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

    private function templates(): array
    {
        return [
            /* ---- Logos ------------------------------------------------ */
            [
                'title' => 'Hexagon badge',
                'description' => 'A hexagon with an inner outline - a starting point for a mark or a logo.',
                'category' => 'Logos',
                'width' => 400,
                'height' => 400,
                'tags' => ['logo', 'badge', 'hexagon'],
                'paths' => [
                    [
                        'type' => 'polygon',
                        'points' => $this->polygon(6, 200, 200, 150),
                        ...$this->style(['fill' => '#0ea5e9', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                    [
                        'type' => 'polygon',
                        'points' => $this->polygon(6, 200, 200, 118),
                        ...$this->style(['stroke' => '#ffffff', 'strokeWidth' => 4]),
                    ],
                ],
                'textItems' => [
                    $this->text('LOGO', 200, 215, [
                        'fill' => '#ffffff',
                        'fontSize' => 44,
                        'fontWeight' => 'bold',
                    ]),
                ],
            ],
            [
                'title' => 'Circle monogram',
                'description' => 'A ringed circle with a letter in the middle.',
                'category' => 'Logos',
                'width' => 400,
                'height' => 400,
                'tags' => ['logo', 'monogram', 'circle'],
                'paths' => [
                    [
                        'type' => 'circle',
                        'cx' => 200, 'cy' => 200, 'r' => 150,
                        ...$this->style(['fill' => '#111827', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                    [
                        'type' => 'circle',
                        'cx' => 200, 'cy' => 200, 'r' => 128,
                        ...$this->style(['stroke' => '#fbbf24', 'strokeWidth' => 3]),
                    ],
                ],
                'textItems' => [
                    $this->text('A', 200, 232, [
                        'fill' => '#fbbf24',
                        'fontSize' => 96,
                        'fontWeight' => 'bold',
                    ]),
                ],
            ],
            [
                'title' => 'Shield mark',
                'description' => 'A shield outline built from a path, ready to be node-edited.',
                'category' => 'Logos',
                'width' => 400,
                'height' => 400,
                'tags' => ['logo', 'shield', 'security'],
                'paths' => [
                    [
                        'type' => 'path',
                        'd' => 'M200 60L330 110V220C330 290 270 330 200 355C130 330 70 290 70 220V110Z',
                        ...$this->style(['fill' => '#22c55e', 'stroke' => '#14532d', 'strokeWidth' => 4]),
                    ],
                    [
                        'type' => 'path',
                        'd' => 'M140 205L185 250L265 165',
                        ...$this->style(['stroke' => '#ffffff', 'strokeWidth' => 16]),
                    ],
                ],
            ],

            /* ---- Social ----------------------------------------------- */
            [
                'title' => 'Social post square',
                'description' => 'A 1080 square with a heading, a rule and a footer line.',
                'category' => 'Social',
                'width' => 1080,
                'height' => 1080,
                'background' => '#0f172a',
                'tags' => ['instagram', 'post', 'square'],
                'paths' => [
                    [
                        'type' => 'rect',
                        'x' => 80, 'y' => 80, 'width' => 920, 'height' => 920, 'rx' => 32,
                        ...$this->style(['stroke' => '#38bdf8', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 140, 'y' => 560, 'width' => 220, 'height' => 8, 'rx' => 4,
                        ...$this->style(['fill' => '#38bdf8', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                ],
                'textItems' => [
                    $this->text('YOUR HEADLINE', 540, 520, [
                        'fill' => '#ffffff', 'fontSize' => 84, 'fontWeight' => 'bold',
                    ]),
                    $this->text('Supporting line of copy goes here', 540, 640, [
                        'fill' => '#94a3b8', 'fontSize' => 34,
                    ]),
                    $this->text('@yourhandle', 540, 940, [
                        'fill' => '#38bdf8', 'fontSize' => 30, 'fontWeight' => 'bold',
                    ]),
                ],
            ],
            [
                'title' => 'Story frame',
                'description' => 'A 9:16 story layout with a top bar and a caption area.',
                'category' => 'Social',
                'width' => 1080,
                'height' => 1920,
                'background' => '#111827',
                'tags' => ['story', 'vertical', 'reel'],
                'paths' => [
                    [
                        'type' => 'rect',
                        'x' => 60, 'y' => 60, 'width' => 960, 'height' => 12, 'rx' => 6,
                        ...$this->style(['fill' => '#f97316', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 60, 'y' => 1280, 'width' => 960, 'height' => 560, 'rx' => 28,
                        ...$this->style(['fill' => '#1f2937', 'stroke' => '#374151', 'strokeWidth' => 2]),
                    ],
                ],
                'textItems' => [
                    $this->text('SWIPE UP', 540, 1420, [
                        'fill' => '#f97316', 'fontSize' => 64, 'fontWeight' => 'bold',
                    ]),
                    $this->text('Add your message here', 540, 1520, [
                        'fill' => '#d1d5db', 'fontSize' => 38,
                    ]),
                ],
            ],

            /* ---- Badges ------------------------------------------------ */
            [
                'title' => 'Sale starburst',
                'description' => 'A twelve-point burst for an offer or a discount.',
                'category' => 'Badges',
                'width' => 400,
                'height' => 400,
                'tags' => ['sale', 'badge', 'offer'],
                'paths' => [
                    [
                        'type' => 'polygon',
                        'points' => $this->star(12, 200, 200, 175, 0.72),
                        ...$this->style(['fill' => '#ef4444', 'stroke' => '#991b1b', 'strokeWidth' => 3]),
                    ],
                ],
                'textItems' => [
                    $this->text('50%', 200, 195, [
                        'fill' => '#ffffff', 'fontSize' => 68, 'fontWeight' => 'bold',
                    ]),
                    $this->text('OFF', 200, 245, [
                        'fill' => '#fecaca', 'fontSize' => 34, 'fontWeight' => 'bold',
                    ]),
                ],
            ],
            [
                'title' => 'Ribbon banner',
                'description' => 'A banner with folded ends, drawn as three editable polygons.',
                'category' => 'Badges',
                'width' => 500,
                'height' => 200,
                'tags' => ['ribbon', 'banner', 'label'],
                'paths' => [
                    [
                        'type' => 'polygon',
                        'points' => '20,140 60,80 100,140 100,180 20,180',
                        ...$this->style(['fill' => '#7c3aed', 'stroke' => 'none', 'strokeWidth' => 0, 'opacity' => 0.7]),
                    ],
                    [
                        'type' => 'polygon',
                        'points' => '400,140 440,80 480,140 480,180 400,180',
                        ...$this->style(['fill' => '#7c3aed', 'stroke' => 'none', 'strokeWidth' => 0, 'opacity' => 0.7]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 60, 'y' => 60, 'width' => 380, 'height' => 90, 'rx' => 8,
                        ...$this->style(['fill' => '#8b5cf6', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                ],
                'textItems' => [
                    $this->text('NEW', 250, 120, [
                        'fill' => '#ffffff', 'fontSize' => 46, 'fontWeight' => 'bold',
                    ]),
                ],
            ],

            /* ---- Frames ------------------------------------------------ */
            [
                'title' => 'Photo frame - circle',
                'description' => 'A circle sized to the canvas. Put a photo under it and clip to crop it round.',
                'category' => 'Frames',
                'width' => 600,
                'height' => 600,
                'tags' => ['frame', 'crop', 'clip', 'photo'],
                'paths' => [
                    [
                        'type' => 'circle',
                        'cx' => 300, 'cy' => 300, 'r' => 260,
                        ...$this->style(['fill' => '#e2e8f0', 'stroke' => '#0f172a', 'strokeWidth' => 6]),
                    ],
                ],
            ],
            [
                'title' => 'Photo frame - squircle',
                'description' => 'A soft-cornered square for clipping a photo the way app icons are cut.',
                'category' => 'Frames',
                'width' => 600,
                'height' => 600,
                'tags' => ['frame', 'crop', 'clip', 'squircle'],
                'paths' => [
                    [
                        'type' => 'path',
                        'd' => 'M60 300C60 118 118 60 300 60C482 60 540 118 540 300C540 482 482 540 300 540C118 540 60 482 60 300Z',
                        ...$this->style(['fill' => '#e2e8f0', 'stroke' => '#0f172a', 'strokeWidth' => 6]),
                    ],
                ],
            ],
            [
                'title' => 'Corner bracket frame',
                'description' => 'Four corner marks - a light frame that does not box the artwork in.',
                'category' => 'Frames',
                'width' => 600,
                'height' => 400,
                'tags' => ['frame', 'border', 'corners'],
                'paths' => [
                    ['type' => 'path', 'd' => 'M40 110V40H110', ...$this->style(['strokeWidth' => 6])],
                    ['type' => 'path', 'd' => 'M490 40H560V110', ...$this->style(['strokeWidth' => 6])],
                    ['type' => 'path', 'd' => 'M560 290V360H490', ...$this->style(['strokeWidth' => 6])],
                    ['type' => 'path', 'd' => 'M110 360H40V290', ...$this->style(['strokeWidth' => 6])],
                ],
            ],

            /* ---- Diagrams ---------------------------------------------- */
            [
                'title' => 'Flowchart starter',
                'description' => 'Three connected nodes - a start, a decision and an end.',
                'category' => 'Diagrams',
                'width' => 800,
                'height' => 600,
                'tags' => ['flowchart', 'diagram', 'process'],
                'paths' => [
                    [
                        'type' => 'rect',
                        'x' => 300, 'y' => 40, 'width' => 200, 'height' => 80, 'rx' => 40,
                        ...$this->style(['fill' => '#dbeafe', 'stroke' => '#2563eb', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'polygon',
                        'points' => '400,220 520,300 400,380 280,300',
                        ...$this->style(['fill' => '#fef3c7', 'stroke' => '#d97706', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 300, 'y' => 480, 'width' => 200, 'height' => 80, 'rx' => 40,
                        ...$this->style(['fill' => '#dcfce7', 'stroke' => '#16a34a', 'strokeWidth' => 3]),
                    ],
                    ['type' => 'path', 'd' => 'M400 120L400 220', ...$this->style(['strokeWidth' => 3])],
                    ['type' => 'path', 'd' => 'M400 380L400 480', ...$this->style(['strokeWidth' => 3])],
                ],
                'textItems' => [
                    $this->text('Start', 400, 90, ['fontSize' => 28, 'fill' => '#1e40af']),
                    $this->text('Decision', 400, 312, ['fontSize' => 26, 'fill' => '#92400e']),
                    $this->text('End', 400, 530, ['fontSize' => 28, 'fill' => '#15803d']),
                ],
            ],
            [
                'title' => 'Three-column layout',
                'description' => 'A page grid with a header and three columns, for laying a design out.',
                'category' => 'Diagrams',
                'width' => 900,
                'height' => 600,
                'tags' => ['layout', 'grid', 'wireframe'],
                'paths' => [
                    [
                        'type' => 'rect',
                        'x' => 40, 'y' => 40, 'width' => 820, 'height' => 100, 'rx' => 8,
                        ...$this->style(['fill' => '#f1f5f9', 'stroke' => '#94a3b8', 'strokeWidth' => 2]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 40, 'y' => 180, 'width' => 250, 'height' => 380, 'rx' => 8,
                        ...$this->style(['fill' => '#f8fafc', 'stroke' => '#cbd5e1', 'strokeWidth' => 2]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 325, 'y' => 180, 'width' => 250, 'height' => 380, 'rx' => 8,
                        ...$this->style(['fill' => '#f8fafc', 'stroke' => '#cbd5e1', 'strokeWidth' => 2]),
                    ],
                    [
                        'type' => 'rect',
                        'x' => 610, 'y' => 180, 'width' => 250, 'height' => 380, 'rx' => 8,
                        ...$this->style(['fill' => '#f8fafc', 'stroke' => '#cbd5e1', 'strokeWidth' => 2]),
                    ],
                ],
            ],

            /* ---- Basics ------------------------------------------------ */
            [
                'title' => 'Arrow set',
                'description' => 'Four arrows as editable polygons - drag a point to reshape any of them.',
                'category' => 'Basics',
                'width' => 600,
                'height' => 200,
                'tags' => ['arrow', 'direction', 'basic'],
                'paths' => [
                    [
                        'type' => 'polygon',
                        'points' => '40,80 120,80 120,50 180,100 120,150 120,120 40,120',
                        ...$this->style(['fill' => '#0ea5e9', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                    [
                        'type' => 'polygon',
                        'points' => '260,100 320,50 320,80 400,80 400,120 320,120 320,150',
                        ...$this->style(['fill' => '#f97316', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                    [
                        'type' => 'path',
                        'd' => 'M450 120C480 60 520 60 550 120',
                        ...$this->style(['stroke' => '#8b5cf6', 'strokeWidth' => 6]),
                    ],
                    [
                        'type' => 'polygon',
                        'points' => '540,105 560,128 532,132',
                        ...$this->style(['fill' => '#8b5cf6', 'stroke' => 'none', 'strokeWidth' => 0]),
                    ],
                ],
            ],
            [
                'title' => 'Speech bubbles',
                'description' => 'Two bubbles with tails, drawn as paths you can edit point by point.',
                'category' => 'Basics',
                'width' => 600,
                'height' => 400,
                'tags' => ['speech', 'bubble', 'chat'],
                'paths' => [
                    [
                        'type' => 'path',
                        'd' => 'M60 60H300A20 20 0 0 1 320 80V180A20 20 0 0 1 300 200H140L100 250L105 200H60A20 20 0 0 1 40 180V80A20 20 0 0 1 60 60Z',
                        ...$this->style(['fill' => '#e0f2fe', 'stroke' => '#0284c7', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'path',
                        'd' => 'M380 180H540A20 20 0 0 1 560 200V300A20 20 0 0 1 540 320H500L495 370L455 320H380A20 20 0 0 1 360 300V200A20 20 0 0 1 380 180Z',
                        ...$this->style(['fill' => '#fef9c3', 'stroke' => '#ca8a04', 'strokeWidth' => 3]),
                    ],
                ],
            ],
            [
                'title' => 'Shape sampler',
                'description' => 'One of each basic shape, styled and ready to pull apart.',
                'category' => 'Basics',
                'width' => 700,
                'height' => 250,
                'tags' => ['shapes', 'basic', 'starter'],
                'paths' => [
                    [
                        'type' => 'rect', 'x' => 40, 'y' => 70, 'width' => 110, 'height' => 110, 'rx' => 16,
                        ...$this->style(['fill' => '#fca5a5', 'stroke' => '#b91c1c', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'circle', 'cx' => 250, 'cy' => 125, 'r' => 55,
                        ...$this->style(['fill' => '#93c5fd', 'stroke' => '#1d4ed8', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'polygon', 'points' => $this->polygon(3, 390, 130, 60),
                        ...$this->style(['fill' => '#86efac', 'stroke' => '#15803d', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'polygon', 'points' => $this->star(5, 530, 125, 60, 0.4),
                        ...$this->style(['fill' => '#fde047', 'stroke' => '#a16207', 'strokeWidth' => 3]),
                    ],
                    [
                        'type' => 'polygon', 'points' => $this->polygon(6, 650, 125, 55),
                        ...$this->style(['fill' => '#d8b4fe', 'stroke' => '#7e22ce', 'strokeWidth' => 3]),
                    ],
                ],
            ],
        ];
    }
}
