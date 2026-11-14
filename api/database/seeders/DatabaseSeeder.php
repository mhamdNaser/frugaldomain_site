<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\App\database\seeders\AppLogoSeeder;
use App\Modules\Component\database\seeders\ComponentCategorySeeder;
use App\Modules\Component\database\seeders\ComponentLibrarySeeder;
use App\Modules\Drawing\database\seeders\DrawingTemplateLibrarySeeder;
use App\Modules\Drawing\database\seeders\DrawingTemplateSeeder;
use App\Modules\Icon\database\seeders\IconSeeder;
use App\Modules\Icon\database\seeders\IconV2Seeder;
use App\Modules\Learning\database\seeders\LearningCurriculumSeeder;
use App\Modules\Learning\database\seeders\LearningTranslationsSeeder;
use App\Modules\Locale\database\seeders\LanguageSeeder;
use App\Modules\User\database\seeders\AdminSeeder;
use App\Modules\User\database\seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminSeeder::class,
            LanguageSeeder::class,
            IconSeeder::class,
            IconV2Seeder::class,

            // Catalogue content. All four are idempotent on their slugs, so
            // running this after a deploy updates the library in place rather
            // than duplicating it.
            ComponentCategorySeeder::class,
            ComponentLibrarySeeder::class,
            DrawingTemplateSeeder::class,
            DrawingTemplateLibrarySeeder::class,

            // The learning curriculum. Fill-only: it never overwrites a
            // lesson that has been edited in the dashboard.
            LearningCurriculumSeeder::class,
            // Extra lesson languages (hi, zh, tr) from their files; must follow the curriculum.
            LearningTranslationsSeeder::class,

            // App logos. Fill-only: sets `logo` where it is empty, nothing else.
            AppLogoSeeder::class,
        ]);
    }
}
