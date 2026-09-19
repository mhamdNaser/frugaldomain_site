<?php

namespace App\Modules\Icon\database\seeders;

use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconCategories;
use App\Modules\Icon\Models\IconFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Seeds the whole icon library (546 icons across 17 categories).
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
        foreach (self::icons() as $row) {
            [$slug, $title, $categorySlug, $svgSize, $pngSize, $tags] = $row;

            // Flags are 4:3, every other icon is square, so a row may carry
            // its own dimensions and description.
            $dimensions = $row[6] ?? self::PNG_DIMENSIONS;
            $description = $row[7]
                ?? $title . ' icon, available as scalable SVG and ' . $dimensions . ' PNG.';

            $svgPath = 'icons/' . $slug . '.svg';
            $pngPath = 'icons/' . $slug . '.png';

            $icon = Icon::updateOrCreate(
                ['title' => $title],
                [
                    'description' => $description,
                    'category_id' => $categoryIds[$categorySlug] ?? null,
                    'user_id' => null,
                    'is_premium' => false,
                    'is_active' => true,
                    'tags' => $tags,
                    'style' => self::styleForSvg($svgPath),
                    'file_svg' => $svgPath,
                    'file_png' => $pngPath,
                ]
            );

            $this->seedFile($icon->id, $slug . '.svg', $svgPath, 'svg', $svgSize, null);
            $this->seedFile($icon->id, $slug . '.png', $pngPath, 'png', $pngSize, $dimensions);
        }
    }

    /**
     * Derive an icon's style from its own artwork, reading the root <svg>
     * element only: artwork declaring fill="none" together with a stroke is
     * line art (outline), everything else paints with fills (solid).
     *
     * Nested elements are deliberately ignored — several flags carry a
     * stroked detail path inside otherwise filled artwork and would be
     * misread as outlines if inner nodes counted. Artwork missing from disk
     * falls back to 'outline', which is also the column default.
     *
     * Kept in step with the add_style_to_icons_table migration's backfill.
     */
    private static function styleForSvg(string $relativePath): string
    {
        $absolute = public_path($relativePath);

        if (!File::exists($absolute)) {
            return 'outline';
        }

        // The root element is always in the first bytes of the document.
        $head = (string) file_get_contents($absolute, false, null, 0, 2048);

        if ($head === '' || !preg_match('/<svg\b[^>]*>/i', $head, $match)) {
            return 'outline';
        }

        $root = $match[0];

        $hasNoFill = (bool) preg_match('/\bfill\s*=\s*(["\'])\s*none\s*\1/i', $root);
        $hasStroke = (bool) preg_match('/\bstroke\s*=\s*["\']/i', $root);

        return ($hasNoFill && $hasStroke) ? 'outline' : 'solid';
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
            ['Flags & Countries', 'flags-and-countries', 'National flags of the world, drawn 4:3 and available as SVG and PNG.'],
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
            ['flag-kurdistan', 'Flag Kurdistan', 'flags-and-countries', 1088, 10063, ['flag', 'community', 'people', 'ethnic', 'kurdistan'], '512x384', 'Red / white / green bands with the 21-rayed golden sun (Roj). Vector SVG and 512x384 PNG.'],
            ['flag-druze', 'Flag Druze', 'flags-and-countries', 313, 1566, ['flag', 'community', 'people', 'ethnic', 'druze'], '512x384', 'The five-colour star: green, red, yellow, blue, white. Vector SVG and 512x384 PNG.'],
            ['flag-alawite', 'Flag Alawite', 'flags-and-countries', 327, 5229, ['flag', 'community', 'people', 'ethnic', 'alawite'], '512x384', 'Historic Alawite State flag: white field between yellow bands with a red crescent. Vector SVG and 512x384 PNG.'],
            ['flag-assyrian', 'Flag Assyrian', 'flags-and-countries', 667, 14184, ['flag', 'community', 'people', 'ethnic', 'assyrian'], '512x384', 'Four-pointed star with rays in red, white and blue. Vector SVG and 512x384 PNG.'],
            ['flag-syriac', 'Flag Syriac', 'flags-and-countries', 1430, 10691, ['flag', 'community', 'people', 'ethnic', 'syriac'], '512x384', 'Red field with a golden radiant sun and four stars. The real emblem is a winged sun; this is a simplified radiant form. Vector SVG and 512x384 PNG.'],
            ['flag-chaldean', 'Flag Chaldean', 'flags-and-countries', 539, 6468, ['flag', 'community', 'people', 'ethnic', 'chaldean'], '512x384', 'White field with a blue and gold star emblem. Vector SVG and 512x384 PNG.'],
            ['flag-yazidi', 'Flag Yazidi', 'flags-and-countries', 1043, 13250, ['flag', 'community', 'people', 'ethnic', 'yazidi'], '512x384', 'White field, red sun disc with 21 rays and the peacock emblem simplified. Vector SVG and 512x384 PNG.'],
            ['flag-amazigh', 'Flag Amazigh', 'flags-and-countries', 447, 4841, ['flag', 'community', 'people', 'ethnic', 'amazigh'], '512x384', 'Blue / green / yellow bands with the red yaz (ⵣ) character. Vector SVG and 512x384 PNG.'],
            ['flag-turkmen', 'Flag Iraqi Turkmen', 'flags-and-countries', 438, 4303, ['flag', 'community', 'people', 'ethnic', 'iraqi', 'turkmen'], '512x384', 'Light blue field, white band, red crescent and star. Vector SVG and 512x384 PNG.'],
            ['flag-chechen', 'Flag Chechen', 'flags-and-countries', 350, 2006, ['flag', 'community', 'people', 'ethnic', 'chechen'], '512x384', 'Green / white / red bands with a gold ornamental stripe at the hoist. Vector SVG and 512x384 PNG.'],
            ['flag-kurdistan-region-iraq', 'Flag Kurdistan Region', 'flags-and-countries', 1095, 10063, ['flag', 'region', 'autonomous', 'territory', 'kurdistan'], '512x384', 'Same design as the Kurdish flag, used by the Kurdistan Region of Iraq. Vector SVG and 512x384 PNG.'],
            ['flag-rojava', 'Flag Rojava', 'flags-and-countries', 386, 3440, ['flag', 'region', 'autonomous', 'territory', 'rojava'], '512x384', 'Yellow / red / green bands with a green star. Vector SVG and 512x384 PNG.'],
            ['flag-scotland', 'Flag Scotland', 'flags-and-countries', 208, 3777, ['flag', 'region', 'autonomous', 'territory', 'scotland'], '512x384', 'The Saltire: white diagonal cross on blue. Vector SVG and 512x384 PNG.'],
            ['flag-wales', 'Flag Wales', 'flags-and-countries', 639, 6952, ['flag', 'region', 'autonomous', 'territory', 'wales'], '512x384', 'White over green. The Welsh dragon is highly detailed; this is a simplified silhouette, not an exact reproduction. Vector SVG and 512x384 PNG.'],
            ['flag-catalonia', 'Flag Catalonia', 'flags-and-countries', 231, 1472, ['flag', 'region', 'autonomous', 'territory', 'catalonia'], '512x384', 'The Senyera: four red stripes on gold. Vector SVG and 512x384 PNG.'],
            ['flag-basque', 'Flag Basque Country', 'flags-and-countries', 297, 4578, ['flag', 'region', 'autonomous', 'territory', 'basque', 'country'], '512x384', 'The Ikurrina: red field, green saltire, white cross. Vector SVG and 512x384 PNG.'],
            ['flag-hong-kong', 'Flag Hong Kong', 'flags-and-countries', 905, 7249, ['flag', 'region', 'autonomous', 'territory', 'hong', 'kong'], '512x384', 'Red field with a stylised five-petal bauhinia. Vector SVG and 512x384 PNG.'],
            ['flag-united-nations', 'Flag United Nations', 'flags-and-countries', 535, 14052, ['flag', 'organisation', 'international', 'united', 'nations'], '512x384', 'UN blue with the world map and olive wreath, simplified. Vector SVG and 512x384 PNG.'],
            ['flag-european-union', 'Flag European Union', 'flags-and-countries', 2026, 7936, ['flag', 'organisation', 'international', 'european', 'union'], '512x384', 'Twelve gold stars in a circle on blue. Vector SVG and 512x384 PNG.'],
            ['flag-arab-league', 'Flag Arab League', 'flags-and-countries', 347, 10590, ['flag', 'organisation', 'international', 'arab', 'league'], '512x384', 'Green field with a chain wreath and crescent, simplified. Vector SVG and 512x384 PNG.'],
            ['flag-nato', 'Flag NATO', 'flags-and-countries', 418, 9190, ['flag', 'organisation', 'international', 'nato'], '512x384', 'Dark blue field with the white compass rose. Vector SVG and 512x384 PNG.'],
            ['flag-red-cross', 'Flag Red Cross', 'flags-and-countries', 235, 2127, ['flag', 'organisation', 'international', 'red', 'cross'], '512x384', 'White field with a red Greek cross. Vector SVG and 512x384 PNG.'],
            ['flag-olympic', 'Flag Olympic', 'flags-and-countries', 453, 16936, ['flag', 'organisation', 'international', 'olympic'], '512x384', 'Five interlocking rings on white. Vector SVG and 512x384 PNG.'],
            ['flag-racing-checkered', 'Flag Racing Checkered', 'flags-and-countries', 604, 4124, ['flag', 'signal', 'marker', 'racing', 'checkered'], '512x384', 'The chequered finish flag. Vector SVG and 512x384 PNG.'],
            ['flag-white', 'Flag White', 'flags-and-countries', 207, 1517, ['flag', 'signal', 'marker', 'white'], '512x384', 'Plain white flag (truce / surrender). Vector SVG and 512x384 PNG.'],
            ['flag-pirate', 'Flag Pirate', 'flags-and-countries', 541, 8687, ['flag', 'signal', 'marker', 'pirate'], '512x384', 'Jolly Roger: skull and crossbones on black. Vector SVG and 512x384 PNG.'],
            ['flag-triangular', 'Flag Triangular', 'flags-and-countries', 193, 1856, ['flag', 'signal', 'marker', 'triangular'], '512x384', 'Generic pennant shape for map and UI markers. Vector SVG and 512x384 PNG.'],
            ['flag-afghanistan', 'Flag Afghanistan', 'flags-and-countries', 19189, 32835, ['flag', 'country', 'nation', 'afghanistan', 'af'], '512x384', 'Flag of Afghanistan. Vector SVG and 512x384 PNG.'],
            ['flag-albania', 'Flag Albania', 'flags-and-countries', 3123, 13104, ['flag', 'country', 'nation', 'albania', 'al'], '512x384', 'Flag of Albania. Vector SVG and 512x384 PNG.'],
            ['flag-algeria', 'Flag Algeria', 'flags-and-countries', 291, 8172, ['flag', 'country', 'nation', 'algeria', 'dz'], '512x384', 'Flag of Algeria. Vector SVG and 512x384 PNG.'],
            ['flag-andorra', 'Flag Andorra', 'flags-and-countries', 30507, 24944, ['flag', 'country', 'nation', 'andorra', 'ad'], '512x384', 'Flag of Andorra. Vector SVG and 512x384 PNG.'],
            ['flag-angola', 'Flag Angola', 'flags-and-countries', 1599, 8937, ['flag', 'country', 'nation', 'angola', 'ao'], '512x384', 'Flag of Angola. Vector SVG and 512x384 PNG.'],
            ['flag-antigua-and-barbuda', 'Flag Antigua and Barbuda', 'flags-and-countries', 740, 7820, ['flag', 'country', 'nation', 'antigua and barbuda', 'ag'], '512x384', 'Flag of Antigua and Barbuda. Vector SVG and 512x384 PNG.'],
            ['flag-argentina', 'Flag Argentina', 'flags-and-countries', 3458, 18013, ['flag', 'country', 'nation', 'argentina', 'ar'], '512x384', 'Flag of Argentina. Vector SVG and 512x384 PNG.'],
            ['flag-armenia', 'Flag Armenia', 'flags-and-countries', 225, 1417, ['flag', 'country', 'nation', 'armenia', 'am'], '512x384', 'Flag of Armenia. Vector SVG and 512x384 PNG.'],
            ['flag-australia', 'Flag Australia', 'flags-and-countries', 1293, 15375, ['flag', 'country', 'nation', 'australia', 'au'], '512x384', 'Flag of Australia. Vector SVG and 512x384 PNG.'],
            ['flag-austria', 'Flag Austria', 'flags-and-countries', 192, 1571, ['flag', 'country', 'nation', 'austria', 'at'], '512x384', 'Flag of Austria. Vector SVG and 512x384 PNG.'],
            ['flag-azerbaijan', 'Flag Azerbaijan', 'flags-and-countries', 498, 5490, ['flag', 'country', 'nation', 'azerbaijan', 'az'], '512x384', 'Flag of Azerbaijan. Vector SVG and 512x384 PNG.'],
            ['flag-bahamas', 'Flag Bahamas', 'flags-and-countries', 554, 6210, ['flag', 'country', 'nation', 'bahamas', 'bs'], '512x384', 'Flag of Bahamas. Vector SVG and 512x384 PNG.'],
            ['flag-bahrain', 'Flag Bahrain', 'flags-and-countries', 249, 4934, ['flag', 'country', 'nation', 'bahrain', 'bh'], '512x384', 'Flag of Bahrain. Vector SVG and 512x384 PNG.'],
            ['flag-bangladesh', 'Flag Bangladesh', 'flags-and-countries', 184, 5657, ['flag', 'country', 'nation', 'bangladesh', 'bd'], '512x384', 'Flag of Bangladesh. Vector SVG and 512x384 PNG.'],
            ['flag-barbados', 'Flag Barbados', 'flags-and-countries', 625, 6461, ['flag', 'country', 'nation', 'barbados', 'bb'], '512x384', 'Flag of Barbados. Vector SVG and 512x384 PNG.'],
            ['flag-belarus', 'Flag Belarus', 'flags-and-countries', 2075, 8784, ['flag', 'country', 'nation', 'belarus', 'by'], '512x384', 'Flag of Belarus. Vector SVG and 512x384 PNG.'],
            ['flag-belgium', 'Flag Belgium', 'flags-and-countries', 299, 2522, ['flag', 'country', 'nation', 'belgium', 'be'], '512x384', 'Flag of Belgium. Vector SVG and 512x384 PNG.'],
            ['flag-belize', 'Flag Belize', 'flags-and-countries', 42502, 69703, ['flag', 'country', 'nation', 'belize', 'bz'], '512x384', 'Flag of Belize. Vector SVG and 512x384 PNG.'],
            ['flag-benin', 'Flag Benin', 'flags-and-countries', 496, 1987, ['flag', 'country', 'nation', 'benin', 'bj'], '512x384', 'Flag of Benin. Vector SVG and 512x384 PNG.'],
            ['flag-bhutan', 'Flag Bhutan', 'flags-and-countries', 24781, 47041, ['flag', 'country', 'nation', 'bhutan', 'bt'], '512x384', 'Flag of Bhutan. Vector SVG and 512x384 PNG.'],
            ['flag-bolivia', 'Flag Bolivia', 'flags-and-countries', 102877, 20199, ['flag', 'country', 'nation', 'bolivia', 'bo'], '512x384', 'Flag of Bolivia. Vector SVG and 512x384 PNG.'],
            ['flag-bosnia-and-herzegovina', 'Flag Bosnia and Herzegovina', 'flags-and-countries', 1237, 12894, ['flag', 'country', 'nation', 'bosnia and herzegovina', 'ba'], '512x384', 'Flag of Bosnia and Herzegovina. Vector SVG and 512x384 PNG.'],
            ['flag-botswana', 'Flag Botswana', 'flags-and-countries', 261, 1488, ['flag', 'country', 'nation', 'botswana', 'bw'], '512x384', 'Flag of Botswana. Vector SVG and 512x384 PNG.'],
            ['flag-brazil', 'Flag Brazil', 'flags-and-countries', 7137, 18680, ['flag', 'country', 'nation', 'brazil', 'br'], '512x384', 'Flag of Brazil. Vector SVG and 512x384 PNG.'],
            ['flag-brunei', 'Flag Brunei', 'flags-and-countries', 13337, 23267, ['flag', 'country', 'nation', 'brunei', 'bn'], '512x384', 'Flag of Brunei. Vector SVG and 512x384 PNG.'],
            ['flag-bulgaria', 'Flag Bulgaria', 'flags-and-countries', 222, 1572, ['flag', 'country', 'nation', 'bulgaria', 'bg'], '512x384', 'Flag of Bulgaria. Vector SVG and 512x384 PNG.'],
            ['flag-burkina-faso', 'Flag Burkina Faso', 'flags-and-countries', 350, 5728, ['flag', 'country', 'nation', 'burkina faso', 'bf'], '512x384', 'Flag of Burkina Faso. Vector SVG and 512x384 PNG.'],
            ['flag-burundi', 'Flag Burundi', 'flags-and-countries', 1075, 15964, ['flag', 'country', 'nation', 'burundi', 'bi'], '512x384', 'Flag of Burundi. Vector SVG and 512x384 PNG.'],
            ['flag-cambodia', 'Flag Cambodia', 'flags-and-countries', 7119, 17800, ['flag', 'country', 'nation', 'cambodia', 'kh'], '512x384', 'Flag of Cambodia. Vector SVG and 512x384 PNG.'],
            ['flag-cameroon', 'Flag Cameroon', 'flags-and-countries', 837, 4214, ['flag', 'country', 'nation', 'cameroon', 'cm'], '512x384', 'Flag of Cameroon. Vector SVG and 512x384 PNG.'],
            ['flag-canada', 'Flag Canada', 'flags-and-countries', 622, 7850, ['flag', 'country', 'nation', 'canada', 'ca'], '512x384', 'Flag of Canada. Vector SVG and 512x384 PNG.'],
            ['flag-cape-verde', 'Flag Cape Verde', 'flags-and-countries', 1408, 8748, ['flag', 'country', 'nation', 'cape verde', 'cv'], '512x384', 'Flag of Cape Verde. Vector SVG and 512x384 PNG.'],
            ['flag-central-african-republic', 'Flag Central African Republic', 'flags-and-countries', 684, 4451, ['flag', 'country', 'nation', 'central african republic', 'cf'], '512x384', 'Flag of Central African Republic. Vector SVG and 512x384 PNG.'],
            ['flag-chad', 'Flag Chad', 'flags-and-countries', 268, 2512, ['flag', 'country', 'nation', 'chad', 'td'], '512x384', 'Flag of Chad. Vector SVG and 512x384 PNG.'],
            ['flag-chile', 'Flag Chile', 'flags-and-countries', 549, 3753, ['flag', 'country', 'nation', 'chile', 'cl'], '512x384', 'Flag of Chile. Vector SVG and 512x384 PNG.'],
            ['flag-china', 'Flag China', 'flags-and-countries', 810, 5343, ['flag', 'country', 'nation', 'china', 'cn'], '512x384', 'Flag of China. Vector SVG and 512x384 PNG.'],
            ['flag-colombia', 'Flag Colombia', 'flags-and-countries', 283, 1415, ['flag', 'country', 'nation', 'colombia', 'co'], '512x384', 'Flag of Colombia. Vector SVG and 512x384 PNG.'],
            ['flag-comoros', 'Flag Comoros', 'flags-and-countries', 1050, 9445, ['flag', 'country', 'nation', 'comoros', 'km'], '512x384', 'Flag of Comoros. Vector SVG and 512x384 PNG.'],
            ['flag-costa-rica', 'Flag Costa Rica', 'flags-and-countries', 287, 1595, ['flag', 'country', 'nation', 'costa rica', 'cr'], '512x384', 'Flag of Costa Rica. Vector SVG and 512x384 PNG.'],
            ['flag-croatia', 'Flag Croatia', 'flags-and-countries', 30729, 16318, ['flag', 'country', 'nation', 'croatia', 'hr'], '512x384', 'Flag of Croatia. Vector SVG and 512x384 PNG.'],
            ['flag-cuba', 'Flag Cuba', 'flags-and-countries', 607, 7697, ['flag', 'country', 'nation', 'cuba', 'cu'], '512x384', 'Flag of Cuba. Vector SVG and 512x384 PNG.'],
            ['flag-cyprus', 'Flag Cyprus', 'flags-and-countries', 5463, 12584, ['flag', 'country', 'nation', 'cyprus', 'cy'], '512x384', 'Flag of Cyprus. Vector SVG and 512x384 PNG.'],
            ['flag-czechia', 'Flag Czechia', 'flags-and-countries', 222, 2381, ['flag', 'country', 'nation', 'czechia', 'cz'], '512x384', 'Flag of Czechia. Vector SVG and 512x384 PNG.'],
            ['flag-denmark', 'Flag Denmark', 'flags-and-countries', 233, 2407, ['flag', 'country', 'nation', 'denmark', 'dk'], '512x384', 'Flag of Denmark. Vector SVG and 512x384 PNG.'],
            ['flag-djibouti', 'Flag Djibouti', 'flags-and-countries', 582, 4178, ['flag', 'country', 'nation', 'djibouti', 'dj'], '512x384', 'Flag of Djibouti. Vector SVG and 512x384 PNG.'],
            ['flag-dominica', 'Flag Dominica', 'flags-and-countries', 15784, 22877, ['flag', 'country', 'nation', 'dominica', 'dm'], '512x384', 'Flag of Dominica. Vector SVG and 512x384 PNG.'],
            ['flag-dominican-republic', 'Flag Dominican Republic', 'flags-and-countries', 40343, 14922, ['flag', 'country', 'nation', 'dominican republic', 'do'], '512x384', 'Flag of Dominican Republic. Vector SVG and 512x384 PNG.'],
            ['flag-dr-congo', 'Flag DR Congo', 'flags-and-countries', 331, 5603, ['flag', 'country', 'nation', 'dr congo', 'cd'], '512x384', 'Flag of DR Congo. Vector SVG and 512x384 PNG.'],
            ['flag-ecuador', 'Flag Ecuador', 'flags-and-countries', 28774, 52668, ['flag', 'country', 'nation', 'ecuador', 'ec'], '512x384', 'Flag of Ecuador. Vector SVG and 512x384 PNG.'],
            ['flag-egypt', 'Flag Egypt', 'flags-and-countries', 8723, 14616, ['flag', 'country', 'nation', 'egypt', 'eg'], '512x384', 'Flag of Egypt. Vector SVG and 512x384 PNG.'],
            ['flag-el-salvador', 'Flag El Salvador', 'flags-and-countries', 77270, 21924, ['flag', 'country', 'nation', 'el salvador', 'sv'], '512x384', 'Flag of El Salvador. Vector SVG and 512x384 PNG.'],
            ['flag-equatorial-guinea', 'Flag Equatorial Guinea', 'flags-and-countries', 4938, 10949, ['flag', 'country', 'nation', 'equatorial guinea', 'gq'], '512x384', 'Flag of Equatorial Guinea. Vector SVG and 512x384 PNG.'],
            ['flag-eritrea', 'Flag Eritrea', 'flags-and-countries', 3155, 18622, ['flag', 'country', 'nation', 'eritrea', 'er'], '512x384', 'Flag of Eritrea. Vector SVG and 512x384 PNG.'],
            ['flag-estonia', 'Flag Estonia', 'flags-and-countries', 222, 1566, ['flag', 'country', 'nation', 'estonia', 'ee'], '512x384', 'Flag of Estonia. Vector SVG and 512x384 PNG.'],
            ['flag-eswatini', 'Flag Eswatini', 'flags-and-countries', 4660, 16798, ['flag', 'country', 'nation', 'eswatini', 'sz'], '512x384', 'Flag of Eswatini. Vector SVG and 512x384 PNG.'],
            ['flag-ethiopia', 'Flag Ethiopia', 'flags-and-countries', 1142, 10292, ['flag', 'country', 'nation', 'ethiopia', 'et'], '512x384', 'Flag of Ethiopia. Vector SVG and 512x384 PNG.'],
            ['flag-fiji', 'Flag Fiji', 'flags-and-countries', 24097, 27092, ['flag', 'country', 'nation', 'fiji', 'fj'], '512x384', 'Flag of Fiji. Vector SVG and 512x384 PNG.'],
            ['flag-finland', 'Flag Finland', 'flags-and-countries', 231, 2530, ['flag', 'country', 'nation', 'finland', 'fi'], '512x384', 'Flag of Finland. Vector SVG and 512x384 PNG.'],
            ['flag-france', 'Flag France', 'flags-and-countries', 228, 2658, ['flag', 'country', 'nation', 'france', 'fr'], '512x384', 'Flag of France. Vector SVG and 512x384 PNG.'],
            ['flag-gabon', 'Flag Gabon', 'flags-and-countries', 268, 1416, ['flag', 'country', 'nation', 'gabon', 'ga'], '512x384', 'Flag of Gabon. Vector SVG and 512x384 PNG.'],
            ['flag-gambia', 'Flag Gambia', 'flags-and-countries', 540, 1535, ['flag', 'country', 'nation', 'gambia', 'gm'], '512x384', 'Flag of Gambia. Vector SVG and 512x384 PNG.'],
            ['flag-georgia', 'Flag Georgia', 'flags-and-countries', 1310, 7200, ['flag', 'country', 'nation', 'georgia', 'ge'], '512x384', 'Flag of Georgia. Vector SVG and 512x384 PNG.'],
            ['flag-germany', 'Flag Germany', 'flags-and-countries', 218, 1408, ['flag', 'country', 'nation', 'germany', 'de'], '512x384', 'Flag of Germany. Vector SVG and 512x384 PNG.'],
            ['flag-ghana', 'Flag Ghana', 'flags-and-countries', 290, 3553, ['flag', 'country', 'nation', 'ghana', 'gh'], '512x384', 'Flag of Ghana. Vector SVG and 512x384 PNG.'],
            ['flag-greece', 'Flag Greece', 'flags-and-countries', 865, 2328, ['flag', 'country', 'nation', 'greece', 'gr'], '512x384', 'Flag of Greece. Vector SVG and 512x384 PNG.'],
            ['flag-grenada', 'Flag Grenada', 'flags-and-countries', 1709, 15113, ['flag', 'country', 'nation', 'grenada', 'gd'], '512x384', 'Flag of Grenada. Vector SVG and 512x384 PNG.'],
            ['flag-guatemala', 'Flag Guatemala', 'flags-and-countries', 31349, 26199, ['flag', 'country', 'nation', 'guatemala', 'gt'], '512x384', 'Flag of Guatemala. Vector SVG and 512x384 PNG.'],
            ['flag-guinea', 'Flag Guinea', 'flags-and-countries', 289, 2502, ['flag', 'country', 'nation', 'guinea', 'gn'], '512x384', 'Flag of Guinea. Vector SVG and 512x384 PNG.'],
            ['flag-guinea-bissau', 'Flag Guinea-Bissau', 'flags-and-countries', 843, 4034, ['flag', 'country', 'nation', 'guinea-bissau', 'gw'], '512x384', 'Flag of Guinea-Bissau. Vector SVG and 512x384 PNG.'],
            ['flag-guyana', 'Flag Guyana', 'flags-and-countries', 490, 12924, ['flag', 'country', 'nation', 'guyana', 'gy'], '512x384', 'Flag of Guyana. Vector SVG and 512x384 PNG.'],
            ['flag-haiti', 'Flag Haiti', 'flags-and-countries', 13298, 18839, ['flag', 'country', 'nation', 'haiti', 'ht'], '512x384', 'Flag of Haiti. Vector SVG and 512x384 PNG.'],
            ['flag-honduras', 'Flag Honduras', 'flags-and-countries', 1141, 5388, ['flag', 'country', 'nation', 'honduras', 'hn'], '512x384', 'Flag of Honduras. Vector SVG and 512x384 PNG.'],
            ['flag-hungary', 'Flag Hungary', 'flags-and-countries', 268, 1585, ['flag', 'country', 'nation', 'hungary', 'hu'], '512x384', 'Flag of Hungary. Vector SVG and 512x384 PNG.'],
            ['flag-iceland', 'Flag Iceland', 'flags-and-countries', 506, 2631, ['flag', 'country', 'nation', 'iceland', 'is'], '512x384', 'Flag of Iceland. Vector SVG and 512x384 PNG.'],
            ['flag-india', 'Flag India', 'flags-and-countries', 1087, 10252, ['flag', 'country', 'nation', 'india', 'in'], '512x384', 'Flag of India. Vector SVG and 512x384 PNG.'],
            ['flag-indonesia', 'Flag Indonesia', 'flags-and-countries', 175, 1620, ['flag', 'country', 'nation', 'indonesia', 'id'], '512x384', 'Flag of Indonesia. Vector SVG and 512x384 PNG.'],
            ['flag-iran', 'Flag Iran', 'flags-and-countries', 15394, 15761, ['flag', 'country', 'nation', 'iran', 'ir'], '512x384', 'Flag of Iran. Vector SVG and 512x384 PNG.'],
            ['flag-iraq', 'Flag Iraq', 'flags-and-countries', 1417, 5175, ['flag', 'country', 'nation', 'iraq', 'iq'], '512x384', 'Flag of Iraq. Vector SVG and 512x384 PNG.'],
            ['flag-ireland', 'Flag Ireland', 'flags-and-countries', 286, 2651, ['flag', 'country', 'nation', 'ireland', 'ie'], '512x384', 'Flag of Ireland. Vector SVG and 512x384 PNG.'],
            ['flag-israel', 'Flag Israel', 'flags-and-countries', 831, 7898, ['flag', 'country', 'nation', 'israel', 'il'], '512x384', 'Flag of Israel. Vector SVG and 512x384 PNG.'],
            ['flag-italy', 'Flag Italy', 'flags-and-countries', 286, 2665, ['flag', 'country', 'nation', 'italy', 'it'], '512x384', 'Flag of Italy. Vector SVG and 512x384 PNG.'],
            ['flag-ivory-coast', 'Flag Ivory Coast', 'flags-and-countries', 274, 2656, ['flag', 'country', 'nation', 'ivory coast', 'ci'], '512x384', 'Flag of Ivory Coast. Vector SVG and 512x384 PNG.'],
            ['flag-jamaica', 'Flag Jamaica', 'flags-and-countries', 398, 3897, ['flag', 'country', 'nation', 'jamaica', 'jm'], '512x384', 'Flag of Jamaica. Vector SVG and 512x384 PNG.'],
            ['flag-japan', 'Flag Japan', 'flags-and-countries', 467, 5987, ['flag', 'country', 'nation', 'japan', 'jp'], '512x384', 'Flag of Japan. Vector SVG and 512x384 PNG.'],
            ['flag-jordan', 'Flag Jordan', 'flags-and-countries', 724, 3368, ['flag', 'country', 'nation', 'jordan', 'jo'], '512x384', 'Flag of Jordan. Vector SVG and 512x384 PNG.'],
            ['flag-kazakhstan', 'Flag Kazakhstan', 'flags-and-countries', 7091, 31692, ['flag', 'country', 'nation', 'kazakhstan', 'kz'], '512x384', 'Flag of Kazakhstan. Vector SVG and 512x384 PNG.'],
            ['flag-kenya', 'Flag Kenya', 'flags-and-countries', 1418, 12142, ['flag', 'country', 'nation', 'kenya', 'ke'], '512x384', 'Flag of Kenya. Vector SVG and 512x384 PNG.'],
            ['flag-kiribati', 'Flag Kiribati', 'flags-and-countries', 5624, 27417, ['flag', 'country', 'nation', 'kiribati', 'ki'], '512x384', 'Flag of Kiribati. Vector SVG and 512x384 PNG.'],
            ['flag-kuwait', 'Flag Kuwait', 'flags-and-countries', 514, 3272, ['flag', 'country', 'nation', 'kuwait', 'kw'], '512x384', 'Flag of Kuwait. Vector SVG and 512x384 PNG.'],
            ['flag-kyrgyzstan', 'Flag Kyrgyzstan', 'flags-and-countries', 4740, 20837, ['flag', 'country', 'nation', 'kyrgyzstan', 'kg'], '512x384', 'Flag of Kyrgyzstan. Vector SVG and 512x384 PNG.'],
            ['flag-laos', 'Flag Laos', 'flags-and-countries', 453, 4489, ['flag', 'country', 'nation', 'laos', 'la'], '512x384', 'Flag of Laos. Vector SVG and 512x384 PNG.'],
            ['flag-latvia', 'Flag Latvia', 'flags-and-countries', 227, 1537, ['flag', 'country', 'nation', 'latvia', 'lv'], '512x384', 'Flag of Latvia. Vector SVG and 512x384 PNG.'],
            ['flag-lebanon', 'Flag Lebanon', 'flags-and-countries', 2767, 10725, ['flag', 'country', 'nation', 'lebanon', 'lb'], '512x384', 'Flag of Lebanon. Vector SVG and 512x384 PNG.'],
            ['flag-lesotho', 'Flag Lesotho', 'flags-and-countries', 1130, 7329, ['flag', 'country', 'nation', 'lesotho', 'ls'], '512x384', 'Flag of Lesotho. Vector SVG and 512x384 PNG.'],
            ['flag-liberia', 'Flag Liberia', 'flags-and-countries', 710, 4070, ['flag', 'country', 'nation', 'liberia', 'lr'], '512x384', 'Flag of Liberia. Vector SVG and 512x384 PNG.'],
            ['flag-libya', 'Flag Libya', 'flags-and-countries', 546, 4406, ['flag', 'country', 'nation', 'libya', 'ly'], '512x384', 'Flag of Libya. Vector SVG and 512x384 PNG.'],
            ['flag-liechtenstein', 'Flag Liechtenstein', 'flags-and-countries', 7315, 14485, ['flag', 'country', 'nation', 'liechtenstein', 'li'], '512x384', 'Flag of Liechtenstein. Vector SVG and 512x384 PNG.'],
            ['flag-lithuania', 'Flag Lithuania', 'flags-and-countries', 436, 1431, ['flag', 'country', 'nation', 'lithuania', 'lt'], '512x384', 'Flag of Lithuania. Vector SVG and 512x384 PNG.'],
            ['flag-luxembourg', 'Flag Luxembourg', 'flags-and-countries', 222, 1573, ['flag', 'country', 'nation', 'luxembourg', 'lu'], '512x384', 'Flag of Luxembourg. Vector SVG and 512x384 PNG.'],
            ['flag-madagascar', 'Flag Madagascar', 'flags-and-countries', 296, 2129, ['flag', 'country', 'nation', 'madagascar', 'mg'], '512x384', 'Flag of Madagascar. Vector SVG and 512x384 PNG.'],
            ['flag-malawi', 'Flag Malawi', 'flags-and-countries', 3560, 12793, ['flag', 'country', 'nation', 'malawi', 'mw'], '512x384', 'Flag of Malawi. Vector SVG and 512x384 PNG.'],
            ['flag-malaysia', 'Flag Malaysia', 'flags-and-countries', 1375, 8961, ['flag', 'country', 'nation', 'malaysia', 'my'], '512x384', 'Flag of Malaysia. Vector SVG and 512x384 PNG.'],
            ['flag-maldives', 'Flag Maldives', 'flags-and-countries', 283, 4149, ['flag', 'country', 'nation', 'maldives', 'mv'], '512x384', 'Flag of Maldives. Vector SVG and 512x384 PNG.'],
            ['flag-mali', 'Flag Mali', 'flags-and-countries', 270, 2497, ['flag', 'country', 'nation', 'mali', 'ml'], '512x384', 'Flag of Mali. Vector SVG and 512x384 PNG.'],
            ['flag-malta', 'Flag Malta', 'flags-and-countries', 13922, 12864, ['flag', 'country', 'nation', 'malta', 'mt'], '512x384', 'Flag of Malta. Vector SVG and 512x384 PNG.'],
            ['flag-marshall-islands', 'Flag Marshall Islands', 'flags-and-countries', 726, 21745, ['flag', 'country', 'nation', 'marshall islands', 'mh'], '512x384', 'Flag of Marshall Islands. Vector SVG and 512x384 PNG.'],
            ['flag-mauritania', 'Flag Mauritania', 'flags-and-countries', 434, 7536, ['flag', 'country', 'nation', 'mauritania', 'mr'], '512x384', 'Flag of Mauritania. Vector SVG and 512x384 PNG.'],
            ['flag-mauritius', 'Flag Mauritius', 'flags-and-countries', 313, 1427, ['flag', 'country', 'nation', 'mauritius', 'mu'], '512x384', 'Flag of Mauritius. Vector SVG and 512x384 PNG.'],
            ['flag-mexico', 'Flag Mexico', 'flags-and-countries', 84750, 28584, ['flag', 'country', 'nation', 'mexico', 'mx'], '512x384', 'Flag of Mexico. Vector SVG and 512x384 PNG.'],
            ['flag-micronesia', 'Flag Micronesia', 'flags-and-countries', 768, 6652, ['flag', 'country', 'nation', 'micronesia', 'fm'], '512x384', 'Flag of Micronesia. Vector SVG and 512x384 PNG.'],
            ['flag-moldova', 'Flag Moldova', 'flags-and-countries', 11123, 32801, ['flag', 'country', 'nation', 'moldova', 'md'], '512x384', 'Flag of Moldova. Vector SVG and 512x384 PNG.'],
            ['flag-monaco', 'Flag Monaco', 'flags-and-countries', 231, 1621, ['flag', 'country', 'nation', 'monaco', 'mc'], '512x384', 'Flag of Monaco. Vector SVG and 512x384 PNG.'],
            ['flag-mongolia', 'Flag Mongolia', 'flags-and-countries', 1394, 8318, ['flag', 'country', 'nation', 'mongolia', 'mn'], '512x384', 'Flag of Mongolia. Vector SVG and 512x384 PNG.'],
            ['flag-montenegro', 'Flag Montenegro', 'flags-and-countries', 56369, 42002, ['flag', 'country', 'nation', 'montenegro', 'me'], '512x384', 'Flag of Montenegro. Vector SVG and 512x384 PNG.'],
            ['flag-morocco', 'Flag Morocco', 'flags-and-countries', 244, 4486, ['flag', 'country', 'nation', 'morocco', 'ma'], '512x384', 'Flag of Morocco. Vector SVG and 512x384 PNG.'],
            ['flag-mozambique', 'Flag Mozambique', 'flags-and-countries', 2560, 11197, ['flag', 'country', 'nation', 'mozambique', 'mz'], '512x384', 'Flag of Mozambique. Vector SVG and 512x384 PNG.'],
            ['flag-myanmar', 'Flag Myanmar', 'flags-and-countries', 707, 5756, ['flag', 'country', 'nation', 'myanmar', 'mm'], '512x384', 'Flag of Myanmar. Vector SVG and 512x384 PNG.'],
            ['flag-namibia', 'Flag Namibia', 'flags-and-countries', 990, 15133, ['flag', 'country', 'nation', 'namibia', 'na'], '512x384', 'Flag of Namibia. Vector SVG and 512x384 PNG.'],
            ['flag-nauru', 'Flag Nauru', 'flags-and-countries', 643, 5318, ['flag', 'country', 'nation', 'nauru', 'nr'], '512x384', 'Flag of Nauru. Vector SVG and 512x384 PNG.'],
            ['flag-nepal', 'Flag Nepal', 'flags-and-countries', 1002, 13601, ['flag', 'country', 'nation', 'nepal', 'np'], '512x384', 'Flag of Nepal. Vector SVG and 512x384 PNG.'],
            ['flag-netherlands', 'Flag Netherlands', 'flags-and-countries', 222, 1575, ['flag', 'country', 'nation', 'netherlands', 'nl'], '512x384', 'Flag of Netherlands. Vector SVG and 512x384 PNG.'],
            ['flag-new-zealand', 'Flag New Zealand', 'flags-and-countries', 2169, 14061, ['flag', 'country', 'nation', 'new zealand', 'nz'], '512x384', 'Flag of New Zealand. Vector SVG and 512x384 PNG.'],
            ['flag-nicaragua', 'Flag Nicaragua', 'flags-and-countries', 17246, 15636, ['flag', 'country', 'nation', 'nicaragua', 'ni'], '512x384', 'Flag of Nicaragua. Vector SVG and 512x384 PNG.'],
            ['flag-niger', 'Flag Niger', 'flags-and-countries', 270, 3579, ['flag', 'country', 'nation', 'niger', 'ne'], '512x384', 'Flag of Niger. Vector SVG and 512x384 PNG.'],
            ['flag-nigeria', 'Flag Nigeria', 'flags-and-countries', 254, 2651, ['flag', 'country', 'nation', 'nigeria', 'ng'], '512x384', 'Flag of Nigeria. Vector SVG and 512x384 PNG.'],
            ['flag-north-korea', 'Flag North Korea', 'flags-and-countries', 780, 7268, ['flag', 'country', 'nation', 'north korea', 'kp'], '512x384', 'Flag of North Korea. Vector SVG and 512x384 PNG.'],
            ['flag-north-macedonia', 'Flag North Macedonia', 'flags-and-countries', 376, 12370, ['flag', 'country', 'nation', 'north macedonia', 'mk'], '512x384', 'Flag of North Macedonia. Vector SVG and 512x384 PNG.'],
            ['flag-norway', 'Flag Norway', 'flags-and-countries', 315, 2599, ['flag', 'country', 'nation', 'norway', 'no'], '512x384', 'Flag of Norway. Vector SVG and 512x384 PNG.'],
            ['flag-oman', 'Flag Oman', 'flags-and-countries', 22220, 10954, ['flag', 'country', 'nation', 'oman', 'om'], '512x384', 'Flag of Oman. Vector SVG and 512x384 PNG.'],
            ['flag-pakistan', 'Flag Pakistan', 'flags-and-countries', 722, 6958, ['flag', 'country', 'nation', 'pakistan', 'pk'], '512x384', 'Flag of Pakistan. Vector SVG and 512x384 PNG.'],
            ['flag-palau', 'Flag Palau', 'flags-and-countries', 463, 5425, ['flag', 'country', 'nation', 'palau', 'pw'], '512x384', 'Flag of Palau. Vector SVG and 512x384 PNG.'],
            ['flag-palestine', 'Flag Palestine', 'flags-and-countries', 273, 2405, ['flag', 'country', 'nation', 'palestine', 'ps'], '512x384', 'Flag of Palestine. Vector SVG and 512x384 PNG.'],
            ['flag-panama', 'Flag Panama', 'flags-and-countries', 741, 5883, ['flag', 'country', 'nation', 'panama', 'pa'], '512x384', 'Flag of Panama. Vector SVG and 512x384 PNG.'],
            ['flag-papua-new-guinea', 'Flag Papua New Guinea', 'flags-and-countries', 1605, 10848, ['flag', 'country', 'nation', 'papua new guinea', 'pg'], '512x384', 'Flag of Papua New Guinea. Vector SVG and 512x384 PNG.'],
            ['flag-paraguay', 'Flag Paraguay', 'flags-and-countries', 16181, 13889, ['flag', 'country', 'nation', 'paraguay', 'py'], '512x384', 'Flag of Paraguay. Vector SVG and 512x384 PNG.'],
            ['flag-peru', 'Flag Peru', 'flags-and-countries', 183, 2656, ['flag', 'country', 'nation', 'peru', 'pe'], '512x384', 'Flag of Peru. Vector SVG and 512x384 PNG.'],
            ['flag-philippines', 'Flag Philippines', 'flags-and-countries', 1379, 15242, ['flag', 'country', 'nation', 'philippines', 'ph'], '512x384', 'Flag of Philippines. Vector SVG and 512x384 PNG.'],
            ['flag-poland', 'Flag Poland', 'flags-and-countries', 216, 1622, ['flag', 'country', 'nation', 'poland', 'pl'], '512x384', 'Flag of Poland. Vector SVG and 512x384 PNG.'],
            ['flag-portugal', 'Flag Portugal', 'flags-and-countries', 7998, 25746, ['flag', 'country', 'nation', 'portugal', 'pt'], '512x384', 'Flag of Portugal. Vector SVG and 512x384 PNG.'],
            ['flag-qatar', 'Flag Qatar', 'flags-and-countries', 351, 4467, ['flag', 'country', 'nation', 'qatar', 'qa'], '512x384', 'Flag of Qatar. Vector SVG and 512x384 PNG.'],
            ['flag-republic-of-the-congo', 'Flag Republic of the Congo', 'flags-and-countries', 467, 2447, ['flag', 'country', 'nation', 'republic of the congo', 'cg'], '512x384', 'Flag of Republic of the Congo. Vector SVG and 512x384 PNG.'],
            ['flag-romania', 'Flag Romania', 'flags-and-countries', 299, 2523, ['flag', 'country', 'nation', 'romania', 'ro'], '512x384', 'Flag of Romania. Vector SVG and 512x384 PNG.'],
            ['flag-russia', 'Flag Russia', 'flags-and-countries', 222, 1572, ['flag', 'country', 'nation', 'russia', 'ru'], '512x384', 'Flag of Russia. Vector SVG and 512x384 PNG.'],
            ['flag-rwanda', 'Flag Rwanda', 'flags-and-countries', 752, 10162, ['flag', 'country', 'nation', 'rwanda', 'rw'], '512x384', 'Flag of Rwanda. Vector SVG and 512x384 PNG.'],
            ['flag-saint-kitts-and-nevis', 'Flag Saint Kitts and Nevis', 'flags-and-countries', 793, 12848, ['flag', 'country', 'nation', 'saint kitts and nevis', 'kn'], '512x384', 'Flag of Saint Kitts and Nevis. Vector SVG and 512x384 PNG.'],
            ['flag-saint-lucia', 'Flag Saint Lucia', 'flags-and-countries', 349, 11416, ['flag', 'country', 'nation', 'saint lucia', 'lc'], '512x384', 'Flag of Saint Lucia. Vector SVG and 512x384 PNG.'],
            ['flag-saint-vincent-and-the-grenadines', 'Flag Saint Vincent and the Grenadines', 'flags-and-countries', 426, 6624, ['flag', 'country', 'nation', 'saint vincent and the grenadines', 'vc'], '512x384', 'Flag of Saint Vincent and the Grenadines. Vector SVG and 512x384 PNG.'],
            ['flag-samoa', 'Flag Samoa', 'flags-and-countries', 684, 5806, ['flag', 'country', 'nation', 'samoa', 'ws'], '512x384', 'Flag of Samoa. Vector SVG and 512x384 PNG.'],
            ['flag-san-marino', 'Flag San Marino', 'flags-and-countries', 15680, 45265, ['flag', 'country', 'nation', 'san marino', 'sm'], '512x384', 'Flag of San Marino. Vector SVG and 512x384 PNG.'],
            ['flag-sao-tome-and-principe', 'Flag Sao Tome and Principe', 'flags-and-countries', 951, 5619, ['flag', 'country', 'nation', 'sao tome and principe', 'st'], '512x384', 'Flag of Sao Tome and Principe. Vector SVG and 512x384 PNG.'],
            ['flag-saudi-arabia', 'Flag Saudi Arabia', 'flags-and-countries', 9965, 19093, ['flag', 'country', 'nation', 'saudi arabia', 'sa'], '512x384', 'Flag of Saudi Arabia. Vector SVG and 512x384 PNG.'],
            ['flag-senegal', 'Flag Senegal', 'flags-and-countries', 418, 5308, ['flag', 'country', 'nation', 'senegal', 'sn'], '512x384', 'Flag of Senegal. Vector SVG and 512x384 PNG.'],
            ['flag-serbia', 'Flag Serbia', 'flags-and-countries', 181631, 43681, ['flag', 'country', 'nation', 'serbia', 'rs'], '512x384', 'Flag of Serbia. Vector SVG and 512x384 PNG.'],
            ['flag-seychelles', 'Flag Seychelles', 'flags-and-countries', 314, 4429, ['flag', 'country', 'nation', 'seychelles', 'sc'], '512x384', 'Flag of Seychelles. Vector SVG and 512x384 PNG.'],
            ['flag-sierra-leone', 'Flag Sierra Leone', 'flags-and-countries', 269, 1574, ['flag', 'country', 'nation', 'sierra leone', 'sl'], '512x384', 'Flag of Sierra Leone. Vector SVG and 512x384 PNG.'],
            ['flag-singapore', 'Flag Singapore', 'flags-and-countries', 886, 6853, ['flag', 'country', 'nation', 'singapore', 'sg'], '512x384', 'Flag of Singapore. Vector SVG and 512x384 PNG.'],
            ['flag-slovakia', 'Flag Slovakia', 'flags-and-countries', 1179, 9777, ['flag', 'country', 'nation', 'slovakia', 'sk'], '512x384', 'Flag of Slovakia. Vector SVG and 512x384 PNG.'],
            ['flag-slovenia', 'Flag Slovenia', 'flags-and-countries', 1990, 8626, ['flag', 'country', 'nation', 'slovenia', 'si'], '512x384', 'Flag of Slovenia. Vector SVG and 512x384 PNG.'],
            ['flag-solomon-islands', 'Flag Solomon Islands', 'flags-and-countries', 937, 8907, ['flag', 'country', 'nation', 'solomon islands', 'sb'], '512x384', 'Flag of Solomon Islands. Vector SVG and 512x384 PNG.'],
            ['flag-somalia', 'Flag Somalia', 'flags-and-countries', 480, 4372, ['flag', 'country', 'nation', 'somalia', 'so'], '512x384', 'Flag of Somalia. Vector SVG and 512x384 PNG.'],
            ['flag-south-africa', 'Flag South Africa', 'flags-and-countries', 857, 13516, ['flag', 'country', 'nation', 'south africa', 'za'], '512x384', 'Flag of South Africa. Vector SVG and 512x384 PNG.'],
            ['flag-south-korea', 'Flag South Korea', 'flags-and-countries', 1058, 12298, ['flag', 'country', 'nation', 'south korea', 'kr'], '512x384', 'Flag of South Korea. Vector SVG and 512x384 PNG.'],
            ['flag-south-sudan', 'Flag South Sudan', 'flags-and-countries', 395, 7107, ['flag', 'country', 'nation', 'south sudan', 'ss'], '512x384', 'Flag of South Sudan. Vector SVG and 512x384 PNG.'],
            ['flag-spain', 'Flag Spain', 'flags-and-countries', 80955, 23643, ['flag', 'country', 'nation', 'spain', 'es'], '512x384', 'Flag of Spain. Vector SVG and 512x384 PNG.'],
            ['flag-sri-lanka', 'Flag Sri Lanka', 'flags-and-countries', 10721, 24452, ['flag', 'country', 'nation', 'sri lanka', 'lk'], '512x384', 'Flag of Sri Lanka. Vector SVG and 512x384 PNG.'],
            ['flag-sudan', 'Flag Sudan', 'flags-and-countries', 502, 2472, ['flag', 'country', 'nation', 'sudan', 'sd'], '512x384', 'Flag of Sudan. Vector SVG and 512x384 PNG.'],
            ['flag-suriname', 'Flag Suriname', 'flags-and-countries', 309, 4175, ['flag', 'country', 'nation', 'suriname', 'sr'], '512x384', 'Flag of Suriname. Vector SVG and 512x384 PNG.'],
            ['flag-sweden', 'Flag Sweden', 'flags-and-countries', 206, 2335, ['flag', 'country', 'nation', 'sweden', 'se'], '512x384', 'Flag of Sweden. Vector SVG and 512x384 PNG.'],
            ['flag-switzerland', 'Flag Switzerland', 'flags-and-countries', 287, 2118, ['flag', 'country', 'nation', 'switzerland', 'ch'], '512x384', 'Flag of Switzerland. Vector SVG and 512x384 PNG.'],
            ['flag-syria', 'Flag Syria', 'flags-and-countries', 363, 4265, ['flag', 'country', 'nation', 'syria', 'sy'], '512x384', 'Flag of Syria. Vector SVG and 512x384 PNG.'],
            ['flag-tajikistan', 'Flag Tajikistan', 'flags-and-countries', 1840, 8338, ['flag', 'country', 'nation', 'tajikistan', 'tj'], '512x384', 'Flag of Tajikistan. Vector SVG and 512x384 PNG.'],
            ['flag-tanzania', 'Flag Tanzania', 'flags-and-countries', 545, 3670, ['flag', 'country', 'nation', 'tanzania', 'tz'], '512x384', 'Flag of Tanzania. Vector SVG and 512x384 PNG.'],
            ['flag-thailand', 'Flag Thailand', 'flags-and-countries', 281, 1430, ['flag', 'country', 'nation', 'thailand', 'th'], '512x384', 'Flag of Thailand. Vector SVG and 512x384 PNG.'],
            ['flag-timor-leste', 'Flag Timor-Leste', 'flags-and-countries', 608, 8186, ['flag', 'country', 'nation', 'timor-leste', 'tl'], '512x384', 'Flag of Timor-Leste. Vector SVG and 512x384 PNG.'],
            ['flag-togo', 'Flag Togo', 'flags-and-countries', 705, 4065, ['flag', 'country', 'nation', 'togo', 'tg'], '512x384', 'Flag of Togo. Vector SVG and 512x384 PNG.'],
            ['flag-tonga', 'Flag Tonga', 'flags-and-countries', 349, 2108, ['flag', 'country', 'nation', 'tonga', 'to'], '512x384', 'Flag of Tonga. Vector SVG and 512x384 PNG.'],
            ['flag-trinidad-and-tobago', 'Flag Trinidad and Tobago', 'flags-and-countries', 301, 14870, ['flag', 'country', 'nation', 'trinidad and tobago', 'tt'], '512x384', 'Flag of Trinidad and Tobago. Vector SVG and 512x384 PNG.'],
            ['flag-tunisia', 'Flag Tunisia', 'flags-and-countries', 340, 9295, ['flag', 'country', 'nation', 'tunisia', 'tn'], '512x384', 'Flag of Tunisia. Vector SVG and 512x384 PNG.'],
            ['flag-turkey', 'Flag Turkey', 'flags-and-countries', 546, 7773, ['flag', 'country', 'nation', 'turkey', 'tr'], '512x384', 'Flag of Turkey. Vector SVG and 512x384 PNG.'],
            ['flag-turkmenistan', 'Flag Turkmenistan', 'flags-and-countries', 38346, 50298, ['flag', 'country', 'nation', 'turkmenistan', 'tm'], '512x384', 'Flag of Turkmenistan. Vector SVG and 512x384 PNG.'],
            ['flag-tuvalu', 'Flag Tuvalu', 'flags-and-countries', 1424, 16870, ['flag', 'country', 'nation', 'tuvalu', 'tv'], '512x384', 'Flag of Tuvalu. Vector SVG and 512x384 PNG.'],
            ['flag-uganda', 'Flag Uganda', 'flags-and-countries', 3926, 10370, ['flag', 'country', 'nation', 'uganda', 'ug'], '512x384', 'Flag of Uganda. Vector SVG and 512x384 PNG.'],
            ['flag-ukraine', 'Flag Ukraine', 'flags-and-countries', 229, 1404, ['flag', 'country', 'nation', 'ukraine', 'ua'], '512x384', 'Flag of Ukraine. Vector SVG and 512x384 PNG.'],
            ['flag-united-arab-emirates', 'Flag United Arab Emirates', 'flags-and-countries', 263, 2075, ['flag', 'country', 'nation', 'united arab emirates', 'ae'], '512x384', 'Flag of United Arab Emirates. Vector SVG and 512x384 PNG.'],
            ['flag-united-kingdom', 'Flag United Kingdom', 'flags-and-countries', 501, 14663, ['flag', 'country', 'nation', 'united kingdom', 'gb'], '512x384', 'Flag of United Kingdom. Vector SVG and 512x384 PNG.'],
            ['flag-united-states', 'Flag United States', 'flags-and-countries', 645, 13957, ['flag', 'country', 'nation', 'united states', 'us'], '512x384', 'Flag of United States. Vector SVG and 512x384 PNG.'],
            ['flag-uruguay', 'Flag Uruguay', 'flags-and-countries', 1721, 18422, ['flag', 'country', 'nation', 'uruguay', 'uy'], '512x384', 'Flag of Uruguay. Vector SVG and 512x384 PNG.'],
            ['flag-uzbekistan', 'Flag Uzbekistan', 'flags-and-countries', 1495, 6849, ['flag', 'country', 'nation', 'uzbekistan', 'uz'], '512x384', 'Flag of Uzbekistan. Vector SVG and 512x384 PNG.'],
            ['flag-vanuatu', 'Flag Vanuatu', 'flags-and-countries', 2003, 14948, ['flag', 'country', 'nation', 'vanuatu', 'vu'], '512x384', 'Flag of Vanuatu. Vector SVG and 512x384 PNG.'],
            ['flag-vatican-city', 'Flag Vatican City', 'flags-and-countries', 28717, 27752, ['flag', 'country', 'nation', 'vatican city', 'va'], '512x384', 'Flag of Vatican City. Vector SVG and 512x384 PNG.'],
            ['flag-venezuela', 'Flag Venezuela', 'flags-and-countries', 1203, 6238, ['flag', 'country', 'nation', 'venezuela', 've'], '512x384', 'Flag of Venezuela. Vector SVG and 512x384 PNG.'],
            ['flag-vietnam', 'Flag Vietnam', 'flags-and-countries', 487, 5844, ['flag', 'country', 'nation', 'vietnam', 'vn'], '512x384', 'Flag of Vietnam. Vector SVG and 512x384 PNG.'],
            ['flag-yemen', 'Flag Yemen', 'flags-and-countries', 284, 1582, ['flag', 'country', 'nation', 'yemen', 'ye'], '512x384', 'Flag of Yemen. Vector SVG and 512x384 PNG.'],
            ['flag-zambia', 'Flag Zambia', 'flags-and-countries', 5418, 13788, ['flag', 'country', 'nation', 'zambia', 'zm'], '512x384', 'Flag of Zambia. Vector SVG and 512x384 PNG.'],
            ['flag-zimbabwe', 'Flag Zimbabwe', 'flags-and-countries', 6183, 15765, ['flag', 'country', 'nation', 'zimbabwe', 'zw'], '512x384', 'Flag of Zimbabwe. Vector SVG and 512x384 PNG.'],
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
