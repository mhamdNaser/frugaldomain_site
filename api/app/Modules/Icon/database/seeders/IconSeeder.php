<?php

namespace App\Modules\Icon\database\seeders;

use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconCategories;
use App\Modules\Icon\Models\IconFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Seeds the whole icon library (324 icons across 16 categories).
 *
 * The SVG/PNG artwork itself lives in public/icons and is deployed with the
 * repository; this seeder only records the metadata rows that the gallery,
 * the download endpoints and the CDN stylesheet read.
 *
 * Safe to run repeatedly: every row is matched on its natural key
 * (category slug / icon title) and updated in place.
 */
class IconSeeder extends Seeder
{
    /** PNG artwork is rasterised at this size. */
    private const PNG_DIMENSIONS = '512x512';

    public function run(): void
    {
        $categoryIds = $this->seedCategories();
        $this->seedIcons($categoryIds);
        $this->refreshCategoryCounts();
        $this->flushIconCaches();

        $this->command?->info('Seeded ' . count(self::icons()) . ' icons in ' . count(self::categories()) . ' categories.');
    }

    /**
     * @return array<string,int> category slug => id
     */
    private function seedCategories(): array
    {
        $ids = [];

        foreach (self::categories() as [$name, $slug, $description]) {
            $category = IconCategories::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );

            $ids[$slug] = $category->id;
        }

