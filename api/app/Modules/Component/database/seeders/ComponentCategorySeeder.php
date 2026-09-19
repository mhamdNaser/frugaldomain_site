<?php

namespace App\Modules\Component\database\seeders;

use App\Modules\Component\Models\ComponentCategory;
use Illuminate\Database\Seeder;

/**
 * The starting set of categories. More can be added from the dashboard, which
 * is why these are rows rather than an enum.
 *
 * firstOrCreate keyed on the slug, so re-running never duplicates and never
 * overwrites a name the user has since edited.
 */
class ComponentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'tables',
                'name' => 'Tables',
                'name_ar' => 'جداول',
                'description' => 'Data tables with sorting, search and pagination.',
                'accent' => '#2563eb',
                'icon' => 'table',
                'ordering' => 1,
            ],
            [
                'slug' => 'forms',
                'name' => 'Forms',
                'name_ar' => 'نماذج',
                'description' => 'Input fields, validation and multi-step flows.',
                'accent' => '#059669',
                'icon' => 'form',
                'ordering' => 2,
            ],
            [
                'slug' => 'dashboards',
                'name' => 'Dashboards',
                'name_ar' => 'لوحات',
                'description' => 'Stat cards, charts and KPI layouts.',
                'accent' => '#b45309',
                'icon' => 'chart',
                'ordering' => 3,
            ],
            [
                'slug' => 'ui-elements',
                'name' => 'UI Elements',
                'name_ar' => 'عناصر واجهة',
                'description' => 'Buttons, modals, alerts and tabs.',
                'accent' => '#7c3aed',
                'icon' => 'layout',
                'ordering' => 4,
            ],
        ];

        foreach ($categories as $category) {
            ComponentCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }

        $this->command?->info('Component categories seeded.');
    }
}
