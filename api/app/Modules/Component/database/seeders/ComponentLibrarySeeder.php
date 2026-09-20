<?php

namespace App\Modules\Component\database\seeders;

use App\Modules\Component\database\seeders\Library\ComponentKit;
use App\Modules\Component\database\seeders\Library\DashboardLibrary;
use App\Modules\Component\database\seeders\Library\FormLibrary;
use App\Modules\Component\database\seeders\Library\TableLibrary;
use App\Modules\Component\database\seeders\Library\UiElementLibrary;
use App\Modules\Component\Models\Component;
use App\Modules\Component\Models\ComponentCategory;
use App\Modules\Component\Models\ComponentFeature;
use App\Modules\Component\Support\ComponentStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * The shipped component catalogue: fifty-plus ready-made templates in each of
 * the four categories.
 *
 * Each definition is rendered by {@see ComponentKit} into the same
 * single-file HTML the dashboard's upload form produces, written to
 * public/components/<slug>/template.html and cached on the row - so a seeded
 * component is indistinguishable from one an admin uploaded by hand, and the
 * preview frame, the code tab and the download button all work with no
 * special casing.
 *
 * Idempotent on the slug: re-running updates the existing rows and rewrites
 * the existing files rather than duplicating anything, which is what makes it
 * safe to run after every deploy.
 */
class ComponentLibrarySeeder extends Seeder
{
    use ComponentStorage;

    /** Category slug => the class holding that category's definitions. */
    private const LIBRARIES = [
        'tables' => TableLibrary::class,
        'forms' => FormLibrary::class,
        'dashboards' => DashboardLibrary::class,
        'ui-elements' => UiElementLibrary::class,
    ];

    public function run(): void
    {
        // The pivot needs the categories to exist, and this seeder is often
        // run on its own rather than through DatabaseSeeder.
        $this->call(ComponentCategorySeeder::class);

        $categories = ComponentCategory::pluck('id', 'slug');
        $written = 0;

        foreach (self::LIBRARIES as $slug => $library) {
            $definitions = $library::all();

            foreach (array_values($definitions) as $index => $definition) {
                $this->seedOne($definition, $slug, $index, $categories);
                $written++;
            }

            $this->command?->info(sprintf('  %-12s %d components', $slug, count($definitions)));
        }

        $this->command?->info("Component library seeded: {$written} components.");
    }

    /* ------------------------------------------------------------------ */

    /**
     * One component: the file on disk, the row, its features and categories.
     *
     * @param array<string,mixed> $definition
     */
    private function seedOne(array $definition, string $categorySlug, int $index, $categories): void
    {
        $slug = $definition['slug'];
        $html = ComponentKit::page($definition);

        $directory = $this->componentDirectory($slug);
        $absolute = public_path($directory);
        File::ensureDirectoryExists($absolute, 0755, true);
        File::put($absolute . '/template.html', $html);

        // A slug that was soft-deleted still holds the unique index, so the
        // row is reused rather than letting a re-run fail on a duplicate.
        $component = Component::withTrashed()->firstOrNew(['slug' => $slug]);
        if ($component->trashed()) {
            $component->restore();
        }

        $component->fill([
            'name' => $definition['name'],
            'name_ar' => $definition['name_ar'] ?? null,
            'tagline' => $definition['tagline'] ?? null,
            'summary' => $definition['summary'] ?? null,
            'file_type' => 'html',
            'status' => 'published',
            'version' => $definition['version'] ?? '1.0.0',
            'accent' => $definition['accent'] ?? '#2563eb',
            'tags' => $definition['tags'] ?? [],
            'stack' => $definition['stack'] ?? ['HTML', 'CSS'],
            'file_path' => $directory . '/template.html',
            'file_name' => $slug . '.html',
            'file_size' => strlen($html),
            'source' => $html,
            'preview_theme' => $definition['theme'] ?? 'auto',
            'preview_height' => $definition['height'] ?? 420,
            'ordering' => $index + 1,
        ])->save();

        // Rewritten wholesale: the bullets are part of the definition, and
        // matching them up one by one would only preserve ids nothing uses.
        ComponentFeature::where('component_id', $component->id)->delete();
        foreach (array_values($definition['features'] ?? []) as $order => $label) {
            ComponentFeature::create([
                'component_id' => $component->id,
                'label' => $label,
                'ordering' => $order + 1,
            ]);
        }

        // A definition may name extra categories - a stat table belongs under
        // Dashboards as much as under Tables.
        $wanted = array_unique(array_merge([$categorySlug], $definition['categories'] ?? []));
        $ids = collect($wanted)->map(fn($slug) => $categories[$slug] ?? null)->filter()->values()->all();

        $component->categories()->sync($ids);
    }
}
