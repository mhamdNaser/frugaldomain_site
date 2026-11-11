<?php

namespace App\Modules\App\database\seeders;

use App\Modules\App\Models\App;
use Illuminate\Database\Seeder;

/**
 * Gives the four catalogue projects their logos.
 *
 * Touches only the `logo` column, and only where it is still empty, so it
 * never overwrites any other field or a logo set later. Safe to re-run.
 */
class AppLogoSeeder extends Seeder
{
    protected const LOGOS = [
        // Kanaf (كنف = shelter): a sheltering arc over a heart.
        'kanaf' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#d97706"/><stop offset="1" stop-color="#92400e"/></linearGradient></defs><rect width="64" height="64" rx="16" fill="url(#g)"/><path d="M13 40C13 24 22 15 32 15S51 24 51 40" fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round"/><path d="M32 48c-7-5-11-8.5-11-12.8 0-2.9 2.2-5.2 5-5.2 2.4 0 4.2 1.3 6 3.6 1.8-2.3 3.6-3.6 6-3.6 2.8 0 5 2.3 5 5.2 0 4.3-4 7.8-11 12.8z" fill="#fff"/></svg>
SVG,
        // Saydalati (pharmacy): a pharmacy cross carrying a capsule.
        'saydalati' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#10b981"/><stop offset="1" stop-color="#047857"/></linearGradient></defs><rect width="64" height="64" rx="16" fill="url(#g)"/><path d="M27 14h10a2 2 0 0 1 2 2v9h9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-9v9a2 2 0 0 1-2 2H27a2 2 0 0 1-2-2v-9h-9a2 2 0 0 1-2-2V27a2 2 0 0 1 2-2h9v-9a2 2 0 0 1 2-2z" fill="#fff"/><g transform="rotate(-45 32 32)"><rect x="24" y="28.5" width="16" height="7" rx="3.5" fill="#059669"/><path d="M32 28.5h4.5a3.5 3.5 0 0 1 0 7H32z" fill="#34d399"/></g></svg>
SVG,
        // frugal (accounting & POS): rising bars with a growth arrow.
        'frugal-pos' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><rect width="64" height="64" rx="16" fill="url(#g)"/><rect x="15" y="40" width="8" height="10" rx="2" fill="#fff" opacity=".7"/><rect x="28" y="34" width="8" height="16" rx="2" fill="#fff" opacity=".85"/><rect x="41" y="28" width="8" height="22" rx="2" fill="#fff"/><path d="M15 28l12-8 8 3 12-10M41 13h6v6" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
SVG,
        // Manex (integrated platform): stacked layers.
        'manex' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#4338ca"/></linearGradient></defs><rect width="64" height="64" rx="16" fill="url(#g)"/><path d="M32 13l19 10-19 10-19-10z" fill="#fff"/><path d="M13 32l19 10 19-10" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity=".8"/><path d="M13 41l19 10 19-10" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/></svg>
SVG,
    ];

    public function run(): void
    {
        $updated = 0;

        foreach (self::LOGOS as $slug => $svg) {
            $updated += App::withTrashed()
                ->where('slug', $slug)
                ->where(fn($q) => $q->whereNull('logo')->orWhere('logo', ''))
                ->update(['logo' => 'data:image/svg+xml;base64,' . base64_encode($svg)]);
        }

        $this->command?->info("AppLogoSeeder: set {$updated} logo(s).");
    }
}
