<?php

namespace App\Modules\App\database\seeders;

use App\Modules\App\Models\App;
use App\Modules\App\Models\AppFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Imports the legacy static catalogue (sydev-front/public/json/apps.json) into
 * the new tables. That file stays on disk as the reference copy; this seeder
 * is the one-time bridge from it to the database.
 *
 * Idempotent: re-running it updates the existing rows rather than duplicating.
 */
class AppCatalogueSeeder extends Seeder
{
    /**
     * Where the catalogue is looked for, relative to base_path() (= the api/
     * directory).
     *
     * The deployed layout matters here: the repository mirrors public_html, so
     * on the server the API lives at public_html/api and the built front end -
     * including json/apps.json - sits one level up at public_html/json. The
     * sydev-front sources are gitignored and are NOT deployed, so a path that
     * only looked there found nothing and the seeder imported zero rows.
     */
    protected const CANDIDATES = [
        // Deployed layout: public_html/api -> public_html/json/apps.json
        '/../json/apps.json',
        // Local development, where the sources are present
        '/../sydev-front/public/json/apps.json',
        '/../../sydev-front/public/json/apps.json',
        // Last resort: a copy shipped inside the module itself
        '/app/Modules/App/database/seeders/data/apps.json',
        '/database/seeders/data/apps.json',
    ];

    public function run(): void
    {
        $payload = $this->readCatalogue();

        if ($payload === null) {
            $this->command?->warn('AppCatalogueSeeder: apps.json not found — nothing imported.');

            return;
        }

        $apps = is_array($payload['apps'] ?? null) ? $payload['apps'] : [];

        foreach ($apps as $index => $entry) {
            if (empty($entry['slug'])) {
                continue;
            }

            DB::transaction(function () use ($entry, $index) {
                $preview = is_array($entry['preview'] ?? null) ? $entry['preview'] : [];

                $app = App::withTrashed()->updateOrCreate(
                    ['slug' => $entry['slug']],
                    [
                        'name' => $entry['name'] ?? $entry['slug'],
                        'tagline' => $entry['tagline'] ?? null,
                        'summary' => $entry['summary'] ?? null,
                        'type' => $entry['type'] ?? 'react',
                        'status' => $entry['status'] ?? 'draft',
                        'version' => $entry['version'] ?? '1.0.0',
                        'updated_on' => $entry['updated'] ?? null,
                        'accent' => $entry['accent'] ?? '#38bdf8',
                        'tags' => $entry['tags'] ?? [],
                        'stack' => $entry['stack'] ?? [],
                        'docs' => $entry['docs'] ?? [],
                        'repository' => $entry['repository'] ?: null,
                        'preview_mode' => $preview['mode'] ?? 'subfolder',
                        'preview_url' => $preview['url'] ?? null,
                        'preview_embed' => (bool) ($preview['embed'] ?? true),
                        'preview_open_in_new_tab' => (bool) ($preview['openInNewTab'] ?? true),
                        'cover' => $entry['cover'] ?: null,
                        'ordering' => $index,
                        'deleted_at' => null,
                    ]
                );

                // Highlights become feature rows, in the order they were listed.
                AppFeature::where('app_id', $app->id)->delete();

                foreach (($entry['highlights'] ?? []) as $position => $label) {
                    $label = trim((string) $label);
                    if ($label === '') {
                        continue;
                    }

                    AppFeature::create([
                        'app_id' => $app->id,
                        'label' => $label,
                        'ordering' => $position,
                    ]);
                }
            });
        }

        $this->command?->info('AppCatalogueSeeder: imported ' . count($apps) . ' app(s).');
    }

    /** @return array<string,mixed>|null */
    protected function readCatalogue(): ?array
    {
        foreach (self::CANDIDATES as $candidate) {
            $path = base_path() . $candidate;

            if (! is_file($path)) {
                continue;
            }

            // A BOM or a truncated copy would make json_decode return null.
            // Keep walking the candidates instead of giving up on the first
            // file that merely exists, so one bad copy cannot mask a good one.
            $raw = (string) file_get_contents($path);
            $decoded = json_decode(preg_replace('/^ï»¿/', '', $raw), true);

            if (is_array($decoded) && isset($decoded['apps'])) {
                $this->command?->info('AppCatalogueSeeder: reading ' . $path);

                return $decoded;
            }

            $this->command?->warn('AppCatalogueSeeder: ignoring unreadable catalogue at ' . $path);
        }

        return null;
    }
}
