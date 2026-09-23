<?php

namespace App\Modules\Component\database\seeders;

use App\Modules\Component\database\seeders\Library\DashboardLibrary;
use App\Modules\Component\database\seeders\Library\FormLibrary;
use App\Modules\Component\database\seeders\Library\TableLibrary;
use App\Modules\Component\database\seeders\Library\UiElementLibrary;
use App\Modules\Component\Models\Component;
use App\Modules\Component\Models\ComponentFeature;
use Illuminate\Database\Seeder;

/**
 * Backfills the Arabic copy (tagline_ar, summary_ar, feature label_ar) on
 * components that already exist, and touches nothing else.
 *
 * Unlike {@see ComponentLibrarySeeder} - which rewrites every seeded row,
 * its file, its status and its feature list - this one is safe on a live
 * database that admins have edited:
 *   - rows are matched by slug; slugs it does not know are left alone;
 *   - a column is only written while it is still empty, so Arabic an admin
 *     has typed in the dashboard is never overwritten;
 *   - a feature gets its Arabic label only when its English label still
 *     matches the shipped definition, so an edited bullet is never paired
 *     with a translation of the old text.
 *
 * Re-running it is a no-op once everything is filled.
 */
class ComponentArabicCopySeeder extends Seeder
{
    private const LIBRARIES = [
        TableLibrary::class,
        FormLibrary::class,
        DashboardLibrary::class,
        UiElementLibrary::class,
    ];

    public function run(): void
    {
        $components = 0;
        $features = 0;

        foreach (self::LIBRARIES as $library) {
            foreach ($library::all() as $definition) {
                $component = Component::withTrashed()->where('slug', $definition['slug'])->first();

                if (!$component) {
                    continue;
                }

                $changed = false;
                foreach (['tagline_ar', 'summary_ar'] as $column) {
                    if (blank($component->{$column}) && filled($definition[$column] ?? null)) {
                        $component->{$column} = $definition[$column];
                        $changed = true;
                    }
                }

                if ($changed) {
                    // Only the two columns change; timestamps stay as they were.
                    $component->timestamps = false;
                    $component->save();
                    $components++;
                }

                $arabic = array_combine(
                    array_values($definition['features'] ?? []),
                    array_pad(array_values($definition['features_ar'] ?? []), count($definition['features'] ?? []), null),
                );

                foreach ($arabic as $label => $labelAr) {
                    if (blank($labelAr)) {
                        continue;
                    }

                    $features += ComponentFeature::where('component_id', $component->id)
                        ->where('label', $label)
                        ->whereNull('label_ar')
                        ->update(['label_ar' => $labelAr]);
                }
            }
        }

        $this->command?->info("Arabic copy filled: {$components} components, {$features} features.");
    }
}
