<?php

namespace App\Modules\Locale\database\seeders;

use App\Modules\Locale\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/** Adds the site's other languages to the dashboard's language menu. Safe to re-run. */
class DashboardLanguagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['slug' => 'en', 'name' => 'English', 'direction' => 'LTR'],
            ['slug' => 'ar', 'name' => 'Arabic', 'direction' => 'RTL'],
            ['slug' => 'hi', 'name' => 'Hindi', 'direction' => 'LTR'],
            ['slug' => 'zh', 'name' => 'Chinese', 'direction' => 'LTR'],
            ['slug' => 'tr', 'name' => 'Turkish', 'direction' => 'LTR'],
        ] as $language) {
            $existing = Language::where('slug', $language['slug'])->first();
            if ($existing) {
                $existing->update(['status' => 1]);
            } else {
                Language::create($language + ['default' => 0, 'status' => 1]);
            }
        }

        // LanguageRepository caches the menu for a month.
        Cache::forget('languages.all');
        Cache::forget('languages.active');
    }
}