        return $ids;
    }

    /**
     * @param  array<string,int>  $categoryIds
     */
    private function seedIcons(array $categoryIds): void
    {
        foreach (self::icons() as [$slug, $title, $categorySlug, $svgSize, $pngSize, $tags]) {
            $svgPath = 'icons/' . $slug . '.svg';
            $pngPath = 'icons/' . $slug . '.png';

            $icon = Icon::updateOrCreate(
                ['title' => $title],
                [
                    'description' => $title . ' icon, available as scalable SVG and 512x512 PNG.',
                    'category_id' => $categoryIds[$categorySlug] ?? null,
                    'user_id' => null,
                    'is_premium' => false,
                    'is_active' => true,
                    'tags' => $tags,
                    'file_svg' => $svgPath,
                    'file_png' => $pngPath,
                ]
            );

            $this->seedFile($icon->id, $slug . '.svg', $svgPath, 'svg', $svgSize, null);
            $this->seedFile($icon->id, $slug . '.png', $pngPath, 'png', $pngSize, self::PNG_DIMENSIONS);
        }
    }

    private function seedFile(int $iconId, string $fileName, string $filePath, string $type, int $fallbackSize, ?string $dimensions): void
    {
        $absolute = public_path($filePath);

        IconFiles::updateOrCreate(
            ['icon_id' => $iconId, 'file_type' => $type],
            [
                'file_name' => $fileName,
                'file_path' => $filePath,
                // Prefer the real file on disk so the numbers stay honest
                // even if the artwork is re-exported later.
                'file_size' => File::exists($absolute) ? File::size($absolute) : $fallbackSize,
                'dimensions' => $dimensions,
            ]
        );
    }

    private function refreshCategoryCounts(): void
    {
        IconCategories::query()->each(function (IconCategories $category): void {
            $category->forceFill(['icon_count' => $category->icons()->count()])->save();
        });
    }

    private function flushIconCaches(): void
    {
        Cache::forget('icon_all');
        Cache::forget('icons_css');
        Cache::forget('icons_meta');

        foreach (self::categories() as [$name]) {
            Cache::forget('icon_all_WithoutPagination_' . $name . '_allSearch');
        }

        Cache::forget('icon_all_WithoutPagination_allCategories_allSearch');
    }

    /**
     * @return array<int,array{0:string,1:string,2:string}> [name, slug, description]
     */
    private static function categories(): array
    {
        return [
            ['Arrows & Navigation', 'arrows-and-navigation', 'Directional arrows, chevrons and movement indicators.'],
            ['Charts & Data', 'charts-and-data', 'Analytics, reporting, statistics and data presentation icons.'],
            ['Commerce & Finance', 'commerce-and-finance', 'Shopping, payments, currency, shipping and billing icons.'],
            ['Communication', 'communication', 'Messaging, mail, calls and notification icons.'],
            ['Devices & Technology', 'devices-and-technology', 'Hardware, connectivity, servers and developer tooling icons.'],
            ['Files & Documents', 'files-and-documents', 'Documents, folders, archives and file management icons.'],
            ['Interface & Layout', 'interface-and-layout', 'Menus, filters, grids, panels and general UI controls.'],
            ['Maps & Places', 'maps-and-places', 'Maps, locations, buildings and geography icons.'],
            ['Media & Playback', 'media-and-playback', 'Audio, video, photography and playback control icons.'],
            ['Objects & Misc', 'objects-and-misc', 'General purpose objects and everyday icons.'],
            ['Security & Privacy', 'security-and-privacy', 'Locks, keys, shields and visibility controls.'],
            ['Status & Alerts', 'status-and-alerts', 'Confirmation, warnings, errors and state indicators.'],
            ['Text & Editing', 'text-and-editing', 'Typography, formatting and content editing icons.'],
            ['Time & Calendar', 'time-and-calendar', 'Clocks, schedules, dates and calendar icons.'],
            ['Users & Accounts', 'users-and-accounts', 'People, profiles, identity and account management icons.'],
            ['Weather & Nature', 'weather-and-nature', 'Weather conditions, nature and science icons.'],
        ];
    }

    /**
     * @return array<int,array{0:string,1:string,2:string,3:int,4:int,5:array<int,string>}>
     *         [slug, title, category slug, svg bytes, png bytes, tags]
     */
    private static function icons(): array
    {
        return [
            ['academic-cap', 'Academic Cap', 'objects-and-misc', 662, 13499, ['academic', 'cap', 'objects', 'misc', 'icon', 'svg']],
            ['adjustments-horizontal', 'Adjustments Horizontal', 'interface-and-layout', 408, 5944, ['adjustments', 'horizontal', 'interface', 'layout', 'icon', 'svg']],
            ['adjustments-vertical', 'Adjustments Vertical', 'interface-and-layout', 407, 5963, ['adjustments', 'vertical', 'interface', 'layout', 'icon', 'svg']],
            ['archive-box', 'Archive Box', 'files-and-documents', 453, 6882, ['archive', 'box', 'files', 'documents', 'icon', 'svg']],
            ['archive-box-arrow-down', 'Archive Box Arrow Down', 'files-and-documents', 470, 7879, ['archive', 'box', 'arrow', 'down', 'files', 'documents', 'icon', 'svg']],
            ['archive-box-x-mark', 'Archive Box X Mark', 'files-and-documents', 515, 7753, ['archive', 'box', 'mark', 'files', 'documents', 'icon', 'svg']],
            ['arrow-down', 'Arrow Down', 'arrows-and-navigation', 234, 3326, ['arrow', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-circle', 'Arrow Down Circle', 'arrows-and-navigation', 265, 11341, ['arrow', 'down', 'circle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-left', 'Arrow Down Left', 'arrows-and-navigation', 237, 3046, ['arrow', 'down', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-on-square', 'Arrow Down On Square', 'arrows-and-navigation', 350, 6162, ['arrow', 'down', 'on', 'square', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-on-square-stack', 'Arrow Down On Square Stack', 'arrows-and-navigation', 461, 7901, ['arrow', 'down', 'on', 'square', 'stack', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-right', 'Arrow Down Right', 'arrows-and-navigation', 234, 3058, ['arrow', 'down', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-down-tray', 'Arrow Down Tray', 'arrows-and-navigation', 306, 4461, ['arrow', 'down', 'tray', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-left', 'Arrow Left', 'arrows-and-navigation', 233, 2605, ['arrow', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-left-circle', 'Arrow Left Circle', 'arrows-and-navigation', 264, 11032, ['arrow', 'left', 'circle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-left-end-on-rectangle', 'Arrow Left End On Rectangle', 'arrows-and-navigation', 351, 5630, ['arrow', 'left', 'end', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-left-on-rectangle', 'Arrow Left On Rectangle', 'arrows-and-navigation', 351, 5630, ['arrow', 'left', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-left-start-on-rectangle', 'Arrow Left Start On Rectangle', 'arrows-and-navigation', 347, 5662, ['arrow', 'left', 'start', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-long-down', 'Arrow Long Down', 'arrows-and-navigation', 238, 2909, ['arrow', 'long', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-long-left', 'Arrow Long Left', 'arrows-and-navigation', 236, 2205, ['arrow', 'long', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-long-right', 'Arrow Long Right', 'arrows-and-navigation', 237, 2180, ['arrow', 'long', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-long-up', 'Arrow Long Up', 'arrows-and-navigation', 235, 2845, ['arrow', 'long', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-path', 'Arrow Path', 'arrows-and-navigation', 365, 8681, ['arrow', 'path', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-path-rounded-square', 'Arrow Path Rounded Square', 'arrows-and-navigation', 525, 8547, ['arrow', 'path', 'rounded', 'square', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-right', 'Arrow Right', 'arrows-and-navigation', 233, 2580, ['arrow', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-right-circle', 'Arrow Right Circle', 'arrows-and-navigation', 265, 10940, ['arrow', 'right', 'circle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-right-end-on-rectangle', 'Arrow Right End On Rectangle', 'arrows-and-navigation', 348, 5700, ['arrow', 'right', 'end', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-right-on-rectangle', 'Arrow Right On Rectangle', 'arrows-and-navigation', 344, 5700, ['arrow', 'right', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-right-start-on-rectangle', 'Arrow Right Start On Rectangle', 'arrows-and-navigation', 344, 5700, ['arrow', 'right', 'start', 'on', 'rectangle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-small-down', 'Arrow Small Down', 'arrows-and-navigation', 241, 3108, ['arrow', 'small', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-small-left', 'Arrow Small Left', 'arrows-and-navigation', 241, 2525, ['arrow', 'small', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-small-right', 'Arrow Small Right', 'arrows-and-navigation', 241, 2491, ['arrow', 'small', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-small-up', 'Arrow Small Up', 'arrows-and-navigation', 241, 3071, ['arrow', 'small', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-top-right-on-square', 'Arrow Top Right On Square', 'arrows-and-navigation', 330, 5702, ['arrow', 'top', 'right', 'on', 'square', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-trending-down', 'Arrow Trending Down', 'charts-and-data', 307, 6230, ['arrow', 'trending', 'down', 'charts', 'data', 'icon', 'svg']],
            ['arrow-trending-up', 'Arrow Trending Up', 'charts-and-data', 302, 5836, ['arrow', 'trending', 'up', 'charts', 'data', 'icon', 'svg']],
            ['arrow-turn-down-left', 'Arrow Turn Down Left', 'arrows-and-navigation', 252, 2877, ['arrow', 'turn', 'down', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-down-right', 'Arrow Turn Down Right', 'arrows-and-navigation', 252, 2829, ['arrow', 'turn', 'down', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-left-down', 'Arrow Turn Left Down', 'arrows-and-navigation', 254, 2991, ['arrow', 'turn', 'left', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-left-up', 'Arrow Turn Left Up', 'arrows-and-navigation', 254, 2989, ['arrow', 'turn', 'left', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-right-down', 'Arrow Turn Right Down', 'arrows-and-navigation', 254, 2984, ['arrow', 'turn', 'right', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-right-up', 'Arrow Turn Right Up', 'arrows-and-navigation', 255, 2982, ['arrow', 'turn', 'right', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-up-left', 'Arrow Turn Up Left', 'arrows-and-navigation', 252, 2953, ['arrow', 'turn', 'up', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-turn-up-right', 'Arrow Turn Up Right', 'arrows-and-navigation', 252, 2892, ['arrow', 'turn', 'up', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up', 'Arrow Up', 'arrows-and-navigation', 232, 3289, ['arrow', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-circle', 'Arrow Up Circle', 'arrows-and-navigation', 264, 11276, ['arrow', 'up', 'circle', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-left', 'Arrow Up Left', 'arrows-and-navigation', 238, 3030, ['arrow', 'up', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-on-square', 'Arrow Up On Square', 'arrows-and-navigation', 346, 6125, ['arrow', 'up', 'on', 'square', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-on-square-stack', 'Arrow Up On Square Stack', 'arrows-and-navigation', 461, 7846, ['arrow', 'up', 'on', 'square', 'stack', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-right', 'Arrow Up Right', 'arrows-and-navigation', 236, 3014, ['arrow', 'up', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-up-tray', 'Arrow Up Tray', 'arrows-and-navigation', 304, 4390, ['arrow', 'up', 'tray', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-uturn-down', 'Arrow Uturn Down', 'arrows-and-navigation', 239, 6055, ['arrow', 'uturn', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-uturn-left', 'Arrow Uturn Left', 'arrows-and-navigation', 240, 5671, ['arrow', 'uturn', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-uturn-right', 'Arrow Uturn Right', 'arrows-and-navigation', 239, 5589, ['arrow', 'uturn', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrow-uturn-up', 'Arrow Uturn Up', 'arrows-and-navigation', 240, 6171, ['arrow', 'uturn', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['arrows-pointing-in', 'Arrows Pointing In', 'arrows-and-navigation', 331, 4486, ['arrows', 'pointing', 'in', 'navigation', 'icon', 'svg']],
            ['arrows-pointing-out', 'Arrows Pointing Out', 'arrows-and-navigation', 347, 4393, ['arrows', 'pointing', 'out', 'navigation', 'icon', 'svg']],
            ['arrows-right-left', 'Arrows Right Left', 'arrows-and-navigation', 272, 3340, ['arrows', 'right', 'left', 'navigation', 'icon', 'svg']],
            ['arrows-up-down', 'Arrows Up Down', 'arrows-and-navigation', 271, 4204, ['arrows', 'up', 'down', 'navigation', 'icon', 'svg']],
            ['at-symbol', 'At Symbol', 'communication', 321, 13908, ['at', 'symbol', 'communication', 'icon', 'svg']],
            ['backspace', 'Backspace', 'objects-and-misc', 446, 5313, ['backspace', 'objects', 'misc', 'icon', 'svg']],
            ['backward', 'Backward', 'media-and-playback', 448, 4808, ['backward', 'media', 'playback', 'icon', 'svg']],
            ['banknotes', 'Banknotes', 'commerce-and-finance', 694, 9434, ['banknotes', 'commerce', 'finance', 'icon', 'svg']],
            ['bars-2', 'Bars 2', 'interface-and-layout', 226, 1812, ['bars', 'interface', 'layout', 'icon', 'svg']],
            ['bars-3', 'Bars 3', 'interface-and-layout', 242, 2139, ['bars', 'interface', 'layout', 'icon', 'svg']],
            ['bars-3-bottom-left', 'Bars 3 Bottom Left', 'interface-and-layout', 240, 2142, ['bars', 'bottom', 'left', 'interface', 'layout', 'icon', 'svg']],
            ['bars-3-bottom-right', 'Bars 3 Bottom Right', 'interface-and-layout', 240, 2141, ['bars', 'bottom', 'right', 'interface', 'layout', 'icon', 'svg']],
            ['bars-3-center-left', 'Bars 3 Center Left', 'interface-and-layout', 240, 2141, ['bars', 'center', 'left', 'interface', 'layout', 'icon', 'svg']],
            ['bars-4', 'Bars 4', 'interface-and-layout', 258, 2466, ['bars', 'interface', 'layout', 'icon', 'svg']],
            ['bars-arrow-down', 'Bars Arrow Down', 'interface-and-layout', 274, 3608, ['bars', 'arrow', 'down', 'interface', 'layout', 'icon', 'svg']],
            ['bars-arrow-up', 'Bars Arrow Up', 'interface-and-layout', 272, 3496, ['bars', 'arrow', 'up', 'interface', 'layout', 'icon', 'svg']],
            ['battery-0', 'Battery 0', 'devices-and-technology', 399, 4355, ['battery', 'devices', 'technology', 'icon', 'svg']],
            ['battery-100', 'Battery 100', 'devices-and-technology', 424, 5009, ['battery', '100', 'devices', 'technology', 'icon', 'svg']],
            ['battery-50', 'Battery 50', 'devices-and-technology', 426, 5094, ['battery', '50', 'devices', 'technology', 'icon', 'svg']],
            ['beaker', 'Beaker', 'weather-and-nature', 629, 8326, ['beaker', 'weather', 'nature', 'icon', 'svg']],
            ['bell', 'Bell', 'communication', 412, 9785, ['bell', 'communication', 'icon', 'svg']],
            ['bell-alert', 'Bell Alert', 'communication', 485, 12082, ['bell', 'alert', 'communication', 'icon', 'svg']],
            ['bell-slash', 'Bell Slash', 'communication', 492, 10535, ['bell', 'slash', 'communication', 'icon', 'svg']],
            ['bell-snooze', 'Bell Snooze', 'communication', 433, 10674, ['bell', 'snooze', 'communication', 'icon', 'svg']],
            ['bold', 'Bold', 'text-and-editing', 499, 5850, ['bold', 'text', 'editing', 'icon', 'svg']],
            ['bolt', 'Bolt', 'devices-and-technology', 257, 5301, ['bolt', 'devices', 'technology', 'icon', 'svg']],
            ['bolt-slash', 'Bolt Slash', 'devices-and-technology', 362, 7308, ['bolt', 'slash', 'devices', 'technology', 'icon', 'svg']],
            ['book-open', 'Book Open', 'files-and-documents', 440, 7415, ['book', 'open', 'files', 'documents', 'icon', 'svg']],
            ['bookmark', 'Bookmark', 'files-and-documents', 328, 5303, ['bookmark', 'files', 'documents', 'icon', 'svg']],
            ['bookmark-slash', 'Bookmark Slash', 'files-and-documents', 391, 6423, ['bookmark', 'slash', 'files', 'documents', 'icon', 'svg']],
            ['bookmark-square', 'Bookmark Square', 'files-and-documents', 357, 5961, ['bookmark', 'square', 'files', 'documents', 'icon', 'svg']],
            ['briefcase', 'Briefcase', 'objects-and-misc', 785, 9968, ['briefcase', 'objects', 'misc', 'icon', 'svg']],
            ['bug-ant', 'Bug Ant', 'objects-and-misc', 1136, 15411, ['bug', 'ant', 'objects', 'misc', 'icon', 'svg']],
            ['building-library', 'Building Library', 'maps-and-places', 355, 6463, ['building', 'library', 'maps', 'places', 'icon', 'svg']],
            ['building-office', 'Building Office', 'maps-and-places', 379, 5260, ['building', 'office', 'maps', 'places', 'icon', 'svg']],
            ['building-office-2', 'Building Office 2', 'maps-and-places', 496, 6916, ['building', 'office', 'maps', 'places', 'icon', 'svg']],
            ['building-storefront', 'Building Storefront', 'commerce-and-finance', 727, 8290, ['building', 'storefront', 'commerce', 'finance', 'icon', 'svg']],
            ['cake', 'Cake', 'objects-and-misc', 904, 8022, ['cake', 'objects', 'misc', 'icon', 'svg']],
            ['calculator', 'Calculator', 'commerce-and-finance', 848, 11072, ['calculator', 'commerce', 'finance', 'icon', 'svg']],
            ['calendar', 'Calendar', 'time-and-calendar', 423, 5312, ['calendar', 'time', 'icon', 'svg']],
            ['calendar-date-range', 'Calendar Date Range', 'time-and-calendar', 694, 8659, ['calendar', 'date', 'range', 'time', 'icon', 'svg']],
            ['calendar-days', 'Calendar Days', 'time-and-calendar', 753, 6778, ['calendar', 'days', 'time', 'icon', 'svg']],
            ['camera', 'Camera', 'media-and-playback', 705, 11112, ['camera', 'media', 'playback', 'icon', 'svg']],
            ['chart-bar', 'Chart Bar', 'charts-and-data', 646, 5938, ['chart', 'bar', 'charts', 'data', 'icon', 'svg']],
            ['chart-bar-square', 'Chart Bar Square', 'charts-and-data', 356, 6126, ['chart', 'bar', 'square', 'charts', 'data', 'icon', 'svg']],
            ['chart-pie', 'Chart Pie', 'charts-and-data', 336, 9894, ['chart', 'pie', 'charts', 'data', 'icon', 'svg']],
            ['chat-bubble-bottom-center', 'Chat Bubble Bottom Center', 'communication', 538, 7639, ['chat', 'bubble', 'bottom', 'center', 'communication', 'icon', 'svg']],
            ['chat-bubble-bottom-center-text', 'Chat Bubble Bottom Center Text', 'communication', 550, 8356, ['chat', 'bubble', 'bottom', 'center', 'text', 'communication', 'icon', 'svg']],
            ['chat-bubble-left', 'Chat Bubble Left', 'communication', 509, 7522, ['chat', 'bubble', 'left', 'communication', 'icon', 'svg']],
            ['chat-bubble-left-ellipsis', 'Chat Bubble Left Ellipsis', 'communication', 688, 8291, ['chat', 'bubble', 'left', 'ellipsis', 'communication', 'icon', 'svg']],
            ['chat-bubble-left-right', 'Chat Bubble Left Right', 'communication', 716, 9556, ['chat', 'bubble', 'left', 'right', 'communication', 'icon', 'svg']],
            ['chat-bubble-oval-left', 'Chat Bubble Oval Left', 'communication', 424, 9999, ['chat', 'bubble', 'oval', 'left', 'communication', 'icon', 'svg']],
            ['chat-bubble-oval-left-ellipsis', 'Chat Bubble Oval Left Ellipsis', 'communication', 619, 10714, ['chat', 'bubble', 'oval', 'left', 'ellipsis', 'communication', 'icon', 'svg']],
            ['check', 'Check', 'status-and-alerts', 219, 2937, ['check', 'status', 'alerts', 'icon', 'svg']],
            ['check-badge', 'Check Badge', 'status-and-alerts', 679, 11345, ['check', 'badge', 'status', 'alerts', 'icon', 'svg']],
            ['check-circle', 'Check Circle', 'status-and-alerts', 260, 11195, ['check', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['chevron-double-down', 'Chevron Double Down', 'arrows-and-navigation', 245, 3576, ['chevron', 'double', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-double-left', 'Chevron Double Left', 'arrows-and-navigation', 246, 3036, ['chevron', 'double', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-double-right', 'Chevron Double Right', 'arrows-and-navigation', 244, 3021, ['chevron', 'double', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-double-up', 'Chevron Double Up', 'arrows-and-navigation', 312, 3533, ['chevron', 'double', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-down', 'Chevron Down', 'arrows-and-navigation', 224, 2396, ['chevron', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-left', 'Chevron Left', 'arrows-and-navigation', 225, 2421, ['chevron', 'left', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-right', 'Chevron Right', 'arrows-and-navigation', 223, 2396, ['chevron', 'right', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-up', 'Chevron Up', 'arrows-and-navigation', 224, 2365, ['chevron', 'up', 'arrows', 'navigation', 'icon', 'svg']],
            ['chevron-up-down', 'Chevron Up Down', 'arrows-and-navigation', 247, 2772, ['chevron', 'up', 'down', 'arrows', 'navigation', 'icon', 'svg']],
            ['circle-stack', 'Circle Stack', 'charts-and-data', 555, 13822, ['circle', 'stack', 'charts', 'data', 'icon', 'svg']],
            ['clipboard', 'Clipboard', 'files-and-documents', 566, 6034, ['clipboard', 'files', 'documents', 'icon', 'svg']],
            ['clipboard-document', 'Clipboard Document', 'files-and-documents', 760, 9184, ['clipboard', 'document', 'files', 'documents', 'icon', 'svg']],
            ['clipboard-document-check', 'Clipboard Document Check', 'files-and-documents', 740, 7821, ['clipboard', 'document', 'check', 'files', 'documents', 'icon', 'svg']],
            ['clipboard-document-list', 'Clipboard Document List', 'files-and-documents', 794, 8685, ['clipboard', 'document', 'list', 'files', 'documents', 'icon', 'svg']],
            ['clock', 'Clock', 'time-and-calendar', 246, 10741, ['clock', 'time', 'calendar', 'icon', 'svg']],
            ['cloud', 'Cloud', 'weather-and-nature', 336, 8543, ['cloud', 'weather', 'nature', 'icon', 'svg']],
            ['cloud-arrow-down', 'Cloud Arrow Down', 'weather-and-nature', 342, 9815, ['cloud', 'arrow', 'down', 'weather', 'nature', 'icon', 'svg']],
            ['cloud-arrow-up', 'Cloud Arrow Up', 'weather-and-nature', 345, 9788, ['cloud', 'arrow', 'up', 'weather', 'nature', 'icon', 'svg']],
            ['code-bracket', 'Code Bracket', 'devices-and-technology', 268, 4832, ['code', 'bracket', 'devices', 'technology', 'icon', 'svg']],
            ['code-bracket-square', 'Code Bracket Square', 'devices-and-technology', 367, 5744, ['code', 'bracket', 'square', 'devices', 'technology', 'icon', 'svg']],
            ['cog', 'Cog', 'objects-and-misc', 647, 13612, ['cog', 'objects', 'misc', 'icon', 'svg']],
            ['cog-6-tooth', 'Cog 6 Tooth', 'objects-and-misc', 1294, 13041, ['cog', 'tooth', 'objects', 'misc', 'icon', 'svg']],
            ['cog-8-tooth', 'Cog 8 Tooth', 'objects-and-misc', 1368, 13561, ['cog', 'tooth', 'objects', 'misc', 'icon', 'svg']],
            ['command-line', 'Command Line', 'devices-and-technology', 347, 5720, ['command', 'line', 'devices', 'technology', 'icon', 'svg']],
            ['computer-desktop', 'Computer Desktop', 'devices-and-technology', 466, 5898, ['computer', 'desktop', 'devices', 'technology', 'icon', 'svg']],
            ['cpu-chip', 'Cpu Chip', 'devices-and-technology', 469, 6831, ['cpu', 'chip', 'devices', 'technology', 'icon', 'svg']],
            ['credit-card', 'Credit Card', 'commerce-and-finance', 376, 5052, ['credit', 'card', 'commerce', 'finance', 'icon', 'svg']],
            ['cube', 'Cube', 'objects-and-misc', 281, 5918, ['cube', 'objects', 'misc', 'icon', 'svg']],
            ['cube-transparent', 'Cube Transparent', 'objects-and-misc', 464, 7962, ['cube', 'transparent', 'objects', 'misc', 'icon', 'svg']],
            ['currency-bangladeshi', 'Currency Bangladeshi', 'commerce-and-finance', 394, 12704, ['currency', 'bangladeshi', 'commerce', 'finance', 'icon', 'svg']],
            ['currency-dollar', 'Currency Dollar', 'commerce-and-finance', 432, 13730, ['currency', 'dollar', 'commerce', 'finance', 'icon', 'svg']],
            ['currency-euro', 'Currency Euro', 'commerce-and-finance', 296, 12844, ['currency', 'euro', 'commerce', 'finance', 'icon', 'svg']],
            ['currency-pound', 'Currency Pound', 'commerce-and-finance', 426, 12840, ['currency', 'pound', 'commerce', 'finance', 'icon', 'svg']],
            ['currency-rupee', 'Currency Rupee', 'commerce-and-finance', 277, 12118, ['currency', 'rupee', 'commerce', 'finance', 'icon', 'svg']],
            ['currency-yen', 'Currency Yen', 'commerce-and-finance', 281, 11841, ['currency', 'yen', 'commerce', 'finance', 'icon', 'svg']],
            ['cursor-arrow-rays', 'Cursor Arrow Rays', 'interface-and-layout', 378, 8293, ['cursor', 'arrow', 'rays', 'interface', 'layout', 'icon', 'svg']],
            ['cursor-arrow-ripple', 'Cursor Arrow Ripple', 'interface-and-layout', 351, 14305, ['cursor', 'arrow', 'ripple', 'interface', 'layout', 'icon', 'svg']],
            ['device-phone-mobile', 'Device Phone Mobile', 'devices-and-technology', 362, 5401, ['device', 'phone', 'mobile', 'devices', 'technology', 'icon', 'svg']],
            ['device-tablet', 'Device Tablet', 'devices-and-technology', 341, 5018, ['device', 'tablet', 'devices', 'technology', 'icon', 'svg']],
            ['divide', 'Divide', 'interface-and-layout', 377, 2779, ['divide', 'interface', 'layout', 'icon', 'svg']],
            ['document', 'Document', 'files-and-documents', 455, 6514, ['document', 'files', 'documents', 'icon', 'svg']],
            ['document-arrow-down', 'Document Arrow Down', 'files-and-documents', 482, 7721, ['document', 'arrow', 'down', 'files', 'documents', 'icon', 'svg']],
            ['document-arrow-up', 'Document Arrow Up', 'files-and-documents', 482, 7701, ['document', 'arrow', 'up', 'files', 'documents', 'icon', 'svg']],
            ['document-chart-bar', 'Document Chart Bar', 'files-and-documents', 484, 7597, ['document', 'chart', 'bar', 'files', 'documents', 'icon', 'svg']],
            ['document-check', 'Document Check', 'files-and-documents', 484, 7642, ['document', 'check', 'files', 'documents', 'icon', 'svg']],
            ['document-currency-bangladeshi', 'Document Currency Bangladeshi', 'commerce-and-finance', 598, 9116, ['document', 'currency', 'bangladeshi', 'commerce', 'finance', 'icon', 'svg']],
            ['document-currency-dollar', 'Document Currency Dollar', 'commerce-and-finance', 706, 9166, ['document', 'currency', 'dollar', 'commerce', 'finance', 'icon', 'svg']],
            ['document-currency-euro', 'Document Currency Euro', 'commerce-and-finance', 591, 9544, ['document', 'currency', 'euro', 'commerce', 'finance', 'icon', 'svg']],
            ['document-currency-pound', 'Document Currency Pound', 'commerce-and-finance', 619, 9397, ['document', 'currency', 'pound', 'commerce', 'finance', 'icon', 'svg']],
            ['document-currency-rupee', 'Document Currency Rupee', 'commerce-and-finance', 535, 8497, ['document', 'currency', 'rupee', 'commerce', 'finance', 'icon', 'svg']],
            ['document-currency-yen', 'Document Currency Yen', 'commerce-and-finance', 519, 8175, ['document', 'currency', 'yen', 'commerce', 'finance', 'icon', 'svg']],
            ['document-duplicate', 'Document Duplicate', 'files-and-documents', 667, 7483, ['document', 'duplicate', 'files', 'documents', 'icon', 'svg']],
            ['document-magnifying-glass', 'Document Magnifying Glass', 'files-and-documents', 540, 9146, ['document', 'magnifying', 'glass', 'files', 'documents', 'icon', 'svg']],
            ['document-minus', 'Document Minus', 'files-and-documents', 465, 6857, ['document', 'minus', 'files', 'documents', 'icon', 'svg']],
            ['document-plus', 'Document Plus', 'files-and-documents', 470, 7407, ['document', 'plus', 'files', 'documents', 'icon', 'svg']],
            ['document-text', 'Document Text', 'files-and-documents', 480, 7242, ['document', 'text', 'files', 'documents', 'icon', 'svg']],
            ['ellipsis-horizontal', 'Ellipsis Horizontal', 'interface-and-layout', 341, 2044, ['ellipsis', 'horizontal', 'interface', 'layout', 'icon', 'svg']],
            ['ellipsis-horizontal-circle', 'Ellipsis Horizontal Circle', 'interface-and-layout', 415, 10736, ['ellipsis', 'horizontal', 'circle', 'interface', 'layout', 'icon', 'svg']],
            ['ellipsis-vertical', 'Ellipsis Vertical', 'interface-and-layout', 338, 3298, ['ellipsis', 'vertical', 'interface', 'layout', 'icon', 'svg']],
            ['envelope', 'Envelope', 'communication', 452, 6367, ['envelope', 'communication', 'icon', 'svg']],
            ['envelope-open', 'Envelope Open', 'communication', 566, 8413, ['envelope', 'open', 'communication', 'icon', 'svg']],
            ['equals', 'Equals', 'interface-and-layout', 226, 1962, ['equals', 'interface', 'layout', 'icon', 'svg']],
            ['exclamation-circle', 'Exclamation Circle', 'status-and-alerts', 273, 10905, ['exclamation', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['exclamation-triangle', 'Exclamation Triangle', 'status-and-alerts', 374, 8964, ['exclamation', 'triangle', 'status', 'alerts', 'icon', 'svg']],
            ['eye', 'Eye', 'security-and-privacy', 479, 12103, ['eye', 'security', 'privacy', 'icon', 'svg']],
            ['eye-dropper', 'Eye Dropper', 'text-and-editing', 434, 6743, ['eye', 'dropper', 'text', 'editing', 'icon', 'svg']],
            ['eye-slash', 'Eye Slash', 'security-and-privacy', 530, 11202, ['eye', 'slash', 'security', 'privacy', 'icon', 'svg']],
            ['face-frown', 'Face Frown', 'users-and-accounts', 532, 11971, ['face', 'frown', 'users', 'accounts', 'icon', 'svg']],
            ['face-smile', 'Face Smile', 'users-and-accounts', 496, 11965, ['face', 'smile', 'users', 'accounts', 'icon', 'svg']],
            ['film', 'Film', 'media-and-playback', 1958, 4963, ['film', 'media', 'playback', 'icon', 'svg']],
            ['finger-print', 'Finger Print', 'users-and-accounts', 503, 14033, ['finger', 'print', 'users', 'accounts', 'icon', 'svg']],
            ['fire', 'Fire', 'weather-and-nature', 519, 14134, ['fire', 'weather', 'nature', 'icon', 'svg']],
            ['flag', 'Flag', 'maps-and-places', 397, 7940, ['flag', 'maps', 'places', 'icon', 'svg']],
            ['folder', 'Folder', 'files-and-documents', 457, 5381, ['folder', 'files', 'documents', 'icon', 'svg']],
            ['folder-arrow-down', 'Folder Arrow Down', 'files-and-documents', 412, 6129, ['folder', 'arrow', 'down', 'files', 'documents', 'icon', 'svg']],
            ['folder-minus', 'Folder Minus', 'files-and-documents', 395, 5241, ['folder', 'minus', 'files', 'documents', 'icon', 'svg']],
            ['folder-open', 'Folder Open', 'files-and-documents', 534, 6255, ['folder', 'open', 'files', 'documents', 'icon', 'svg']],
            ['folder-plus', 'Folder Plus', 'files-and-documents', 401, 5821, ['folder', 'plus', 'files', 'documents', 'icon', 'svg']],
            ['forward', 'Forward', 'media-and-playback', 444, 4851, ['forward', 'media', 'playback', 'icon', 'svg']],
            ['funnel', 'Funnel', 'interface-and-layout', 497, 6289, ['funnel', 'interface', 'layout', 'icon', 'svg']],
            ['gif', 'Gif', 'media-and-playback', 484, 8381, ['gif', 'media', 'playback', 'icon', 'svg']],
            ['gift', 'Gift', 'commerce-and-finance', 514, 6935, ['gift', 'commerce', 'finance', 'icon', 'svg']],
            ['gift-top', 'Gift Top', 'commerce-and-finance', 626, 9590, ['gift', 'top', 'commerce', 'finance', 'icon', 'svg']],
            ['globe-alt', 'Globe Alt', 'maps-and-places', 673, 15767, ['globe', 'alt', 'maps', 'places', 'icon', 'svg']],
            ['globe-americas', 'Globe Americas', 'maps-and-places', 790, 14676, ['globe', 'americas', 'maps', 'places', 'icon', 'svg']],
            ['globe-asia-australia', 'Globe Asia Australia', 'maps-and-places', 875, 15081, ['globe', 'asia', 'australia', 'maps', 'places', 'icon', 'svg']],
            ['globe-europe-africa', 'Globe Europe Africa', 'maps-and-places', 941, 15822, ['globe', 'europe', 'africa', 'maps', 'places', 'icon', 'svg']],
            ['h1', 'H1', 'text-and-editing', 307, 4419, ['h1', 'text', 'editing', 'icon', 'svg']],
            ['h2', 'H2', 'text-and-editing', 441, 5603, ['h2', 'text', 'editing', 'icon', 'svg']],
            ['h3', 'H3', 'text-and-editing', 478, 6133, ['h3', 'text', 'editing', 'icon', 'svg']],
            ['hand-raised', 'Hand Raised', 'users-and-accounts', 642, 11521, ['hand', 'raised', 'users', 'accounts', 'icon', 'svg']],
            ['hand-thumb-down', 'Hand Thumb Down', 'users-and-accounts', 960, 11231, ['hand', 'thumb', 'down', 'users', 'accounts', 'icon', 'svg']],
            ['hand-thumb-up', 'Hand Thumb Up', 'users-and-accounts', 921, 11356, ['hand', 'thumb', 'up', 'users', 'accounts', 'icon', 'svg']],
            ['hashtag', 'Hashtag', 'interface-and-layout', 262, 4104, ['hashtag', 'interface', 'layout', 'icon', 'svg']],
            ['heart', 'Heart', 'objects-and-misc', 355, 10152, ['heart', 'objects', 'misc', 'icon', 'svg']],
            ['home', 'Home', 'maps-and-places', 432, 5263, ['home', 'maps', 'places', 'icon', 'svg']],
            ['home-modern', 'Home Modern', 'maps-and-places', 431, 6263, ['home', 'modern', 'maps', 'places', 'icon', 'svg']],
            ['identification', 'Identification', 'users-and-accounts', 514, 9133, ['identification', 'users', 'accounts', 'icon', 'svg']],
            ['inbox', 'Inbox', 'files-and-documents', 563, 7440, ['inbox', 'files', 'documents', 'icon', 'svg']],
            ['inbox-arrow-down', 'Inbox Arrow Down', 'files-and-documents', 588, 9203, ['inbox', 'arrow', 'down', 'files', 'documents', 'icon', 'svg']],
            ['inbox-stack', 'Inbox Stack', 'files-and-documents', 849, 8242, ['inbox', 'stack', 'files', 'documents', 'icon', 'svg']],
            ['information-circle', 'Information Circle', 'status-and-alerts', 350, 11591, ['information', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['italic', 'Italic', 'text-and-editing', 273, 3226, ['italic', 'text', 'editing', 'icon', 'svg']],
            ['key', 'Key', 'security-and-privacy', 405, 9215, ['key', 'security', 'privacy', 'icon', 'svg']],
            ['language', 'Language', 'text-and-editing', 440, 9271, ['language', 'text', 'editing', 'icon', 'svg']],
            ['lifebuoy', 'Lifebuoy', 'objects-and-misc', 1198, 14082, ['lifebuoy', 'objects', 'misc', 'icon', 'svg']],
            ['light-bulb', 'Light Bulb', 'objects-and-misc', 444, 10926, ['light', 'bulb', 'objects', 'misc', 'icon', 'svg']],
            ['link', 'Link', 'interface-and-layout', 367, 10212, ['link', 'interface', 'layout', 'icon', 'svg']],
            ['link-slash', 'Link Slash', 'interface-and-layout', 527, 11379, ['link', 'slash', 'interface', 'layout', 'icon', 'svg']],
            ['list-bullet', 'List Bullet', 'text-and-editing', 475, 3816, ['list', 'bullet', 'text', 'editing', 'icon', 'svg']],
            ['lock-closed', 'Lock Closed', 'security-and-privacy', 373, 6482, ['lock', 'closed', 'security', 'privacy', 'icon', 'svg']],
            ['lock-open', 'Lock Open', 'security-and-privacy', 373, 6716, ['lock', 'open', 'security', 'privacy', 'icon', 'svg']],
            ['magnifying-glass', 'Magnifying Glass', 'interface-and-layout', 275, 8997, ['magnifying', 'glass', 'interface', 'layout', 'icon', 'svg']],
            ['magnifying-glass-circle', 'Magnifying Glass Circle', 'interface-and-layout', 324, 13279, ['magnifying', 'glass', 'circle', 'interface', 'layout', 'icon', 'svg']],
            ['magnifying-glass-minus', 'Magnifying Glass Minus', 'interface-and-layout', 288, 9331, ['magnifying', 'glass', 'minus', 'interface', 'layout', 'icon', 'svg']],
            ['magnifying-glass-plus', 'Magnifying Glass Plus', 'interface-and-layout', 293, 10016, ['magnifying', 'glass', 'plus', 'interface', 'layout', 'icon', 'svg']],
            ['map', 'Map', 'maps-and-places', 522, 6759, ['map', 'maps', 'places', 'icon', 'svg']],
            ['map-pin', 'Map Pin', 'maps-and-places', 375, 12421, ['map', 'pin', 'maps', 'places', 'icon', 'svg']],
            ['megaphone', 'Megaphone', 'communication', 817, 11719, ['megaphone', 'communication', 'icon', 'svg']],
            ['microphone', 'Microphone', 'media-and-playback', 336, 8597, ['microphone', 'media', 'playback', 'icon', 'svg']],
            ['minus', 'Minus', 'status-and-alerts', 206, 1464, ['minus', 'status', 'alerts', 'icon', 'svg']],
            ['minus-circle', 'Minus Circle', 'status-and-alerts', 242, 10323, ['minus', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['minus-small', 'Minus Small', 'status-and-alerts', 206, 1478, ['minus', 'small', 'status', 'alerts', 'icon', 'svg']],
            ['moon', 'Moon', 'weather-and-nature', 378, 10695, ['moon', 'weather', 'nature', 'icon', 'svg']],
            ['musical-note', 'Musical Note', 'media-and-playback', 459, 6971, ['musical', 'note', 'media', 'playback', 'icon', 'svg']],
            ['newspaper', 'Newspaper', 'files-and-documents', 476, 6519, ['newspaper', 'files', 'documents', 'icon', 'svg']],
            ['no-symbol', 'No Symbol', 'security-and-privacy', 296, 10570, ['no', 'symbol', 'security', 'privacy', 'icon', 'svg']],
            ['numbered-list', 'Numbered List', 'text-and-editing', 441, 5999, ['numbered', 'list', 'text', 'editing', 'icon', 'svg']],
            ['paint-brush', 'Paint Brush', 'text-and-editing', 553, 10010, ['paint', 'brush', 'text', 'editing', 'icon', 'svg']],
            ['paper-airplane', 'Paper Airplane', 'communication', 295, 8866, ['paper', 'airplane', 'communication', 'icon', 'svg']],
            ['paper-clip', 'Paper Clip', 'files-and-documents', 358, 8138, ['paper', 'clip', 'files', 'documents', 'icon', 'svg']],
            ['pause', 'Pause', 'media-and-playback', 229, 2624, ['pause', 'media', 'playback', 'icon', 'svg']],
            ['pause-circle', 'Pause Circle', 'media-and-playback', 254, 10988, ['pause', 'circle', 'media', 'playback', 'icon', 'svg']],
            ['pencil', 'Pencil', 'text-and-editing', 361, 5406, ['pencil', 'text', 'editing', 'icon', 'svg']],
            ['pencil-square', 'Pencil Square', 'text-and-editing', 454, 7892, ['pencil', 'square', 'text', 'editing', 'icon', 'svg']],
            ['percent-badge', 'Percent Badge', 'commerce-and-finance', 864, 12031, ['percent', 'badge', 'commerce', 'finance', 'icon', 'svg']],
            ['phone', 'Phone', 'communication', 551, 10353, ['phone', 'communication', 'icon', 'svg']],
            ['phone-arrow-down-left', 'Phone Arrow Down Left', 'communication', 575, 11598, ['phone', 'arrow', 'down', 'left', 'communication', 'icon', 'svg']],
            ['phone-arrow-up-right', 'Phone Arrow Up Right', 'communication', 573, 11501, ['phone', 'arrow', 'up', 'right', 'communication', 'icon', 'svg']],
            ['phone-x-mark', 'Phone X Mark', 'communication', 602, 11460, ['phone', 'mark', 'communication', 'icon', 'svg']],
            ['photo', 'Photo', 'media-and-playback', 509, 6384, ['photo', 'media', 'playback', 'icon', 'svg']],
            ['play', 'Play', 'media-and-playback', 324, 4936, ['play', 'media', 'playback', 'icon', 'svg']],
            ['play-circle', 'Play Circle', 'media-and-playback', 414, 11858, ['play', 'circle', 'media', 'playback', 'icon', 'svg']],
            ['play-pause', 'Play Pause', 'media-and-playback', 339, 5106, ['play', 'pause', 'media', 'playback', 'icon', 'svg']],
            ['plus', 'Plus', 'status-and-alerts', 220, 2461, ['plus', 'status', 'alerts', 'icon', 'svg']],
            ['plus-circle', 'Plus Circle', 'status-and-alerts', 247, 10877, ['plus', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['plus-small', 'Plus Small', 'status-and-alerts', 212, 2312, ['plus', 'small', 'status', 'alerts', 'icon', 'svg']],
            ['power', 'Power', 'devices-and-technology', 236, 9287, ['power', 'devices', 'technology', 'icon', 'svg']],
            ['presentation-chart-bar', 'Presentation Chart Bar', 'charts-and-data', 408, 5535, ['presentation', 'chart', 'bar', 'charts', 'data', 'icon', 'svg']],
            ['presentation-chart-line', 'Presentation Chart Line', 'charts-and-data', 433, 6173, ['presentation', 'chart', 'line', 'charts', 'data', 'icon', 'svg']],
            ['printer', 'Printer', 'devices-and-technology', 809, 9445, ['printer', 'devices', 'technology', 'icon', 'svg']],
            ['puzzle-piece', 'Puzzle Piece', 'objects-and-misc', 1272, 13465, ['puzzle', 'piece', 'objects', 'misc', 'icon', 'svg']],
            ['qr-code', 'Qr Code', 'devices-and-technology', 935, 6765, ['qr', 'code', 'devices', 'technology', 'icon', 'svg']],
            ['question-mark-circle', 'Question Mark Circle', 'status-and-alerts', 397, 12567, ['question', 'mark', 'circle', 'status', 'alerts', 'icon', 'svg']],
            ['queue-list', 'Queue List', 'charts-and-data', 315, 4033, ['queue', 'list', 'charts', 'data', 'icon', 'svg']],
            ['radio', 'Radio', 'devices-and-technology', 1219, 12869, ['radio', 'devices', 'technology', 'icon', 'svg']],
            ['receipt-percent', 'Receipt Percent', 'commerce-and-finance', 518, 7167, ['receipt', 'percent', 'commerce', 'finance', 'icon', 'svg']],
            ['receipt-refund', 'Receipt Refund', 'commerce-and-finance', 428, 7614, ['receipt', 'refund', 'commerce', 'finance', 'icon', 'svg']],
            ['rectangle-group', 'Rectangle Group', 'interface-and-layout', 648, 5744, ['rectangle', 'group', 'interface', 'layout', 'icon', 'svg']],
            ['rectangle-stack', 'Rectangle Stack', 'interface-and-layout', 564, 5938, ['rectangle', 'stack', 'interface', 'layout', 'icon', 'svg']],
            ['rocket-launch', 'Rocket Launch', 'objects-and-misc', 628, 14476, ['rocket', 'launch', 'objects', 'misc', 'icon', 'svg']],
            ['rss', 'Rss', 'interface-and-layout', 332, 7997, ['rss', 'interface', 'layout', 'icon', 'svg']],
            ['scale', 'Scale', 'commerce-and-finance', 704, 10332, ['scale', 'commerce', 'finance', 'icon', 'svg']],
            ['scissors', 'Scissors', 'objects-and-misc', 771, 13352, ['scissors', 'objects', 'misc', 'icon', 'svg']],
            ['server', 'Server', 'devices-and-technology', 495, 8545, ['server', 'devices', 'technology', 'icon', 'svg']],
            ['server-stack', 'Server Stack', 'devices-and-technology', 570, 8563, ['server', 'stack', 'devices', 'technology', 'icon', 'svg']],
            ['share', 'Share', 'interface-and-layout', 459, 8765, ['share', 'interface', 'layout', 'icon', 'svg']],
            ['shield-check', 'Shield Check', 'security-and-privacy', 408, 11491, ['shield', 'check', 'security', 'privacy', 'icon', 'svg']],
            ['shield-exclamation', 'Shield Exclamation', 'security-and-privacy', 418, 11179, ['shield', 'exclamation', 'security', 'privacy', 'icon', 'svg']],
            ['shopping-bag', 'Shopping Bag', 'commerce-and-finance', 500, 8950, ['shopping', 'bag', 'commerce', 'finance', 'icon', 'svg']],
            ['shopping-cart', 'Shopping Cart', 'commerce-and-finance', 473, 7549, ['shopping', 'cart', 'commerce', 'finance', 'icon', 'svg']],
            ['signal', 'Signal', 'devices-and-technology', 496, 13767, ['signal', 'devices', 'technology', 'icon', 'svg']],
            ['signal-slash', 'Signal Slash', 'devices-and-technology', 536, 12763, ['signal', 'slash', 'devices', 'technology', 'icon', 'svg']],
            ['slash', 'Slash', 'objects-and-misc', 214, 2713, ['slash', 'objects', 'misc', 'icon', 'svg']],
            ['sparkles', 'Sparkles', 'weather-and-nature', 809, 10858, ['sparkles', 'weather', 'nature', 'icon', 'svg']],
            ['speaker-wave', 'Speaker Wave', 'media-and-playback', 471, 9648, ['speaker', 'wave', 'media', 'playback', 'icon', 'svg']],
            ['speaker-x-mark', 'Speaker X Mark', 'media-and-playback', 470, 6041, ['speaker', 'mark', 'media', 'playback', 'icon', 'svg']],
            ['square-2-stack', 'Square 2 Stack', 'interface-and-layout', 436, 6179, ['square', 'stack', 'interface', 'layout', 'icon', 'svg']],
            ['square-3-stack-3d', 'Square 3 Stack 3D', 'interface-and-layout', 393, 7706, ['square', 'stack', '3d', 'interface', 'layout', 'icon', 'svg']],
            ['squares-2x2', 'Squares 2X2', 'interface-and-layout', 700, 7230, ['squares', '2x2', 'interface', 'layout', 'icon', 'svg']],
            ['squares-plus', 'Squares Plus', 'interface-and-layout', 624, 7720, ['squares', 'plus', 'interface', 'layout', 'icon', 'svg']],
            ['star', 'Star', 'objects-and-misc', 565, 9799, ['star', 'objects', 'misc', 'icon', 'svg']],
            ['stop', 'Stop', 'media-and-playback', 321, 4178, ['stop', 'media', 'playback', 'icon', 'svg']],
            ['stop-circle', 'Stop Circle', 'media-and-playback', 427, 10908, ['stop', 'circle', 'media', 'playback', 'icon', 'svg']],
            ['strikethrough', 'Strikethrough', 'text-and-editing', 474, 7071, ['strikethrough', 'text', 'editing', 'icon', 'svg']],
            ['sun', 'Sun', 'weather-and-nature', 391, 8252, ['sun', 'weather', 'nature', 'icon', 'svg']],
            ['swatch', 'Swatch', 'text-and-editing', 605, 7202, ['swatch', 'text', 'editing', 'icon', 'svg']],
            ['table-cells', 'Table Cells', 'charts-and-data', 1434, 4295, ['table', 'cells', 'charts', 'data', 'icon', 'svg']],
            ['tag', 'Tag', 'commerce-and-finance', 484, 6386, ['tag', 'commerce', 'finance', 'icon', 'svg']],
            ['ticket', 'Ticket', 'commerce-and-finance', 474, 6848, ['ticket', 'commerce', 'finance', 'icon', 'svg']],
            ['trash', 'Trash', 'objects-and-misc', 611, 10907, ['trash', 'objects', 'misc', 'icon', 'svg']],
            ['trophy', 'Trophy', 'objects-and-misc', 843, 10791, ['trophy', 'objects', 'misc', 'icon', 'svg']],
            ['truck', 'Truck', 'commerce-and-finance', 615, 7636, ['truck', 'commerce', 'finance', 'icon', 'svg']],
            ['tv', 'Tv', 'devices-and-technology', 378, 3802, ['tv', 'devices', 'technology', 'icon', 'svg']],
            ['underline', 'Underline', 'text-and-editing', 253, 5548, ['underline', 'text', 'editing', 'icon', 'svg']],
            ['user', 'User', 'users-and-accounts', 349, 10282, ['user', 'users', 'accounts', 'icon', 'svg']],
            ['user-circle', 'User Circle', 'users-and-accounts', 397, 14127, ['user', 'circle', 'users', 'accounts', 'icon', 'svg']],
            ['user-group', 'User Group', 'users-and-accounts', 708, 13236, ['user', 'group', 'users', 'accounts', 'icon', 'svg']],
            ['user-minus', 'User Minus', 'users-and-accounts', 380, 9767, ['user', 'minus', 'users', 'accounts', 'icon', 'svg']],
            ['user-plus', 'User Plus', 'users-and-accounts', 397, 10332, ['user', 'plus', 'users', 'accounts', 'icon', 'svg']],
            ['users', 'Users', 'users-and-accounts', 575, 13551, ['users', 'accounts', 'icon', 'svg']],
            ['variable', 'Variable', 'charts-and-data', 488, 11363, ['variable', 'charts', 'data', 'icon', 'svg']],
            ['video-camera', 'Video Camera', 'media-and-playback', 402, 5840, ['video', 'camera', 'media', 'playback', 'icon', 'svg']],
            ['video-camera-slash', 'Video Camera Slash', 'media-and-playback', 489, 6501, ['video', 'camera', 'slash', 'media', 'playback', 'icon', 'svg']],
            ['view-columns', 'View Columns', 'charts-and-data', 365, 4195, ['view', 'columns', 'charts', 'data', 'icon', 'svg']],
            ['viewfinder-circle', 'Viewfinder Circle', 'objects-and-misc', 385, 7768, ['viewfinder', 'circle', 'objects', 'misc', 'icon', 'svg']],
            ['wallet', 'Wallet', 'commerce-and-finance', 465, 7083, ['wallet', 'commerce', 'finance', 'icon', 'svg']],
            ['wifi', 'Wifi', 'devices-and-technology', 375, 9012, ['wifi', 'devices', 'technology', 'icon', 'svg']],
            ['window', 'Window', 'interface-and-layout', 413, 5064, ['window', 'interface', 'layout', 'icon', 'svg']],
            ['wrench', 'Wrench', 'objects-and-misc', 557, 10953, ['wrench', 'objects', 'misc', 'icon', 'svg']],
            ['wrench-screwdriver', 'Wrench Screwdriver', 'objects-and-misc', 719, 14551, ['wrench', 'screwdriver', 'objects', 'misc', 'icon', 'svg']],
            ['x-circle', 'X Circle', 'status-and-alerts', 267, 11082, ['circle', 'status', 'alerts', 'icon', 'svg']],
            ['x-mark', 'X Mark', 'status-and-alerts', 218, 3023, ['mark', 'status', 'alerts', 'icon', 'svg']],
        ];
    }
}
