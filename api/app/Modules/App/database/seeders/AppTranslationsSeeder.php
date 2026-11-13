<?php

namespace App\Modules\App\database\seeders;

use App\Modules\App\Models\App;
use Illuminate\Database\Seeder;

/**
 * Fills the `translations` column of the existing apps from
 * data/translations.json: { "<slug>": { "ar": {...}, "hi": {...}, "zh": {...}, "tr": {...} } }.
 *
 * The docs were written in Arabic into the English columns. The file's "en"
 * entry holds their English; a section still in Arabic is moved to
 * translations.ar.docs and replaced by that English, so the English page reads
 * English and the Arabic text is kept, not lost. A section already in English
 * (edited since) is left alone.
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

            $english = $translations['en']['docs'] ?? [];
            unset($translations['en']);

            $current = $record->translations ?? [];
            $docs = $record->docs ?? [];
            foreach ($docs as $i => $doc) {
                $id = $doc['id'] ?? null;
                if ($id === null || ! isset($english[$id]) || ! self::isArabic($doc['title'] ?? '')) {
                    continue;
                }
                $current['ar']['docs'][$id] ??= array_intersect_key($doc, array_flip(['title', 'body', 'steps']));
                $docs[$i] = array_merge($doc, array_intersect_key($english[$id], array_flip(['title', 'body', 'steps'])));
            }

            $record->docs = $docs;
            $record->translations = array_replace_recursive($translations, $current);
            $record->save();
            $this->command?->info("Translated {$slug}.");
        }
    }

    private static function isArabic(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }
}
