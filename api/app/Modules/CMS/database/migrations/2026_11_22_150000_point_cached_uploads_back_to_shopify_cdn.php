<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard uploads used to be served from a local copy under storage/shopify-cache,
 * which returns 404 on the host (no public storage link). Point them back at the
 * Shopify CDN URL they were copied from, the same way synced files are stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('files')
            ->where('disk', 'public')
            ->where('path', 'like', 'shopify-cache/%')
            ->orderBy('id')
            ->chunkById(200, function ($files) {
                foreach ($files as $file) {
                    $meta = json_decode((string) $file->meta, true) ?: [];
                    $source = $meta['source_url'] ?? null;

                    if (!is_string($source) || !str_starts_with($source, 'http')) {
                        continue; // nothing to point at; leave the row as it was
                    }

                    unset($meta['cached_locally']);
                    DB::table('files')->where('id', $file->id)->update([
                        'disk' => 'shopify',
                        'path' => $source,
                        'url' => $source,
                        'meta' => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // The local copies are not needed and are not restored.
    }
};
