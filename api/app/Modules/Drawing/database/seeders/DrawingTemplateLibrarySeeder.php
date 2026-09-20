<?php

namespace App\Modules\Drawing\database\seeders;

use App\Modules\Drawing\database\seeders\Library\BadgeTemplates;
use App\Modules\Drawing\database\seeders\Library\BasicTemplates;
use App\Modules\Drawing\database\seeders\Library\DiagramTemplates;
use App\Modules\Drawing\database\seeders\Library\FrameTemplates;
use App\Modules\Drawing\database\seeders\Library\LogoTemplates;
use App\Modules\Drawing\database\seeders\Library\SocialTemplates;
use App\Modules\Drawing\Models\DrawingTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The full starter library: fifty-plus templates in each of the six
 * categories the editor's template panel offers.
 *
 * These join the handful seeded by {@see DrawingTemplateSeeder} rather than
 * replacing them - the categories match, so both sets appear under the same
 * tabs.
 *
 * Every document is built from the editor's own element shapes, so a template
 * lands on the canvas as ordinary editable geometry: selectable, resizable,
 * recolourable, node-editable and open to every boolean operation. Nothing
 * here is a flattened image or a raw SVG blob.
 *
 * Idempotent on the slug, so it is safe to re-run after every deploy: an
 * existing template is updated in place, and because the geometry is derived
 * rather than randomised, an unchanged definition produces an identical
 * document.
 */
class DrawingTemplateLibrarySeeder extends Seeder
{
    /** Category name => the class holding that category's definitions. */
    private const LIBRARIES = [
        'Logos' => LogoTemplates::class,
        'Social' => SocialTemplates::class,
        'Badges' => BadgeTemplates::class,
        'Frames' => FrameTemplates::class,
        'Diagrams' => DiagramTemplates::class,
        'Basics' => BasicTemplates::class,
    ];

    public function run(): void
    {
        $total = 0;

        foreach (self::LIBRARIES as $category => $library) {
            $definitions = $library::all();

            foreach (array_values($definitions) as $index => $definition) {
                $this->seedOne($definition, $category, $index);
                $total++;
            }

            $this->command?->info(sprintf('  %-10s %d templates', $category, count($definitions)));
        }

        $this->command?->info("Drawing template library seeded: {$total} templates.");
    }

    /**
     * One template row.
     *
     * @param array<string,mixed> $definition
     */
    private function seedOne(array $definition, string $category, int $index): void
    {
        $document = [
            'paths' => $definition['paths'],
            'textItems' => $definition['textItems'] ?? [],
            'width' => $definition['width'],
            'height' => $definition['height'],
            'background' => $definition['background'] ?? '#ffffff',
        ];

        $slug = Str::slug($definition['title']);

        // A soft-deleted row still holds the unique slug, so it is restored
        // and reused rather than letting a re-run fail on a duplicate.
        $template = DrawingTemplate::withTrashed()->firstOrNew(['slug' => $slug]);
        if ($template->trashed()) {
            $template->restore();
        }

        $template->fill([
            'title' => $definition['title'],
            'description' => $definition['description'],
            'category' => $category,
            'document' => $document,
            'width' => (int) $definition['width'],
            'height' => (int) $definition['height'],
            // Offset past the originally seeded handful, so the first
            // templates in each panel stay the ones that were there before.
            'sort_order' => 100 + $index,
            'is_active' => true,
            'tags' => $definition['tags'] ?? [],
        ])->save();
    }
}
