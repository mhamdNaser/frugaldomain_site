<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LocaleController extends Controller
{
    public function setlocale($lang)
    {
        App::setLocale($lang);

        // Bump this suffix whenever resources/lang/* changes: the payload is
        // cached for a day, so an edit to a translation file is otherwise
        // invisible until the entry expires.
        $cacheKey = 'translations_all_' . $lang . '_v4';

        $payload = Cache::remember($cacheKey, 86400, function () use ($lang) {
            $adminPath = resource_path("lang/{$lang}/admin.php");
            $sitePath = resource_path("lang/{$lang}/site.php");

            $admin = file_exists($adminPath) ? require $adminPath : [];
            $site = file_exists($sitePath) ? require $sitePath : [];

            // Backward-compatible flat object + structured namespaces
            return array_merge($site, $admin, [
                'site' => $site,
                'admin' => $admin,
                '__meta' => [
                    'language' => $lang,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        });

        // Access-Control-Allow-Origin differs per requesting origin, and is
        // absent when a request carries none (a direct visit, a crawler). The
        // Hostinger CDN cached this response as `public` without regard to
        // Origin, so every visitor got whichever copy it stored first - often
        // one with no CORS header, and the site's translations were blocked.
        // `private` keeps it out of shared caches; Vary covers the browser's.
        return response()->json($payload)->withHeaders([
            'Cache-Control' => 'private, max-age=3600',
            'Vary' => 'Origin',
            'X-Cache-Status' => Cache::has($cacheKey) ? 'HIT' : 'MISS',
        ]);
    }
}
