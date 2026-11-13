<?php

namespace Tests\Feature;

use App\Modules\App\Models\App;
use App\Modules\App\database\seeders\AppTranslationsSeeder;
use App\Modules\DesktopSoftware\Models\DesktopSoftware;
use App\Modules\DesktopSoftware\database\seeders\DesktopSoftwareTranslationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTranslationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_translations_fill_gaps_and_keep_dashboard_text(): void
    {
        $app = App::create(['slug' => 'kanaf', 'name' => 'Kanaf', 'translations' => ['ar' => ['name' => 'Edited in dashboard']]]);

        $this->seed(AppTranslationsSeeder::class);
        $this->seed(AppTranslationsSeeder::class); // running twice changes nothing

        $translations = $app->fresh()->translations;
        $this->assertSame('Edited in dashboard', $translations['ar']['name']);
        $this->assertNotEmpty($translations['ar']['summary']);
        foreach (['hi', 'zh', 'tr'] as $lang) {
            $this->assertNotEmpty($translations[$lang]['name']);
            $this->assertNotEmpty($translations[$lang]['tagline']);
        }
    }

    public function test_desktop_translations_fill_summary_and_readme(): void
    {
        $software = DesktopSoftware::create([
            'slug' => 'passwordmanager', 'name' => 'Amanah PassVault', 'summary' => 'S', 'readme' => '# R',
            'platform' => 'windows', 'environment' => '.NET 8', 'programming_language' => 'C#',
            'is_open_source' => false, 'status' => 'published', 'ordering' => 0,
        ]);

        $this->seed(DesktopSoftwareTranslationsSeeder::class);

        $translations = $software->fresh()->translations;
        foreach (['ar', 'hi', 'zh', 'tr'] as $lang) {
            $this->assertNotEmpty($translations[$lang]['summary']);
            $this->assertNotEmpty($translations[$lang]['readme']);
        }
    }
}
