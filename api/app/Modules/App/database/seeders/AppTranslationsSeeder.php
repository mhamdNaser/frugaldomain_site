<?php

namespace App\Modules\App\database\seeders;

use App\Modules\App\Models\App;
use Illuminate\Database\Seeder;

/**
 * Fills the `translations` column of the existing apps from
 * data/translations.json: { "<slug>": { "ar": {...}, "hi": {...}, "zh": {...}, "tr": {...} } }.
 *
 * Safe to run again: a text already entered in the dashboard is kept, the file
 * only fills what is still empty. An entry whose slug is not in the database
 * is skipped.
 */
class AppTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $seed = json_decode(file_get_contents(__DIR__.'/data/translations.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach ($seed as $slug => $translations) {
            $record = App::where('slug', $slug)->first();
            if (! $record) {
                $this->command?->warn("Skipped {$slug}: not in the database.");
                continue;
            }

            $record->translations = array_replace_recursive($translations, $record->translations ?? []);
            $record->save();
            $this->command?->info("Translated {$slug}.");
        }
    }
}
