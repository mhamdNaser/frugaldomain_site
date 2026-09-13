<?php

namespace App\Http\Controllers;

use App\Models\IconFiles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IconCssController extends Controller
{
    /** How long a generated stylesheet stays cached. */
    private const CACHE_TTL = 3600;

    public function generate()
    {
        // The artwork lives in the API application's public directory
        // (public_html/api/public/icons); this app only serves the stylesheet.
        $iconsRoot = rtrim(config('icons.public_path'), '/\\');

        // A single query instead of one lookup per icon, and the stamp changes
        // whenever any icon row is added, edited or soft deleted.
        $files = IconFiles::query()
            ->join('icons', 'icons.id', '=', 'icon_files.icon_id')
            ->whereIn('icon_files.file_type', ['svg', 'image/svg+xml', 'SVG'])
            ->whereNull('icons.deleted_at')
            ->where('icons.is_active', true)
            ->orderBy('icons.title')
            ->get([
                'icons.title as title',
                'icon_files.file_path as file_path',
                'icon_files.updated_at as file_updated_at',
                'icons.updated_at as icon_updated_at',
            ]);

        $stamp = md5($files->count() . '|' . $files->max('icon_updated_at') . '|' . $files->max('file_updated_at'));
        $cacheKey = 'icons_css:' . $stamp;

        $css = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($files, $iconsRoot) {
            return $this->buildCss($files, $iconsRoot);
        });

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => '"' . $stamp . '"',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection  $files
     */
    private function buildCss($files, string $iconsRoot): string
    {
        $css = "/* Fruga icon stylesheet - generated automatically. */\n"
            . ".sydev{display:inline-block;vertical-align:middle;width:1em;height:1em;"
            . "background-repeat:no-repeat;background-position:center;background-size:contain}\n";

        foreach ($files as $file) {
            if (!$file->file_path) {
                continue;
            }

            $fullPath = $iconsRoot . '/' . ltrim($file->file_path, '/\\');
            if (!is_file($fullPath)) {
                continue;
            }

            // Titles are human readable ("Academic Cap"), so they must be
            // slugged before they can be used as a CSS class name.
            $class = Str::slug($file->title);
            if ($class === '') {
                continue;
            }

            $dataUri = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($fullPath));

            $css .= ".sydev-{$class}{background-image:url('{$dataUri}')}\n";
        }

        return $css;
    }
}
