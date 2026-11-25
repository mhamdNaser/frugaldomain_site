<?php

use Illuminate\Support\Facades\Cache;

it('serves a changed translation file at once, not after the cache expires', function () {
    config(['cache.default' => 'array']);
    $path = resource_path('lang/ar/admin.php');
    $original = file_get_contents($path);
    $mtime = filemtime($path);

    try {
        $this->getJson('/api/locale/ar')->assertOk()->assertJsonMissingPath('Locale cache probe');

        file_put_contents($path, str_replace("\n);", "\n  'Locale cache probe' => 'ok',\n);", $original));
        touch($path, $mtime + 5);
        clearstatcache();

        $this->getJson('/api/locale/ar')->assertOk()->assertJsonPath('Locale cache probe', 'ok');
    } finally {
        file_put_contents($path, $original);
        touch($path, $mtime);
        clearstatcache();
    }
});
