<?php

namespace App\Modules\Component\database\seeders\Library;

use App\Modules\Component\database\seeders\Library\ComponentKit as Kit;

/**
 * The Dashboards category: stat tiles, charts and the panels they sit in.
 *
 * Every chart here is inline SVG rather than a charting library, which is the
 * only way a single-file template can draw one. That constraint turns out to
 * be a feature: the marks are plain elements, so they inherit the theme, print
 * correctly and carry a native `<title>` tooltip without a line of JavaScript.
 *
 * The chart rules the whole category follows:
 *
 *   - categorical colours come from {@see Kit::SERIES} in fixed order, so a
 *     fourth series never repaints the first three;
 *   - magnitude uses one hue from {@see Kit::RAMP}, light to dark, never a
 *     rainbow;
 *   - two series or more always carry a legend, so identity is never colour
 *     alone;
 *   - one y-scale per chart, always - two scales on one plot can be drawn to
 *     support any conclusion you like;
 *   - grid lines and axes recede; the data is the only thing at full strength.
 */
final class DashboardLibrary
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return array_merge(self::setOne(), self::setTwo(), self::setThree());
    }

    /* ================================================================== */
    /* Shells                                                              */
    /* ================================================================== */

    private static function wrap(string ...$parts): string
    {
        return '<div class="wrap">' . implode('', $parts) . '</div>';
    }

    private static function card(string $inner, string $style = ''): string
    {
        return '<div class="card"' . ($style === '' ? '' : ' style="' . $style . '"') . '>' . $inner . '</div>';
    }

    private static function head(string $title, string $sub = '', string $tools = ''): string
    {
        $caption = $sub === '' ? '' : '<p class="sub">' . $sub . '</p>';

        return '<div class="hd"><div><h2>' . $title . '</h2>' . $caption . '</div>'
            . ($tools === '' ? '' : '<div class="row" style="gap:8px;flex-wrap:wrap">' . $tools . '</div>')
            . '</div>';
    }

    /** A responsive grid of cards. */
    private static function grid(int $min, string ...$cards): string
    {
        return '<div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(' . $min . 'px,1fr))">'
            . implode('', $cards) . '</div>';
    }

    /**
     * A stat tile.
     *
     * The number is the mark here, so it is set large and everything else -
     * label, delta, sparkline - is support. A tile with a delta always says
     * what the delta is measured against; "+12%" on its own is not a fact.
     */
    private static function tile(string $label, string $value, string $icon, string $colour, ?array $spark = null, string $delta = '', bool $up = true, string $since = 'vs last month'): string
    {
        $trend = $delta === '' ? '' : '<div style="margin-top:10px">' . Kit::delta($delta, $up, $since) . '</div>';
        $chart = $spark === null ? '' : '<div style="margin-top:12px">' . Kit::spark($spark, $colour, 150, 40) . '</div>';

        return self::card(
            '<div class="pad">'
            . '<div class="row" style="justify-content:space-between;align-items:flex-start">'
            . '<span><span class="xs mut" style="text-transform:uppercase;letter-spacing:.06em;font-weight:700">' . $label . '</span>'
            . '<div class="num" style="font-size:27px;font-weight:700;letter-spacing:-.02em;margin-top:6px">' . $value . '</div></span>'
            . Kit::iconTile($icon, $colour, 38) . '</div>'
            . $trend . $chart . '</div>'
        );
    }

    /* ================================================================== */
    /* Charts                                                              */
    /* ================================================================== */

    /**
     * A line chart.
     *
     * @param array<int,array{label:string,values:array<int,float>}> $series
     * @param array<int,string> $xLabels
     */
    private static function line(array $series, array $xLabels, array $opt = []): string
    {
        $w = $opt['w'] ?? 640;
        $h = $opt['h'] ?? 220;
        $padL = 42;
        $padB = 26;
        $padT = 10;

        $all = array_merge(...array_map(fn($s) => $s['values'], $series));
        $max = $opt['max'] ?? (max($all) * 1.12);
        $min = $opt['min'] ?? 0;
        $plotW = $w - $padL - 8;
        $plotH = $h - $padB - $padT;

        $x = fn(int $i, int $count) => round($padL + ($count < 2 ? 0 : $i / ($count - 1) * $plotW), 2);
        $y = fn(float $value) => round($padT + $plotH - ($value - $min) / (($max - $min) ?: 1) * $plotH, 2);

        // Four grid lines is enough to read a value off; more is wallpaper.
        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" role="img" aria-label="' . ($opt['alt'] ?? 'Line chart') . '" style="display:block;overflow:visible">';
        for ($g = 0; $g <= 4; $g++) {
            $value = $min + ($max - $min) * $g / 4;
            $gy = $y($value);
            $svg .= '<line x1="' . $padL . '" y1="' . $gy . '" x2="' . ($w - 8) . '" y2="' . $gy . '" stroke="var(--bd)" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 8) . '" y="' . ($gy + 3.5) . '" text-anchor="end" font-size="10" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . self::tick($value, $opt['unit'] ?? '') . '</text>';
        }

        foreach (array_values($xLabels) as $i => $label) {
            $svg .= '<text x="' . $x($i, count($xLabels)) . '" y="' . ($h - 8) . '" text-anchor="middle" font-size="10" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . $label . '</text>';
        }

        foreach (array_values($series) as $index => $line) {
            $colour = $line['colour'] ?? Kit::series($index, ($opt['dark'] ?? false));
            $count = count($line['values']);
            $points = [];
            foreach (array_values($line['values']) as $i => $value) {
                $points[] = $x($i, $count) . ',' . $y($value);
            }

            if (!empty($opt['area']) && count($series) === 1) {
                $id = 'ar' . substr(md5($colour . implode($points)), 0, 6);
                $svg .= '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1">'
                    . '<stop offset="0" stop-color="' . $colour . '" stop-opacity=".22"/>'
                    . '<stop offset="1" stop-color="' . $colour . '" stop-opacity="0"/></linearGradient></defs>'
                    . '<path d="M' . implode(' L', $points) . ' L' . $x($count - 1, $count) . ',' . ($padT + $plotH) . ' L' . $padL . ',' . ($padT + $plotH) . ' Z" fill="url(#' . $id . ')"/>';
            }

            $svg .= '<path d="M' . implode(' L', $points) . '" fill="none" stroke="' . $colour . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                . '<title>' . Kit::esc($line['label']) . '</title></path>';

            // Only the last point gets a marker and a direct label: a dot on
            // every point turns the line into a dotted line.
            $lastX = $x($count - 1, $count);
            $lastY = $y(end($line['values']));
            $svg .= '<circle cx="' . $lastX . '" cy="' . $lastY . '" r="4" fill="' . $colour . '" stroke="var(--card)" stroke-width="2"/>';
        }

        return $svg . '</svg>';
    }

    /** Axis tick text - thousands folded to k so the gutter stays narrow. */
    private static function tick(float $value, string $unit): string
    {
        if ($unit === '%') {
            return round($value) . '%';
        }
        $prefix = $unit === '$' ? '$' : '';

        if (abs($value) >= 1000) {
            return $prefix . round($value / 1000, abs($value) >= 10000 ? 0 : 1) . 'k';
        }

        return $prefix . round($value);
    }

    /**
     * A column chart, optionally grouped.
     *
     * @param array<int,array{label:string,values:array<int,float>}> $series
     * @param array<int,string> $labels
     */
    private static function columns(array $series, array $labels, array $opt = []): string
    {
        $w = $opt['w'] ?? 640;
        $h = $opt['h'] ?? 220;
        $padL = 42;
        $padB = 26;
        $padT = 10;
        $plotW = $w - $padL - 8;
        $plotH = $h - $padB - $padT;

        $all = array_merge(...array_map(fn($s) => $s['values'], $series));
        $max = $opt['max'] ?? (max($all) * 1.12);
        $groups = count($labels);
        $slot = $plotW / max(1, $groups);
        $gap = 2;                                   // the surface gap between bars
        $barW = ($slot * 0.62 - $gap * (count($series) - 1)) / count($series);

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" role="img" aria-label="' . ($opt['alt'] ?? 'Column chart') . '" style="display:block;overflow:visible">';

        for ($g = 0; $g <= 4; $g++) {
            $value = $max * $g / 4;
            $gy = round($padT + $plotH - $value / $max * $plotH, 2);
            $svg .= '<line x1="' . $padL . '" y1="' . $gy . '" x2="' . ($w - 8) . '" y2="' . $gy . '" stroke="var(--bd)" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 8) . '" y="' . ($gy + 3.5) . '" text-anchor="end" font-size="10" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . self::tick($value, $opt['unit'] ?? '') . '</text>';
        }

        foreach (array_values($labels) as $i => $label) {
            $centre = $padL + $slot * $i + $slot / 2;
            $svg .= '<text x="' . round($centre, 2) . '" y="' . ($h - 8) . '" text-anchor="middle" font-size="10" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . $label . '</text>';

            foreach (array_values($series) as $s => $set) {
                $value = $set['values'][$i] ?? 0;
                $barH = max(2, round($value / $max * $plotH, 2));
                $x = $centre - ($slot * 0.62) / 2 + $s * ($barW + $gap);
                $y = round($padT + $plotH - $barH, 2);
                $colour = $set['colour'] ?? Kit::series($s, ($opt['dark'] ?? false));

                $svg .= '<rect x="' . round($x, 2) . '" y="' . $y . '" width="' . round($barW, 2) . '" height="' . $barH
                    . '" rx="4" fill="' . $colour . '"><title>' . Kit::esc($set['label'] . ' · ' . $label . ': ' . self::tick($value, $opt['unit'] ?? '')) . '</title></rect>';
            }
        }

        return $svg . '</svg>';
    }

    /**
     * A heatmap on one hue.
     *
     * @param array<int,array{label:string,values:array<int,?float>}> $rows
     * @param array<int,string> $columns
     */
    private static function heat(array $rows, array $columns, array $opt = []): string
    {
        $cell = $opt['cell'] ?? 40;
        $gap = 3;
        $labelW = $opt['labelW'] ?? 78;
        $w = $labelW + count($columns) * ($cell + $gap);
        $h = 22 + count($rows) * ($cell + $gap);

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" role="img" aria-label="' . ($opt['alt'] ?? 'Heatmap') . '" style="display:block;overflow:visible">';

        foreach (array_values($columns) as $c => $label) {
            $svg .= '<text x="' . ($labelW + $c * ($cell + $gap) + $cell / 2) . '" y="12" text-anchor="middle" font-size="10" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . $label . '</text>';
        }

        foreach (array_values($rows) as $r => $row) {
            $y = 22 + $r * ($cell + $gap);
            $svg .= '<text x="0" y="' . ($y + $cell / 2 + 3.5) . '" font-size="10.5" fill="var(--mut)" font-family="' . Kit::FONT . '">'
                . Kit::esc($row['label']) . '</text>';

            foreach (array_values($row['values']) as $c => $value) {
                $x = $labelW + $c * ($cell + $gap);
                if ($value === null) {
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cell . '" height="' . $cell . '" rx="6" fill="var(--soft)"/>';
                    continue;
                }

                $magnitude = $value / ($opt['max'] ?? 100);
                $fill = Kit::ramp($magnitude);
                // Ink flips once the cell is dark enough to swallow dark text.
                $ink = $magnitude > 0.55 ? '#ffffff' : '#0f172a';
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cell . '" height="' . $cell . '" rx="6" fill="' . $fill . '">'
                    . '<title>' . Kit::esc($row['label'] . ' · ' . ($columns[$c] ?? '') . ': ' . round($value) . '%') . '</title></rect>'
                    . '<text x="' . ($x + $cell / 2) . '" y="' . ($y + $cell / 2 + 3.5) . '" text-anchor="middle" font-size="10" font-weight="600" fill="' . $ink . '" font-family="' . Kit::FONT . '">'
                    . round($value) . '</text>';
            }
        }

        return $svg . '</svg>';
    }

    /**
     * The legend.
     *
     * Present whenever there is more than one series, because identity that
     * lives only in colour is identity a colourblind reader does not have.
     *
     * @param array<int,array{0:string,1:string}> $items label and colour pairs
     */
    private static function legend(array $items): string
    {
        $out = '<div class="row" style="gap:16px;flex-wrap:wrap">';
        foreach ($items as [$label, $colour]) {
            $out .= '<span class="row" style="gap:7px;font-size:12px;color:var(--mut)">'
                . '<span style="width:10px;height:10px;border-radius:3px;background:' . $colour . ';flex:none"></span>'
                . Kit::esc($label) . '</span>';
        }

        return $out . '</div>';
    }

    /** A row in a ranked list - the "top five" panel body. */
    private static function rank(string $label, string $meta, string $value, float $percent, string $colour, string $lead = ''): string
    {
        $mark = $lead === '' ? '' : $lead;

        return '<div style="display:grid;gap:7px">'
            . '<div class="row" style="justify-content:space-between;gap:12px">'
            . '<span class="row" style="gap:9px;min-width:0">' . $mark
            . '<span style="min-width:0"><span class="bold" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . $label . '</span>'
            . '<span class="xs mut">' . $meta . '</span></span></span>'
            . '<span class="num bold" style="white-space:nowrap">' . $value . '</span></div>'
            . Kit::meter($percent, $colour, 6) . '</div>';
    }

    /** A timeline entry for the activity panels. */
    private static function event(string $icon, string $colour, string $title, string $meta): string
    {
        return '<div class="row" style="gap:11px;align-items:flex-start">' . Kit::iconTile($icon, $colour, 34)
            . '<span style="flex:1"><span class="sm" style="display:block">' . $title . '</span>'
            . '<span class="xs mut">' . $meta . '</span></span></div>';
    }

    /** The date-range control every dashboard carries. */
    private static function range(string $current = 'Last 30 days'): string
    {
        return '<select class="in" aria-label="Date range" style="width:auto;padding:7px 32px 7px 11px;font-size:12.5px">'
            . '<option>' . $current . '</option><option>Last 7 days</option><option>Last 90 days</option>'
            . '<option>This year</option><option>Custom range</option></select>';
    }

    private static function btn(string $label, string $icon = '', string $kind = ''): string
    {
        $glyph = $icon === '' ? '' : Kit::icon($icon, 15);

        return '<button class="btn tiny ' . $kind . '" type="button">' . $glyph . $label . '</button>';
    }

    /* ================================================================== */
    /* Definitions                                                         */
    /* ================================================================== */

    /** @return array<int,array<string,mixed>> */
    private static function setOne(): array
    {
        return [
            [
                'slug' => 'revenue-overview-dashboard',
                'name' => 'Revenue overview dashboard',
                'name_ar' => 'لوحة نظرة عامة على الإيرادات',
                'tagline' => 'Four KPI tiles above a two-series revenue chart.',
                'tagline_ar' => 'أربع بطاقات مؤشرات فوق مخطط إيرادات بسلسلتين.',
                'summary' => 'The standard opening screen: the numbers first, then the shape of them over time. Two series share one y-axis rather than being forced onto two scales, and the legend means the colours are never the only thing telling them apart.',
                'summary_ar' => 'الشاشة الافتتاحية المعتادة: الأرقام أولًا، ثم شكلها عبر الزمن. تتشارك السلسلتان محورًا صاديًّا واحدًا بدل حشرهما في مقياسين، ويضمن مفتاح المخطط ألّا تكون الألوان وحدها ما يميّز بينهما.',
                'accent' => '#2563eb',
                'tags' => ['dashboard', 'revenue', 'kpi', 'chart'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['KPI tiles with trend and comparison period', 'Two-series line chart on a single y-axis', 'Native tooltips on every mark, no JavaScript'],
                'features_ar' => ['بطاقات مؤشرات مع الاتجاه وفترة المقارنة', 'مخطط خطي بسلسلتين على محور صادي واحد', 'تلميحات أصلية على كل عنصر، دون JavaScript'],
                'height' => 720,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Overview</h1><p class="sub">1 - 30 September 2026</p></div>'
                    . '<div class="row" style="gap:8px">' . self::range() . self::btn('Export', 'download') . '</div></div>',
                    self::grid(
                        200,
                        self::tile('Revenue', '$248,120', 'wallet', Kit::SERIES[0], [42, 44, 41, 48, 52, 50, 58, 61, 59, 66, 71, 74], '+18.4%', true),
                        self::tile('Orders', '3,482', 'cart', Kit::SERIES[1], [30, 32, 31, 35, 34, 38, 37, 41, 44, 42, 47, 49], '+9.1%', true),
                        self::tile('Average order', '$71.26', 'tag', Kit::SERIES[2], [28, 29, 31, 30, 32, 31, 33, 32, 34, 33, 35, 36], '+2.7%', true),
                        self::tile('Refund rate', '1.8%', 'refresh', Kit::SERIES[3], [22, 21, 24, 23, 20, 19, 21, 18, 17, 19, 16, 15], '-0.4pt', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Revenue and target', 'Monthly, this year against plan', self::legend([['Revenue', Kit::SERIES[0]], ['Target', Kit::SERIES[1]]]))
                        . '<div class="pad">' . self::line(
                            [
                                ['label' => 'Revenue', 'values' => [148, 162, 155, 178, 186, 194, 188, 206, 224, 232, 241, 248]],
                                ['label' => 'Target', 'values' => [150, 160, 170, 180, 190, 200, 210, 215, 220, 230, 240, 250]],
                            ],
                            ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            ['w' => 900, 'h' => 250, 'unit' => '$', 'alt' => 'Revenue against target by month']
                        ) . '</div>'
                        . '<div class="ft"><span>Revenue in thousands</span><span>Ahead of plan in 8 of 12 months</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'kpi-stat-tiles',
                'name' => 'KPI stat tiles',
                'name_ar' => 'بطاقات مؤشرات الأداء',
                'tagline' => 'A row of four metric tiles with sparklines and deltas.',
                'tagline_ar' => 'صف من أربع بطاقات مؤشرات مع مخططات مصغّرة (sparkline) ونسب التغيّر.',
                'summary' => 'The most reused block in any dashboard, done properly: the number dominates, the delta always names what it is compared against, and the sparkline gives the shape without pretending to be a chart you can read values off.',
                'summary_ar' => 'أكثر عنصر يتكرر في أي لوحة تحكم، منفّذًا كما ينبغي: الرقم هو الأبرز، ونسبة التغيّر تذكر دائمًا ما تُقارَن به، والمخطط المصغّر يعطي الشكل العام دون أن يدّعي أنه مخطط تُقرأ منه القيم.',
                'accent' => '#7c3aed',
                'tags' => ['kpi', 'tiles', 'metrics', 'sparkline'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Number as the dominant mark on each tile', 'Delta states its comparison period', 'Wraps to two columns on a narrow frame'],
                'features_ar' => ['الرقم هو العنصر الأبرز في كل بطاقة', 'نسبة التغيّر تذكر فترة المقارنة', 'يلتف إلى عمودين في الإطارات الضيقة'],
                'height' => 320,
                'max' => 980,
                'body' => self::wrap(
                    self::grid(
                        210,
                        self::tile('Monthly visitors', '482,104', 'users', Kit::SERIES[0], [30, 34, 33, 38, 42, 40, 46, 48, 52, 55, 58, 62], '+12.8%', true, 'vs August'),
                        self::tile('Sign-ups', '9,481', 'user', Kit::SERIES[1], [18, 22, 20, 26, 24, 30, 28, 34, 36, 33, 39, 42], '+22.4%', true, 'vs August'),
                        self::tile('Churn', '2.4%', 'trend-down', Kit::SERIES[2], [40, 38, 36, 37, 34, 33, 31, 30, 28, 27, 25, 24], '-0.6pt', true, 'vs August'),
                        self::tile('Support load', '1,204 tickets', 'bell', Kit::SERIES[3], [20, 24, 28, 26, 32, 36, 34, 40, 44, 48, 52, 58], '+16.2%', false, 'vs August')
                    )
                ),
            ],
            [
                'slug' => 'sales-funnel-card',
                'name' => 'Sales funnel card',
                'name_ar' => 'بطاقة قمع المبيعات',
                'tagline' => 'Stage bars with drop-off called out between them.',
                'tagline_ar' => 'أشرطة للمراحل مع إبراز نسبة التسرّب بينها.',
                'summary' => 'A funnel is useless without its drop-off figures, so those sit between the stages rather than being left as an exercise. Stage width is proportional to volume, and the worst drop is flagged, which is the only stage anyone can act on.',
                'summary_ar' => 'لا فائدة من قمع بلا أرقام التسرّب، لذا تظهر بين المراحل بدل أن تُترك للقارئ ليحسبها. عرض كل مرحلة يتناسب مع حجمها، وتُميَّز أسوأ نقطة تسرّب، فهي المرحلة الوحيدة التي يمكن لأحد أن يتصرّف حيالها.',
                'accent' => '#059669',
                'tags' => ['funnel', 'conversion', 'sales', 'stages'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Drop-off percentage shown between stages', 'Worst step flagged automatically in the copy', 'Bars proportional to stage volume'],
                'features_ar' => ['نسبة التسرّب معروضة بين المراحل', 'أسوأ خطوة تُبرَز تلقائيًّا في النص', 'أشرطة متناسبة مع حجم كل مرحلة'],
                'height' => 600,
                'max' => 600,
                'css' => ".stage{display:grid;gap:7px}\n.stage .top{display:flex;justify-content:space-between;font-size:13px}\n.stage .bar{height:34px;border-radius:9px;display:flex;align-items:center;padding:0 12px;color:#fff;font-weight:700;font-size:13px}\n.drop{display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--mut);padding:7px 0 7px 14px}\n.drop.worst{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    self::card(
                        self::head('Conversion funnel', 'Last 30 days · web and app', self::range())
                        . '<div class="pad" style="display:grid;gap:2px">'
                        . self::funnelStage('Visited the site', '482,104', 100, Kit::RAMP[8])
                        . self::funnelDrop('-68.2% leave without viewing a product', false)
                        . self::funnelStage('Viewed a product', '153,308', 32, Kit::RAMP[7])
                        . self::funnelDrop('-54.1% never add to the basket', false)
                        . self::funnelStage('Added to basket', '70,418', 15, Kit::RAMP[6])
                        . self::funnelDrop('-71.4% abandon at the basket - the biggest single loss', true)
                        . self::funnelStage('Started checkout', '20,124', 4.2, Kit::RAMP[5])
                        . self::funnelDrop('-17.3% drop during payment', false)
                        . self::funnelStage('Completed purchase', '16,642', 3.5, Kit::RAMP[4])
                        . '</div>'
                        . '<div class="ft"><span>Overall conversion 3.45%</span><span>Recovering 10% of basket abandons is worth $148k a year</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'traffic-sources-donut',
                'name' => 'Traffic sources donut',
                'name_ar' => 'رسم مصادر الزيارات',
                'tagline' => 'A donut with a labelled breakdown beside it.',
                'tagline_ar' => 'مخطط دائري مجوّف مع تفصيل موسوم بجانبه.',
                'summary' => 'A donut can show a split but never its values, so the legend beside it carries both the number and the share. Five slices is the ceiling - everything past that is folded into Other rather than becoming an unreadable sliver.',
                'summary_ar' => 'يُظهر المخطط الدائري المجوّف التوزيع لكنه لا يُظهر القيم أبدًا، لذا يحمل المفتاح المجاور الرقم والحصة معًا. الحد الأقصى خمس شرائح، وما زاد يُضَمّ إلى «أخرى» بدل أن يصبح شريحة رفيعة لا تُقرأ.',
                'accent' => '#0891b2',
                'tags' => ['donut', 'traffic', 'breakdown', 'analytics'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Values and shares beside the chart, not on it', 'Long tail folded into a single Other slice', 'Total in the middle of the donut'],
                'features_ar' => ['القيم والحصص بجانب المخطط لا فوقه', 'الذيل الطويل مضموم في شريحة «أخرى» واحدة', 'الإجمالي في وسط الحلقة'],
                'height' => 480,
                'max' => 620,
                'css' => ".split{display:flex;gap:26px;align-items:center;flex-wrap:wrap;justify-content:center}\n.rows{display:grid;gap:12px;min-width:220px;flex:1}\n.rowi{display:grid;grid-template-columns:12px 1fr auto;gap:10px;align-items:center;font-size:13px}\n.dot{width:12px;height:12px;border-radius:4px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Traffic sources', 'Sessions, last 30 days', self::range())
                        . '<div class="pad"><div class="split">'
                        . Kit::donut(
                            [[48210, Kit::SERIES[0]], [21904, Kit::SERIES[1]], [15382, Kit::SERIES[2]], [12047, Kit::SERIES[3]], [6118, Kit::SERIES[4]]],
                            168,
                            '103.6k',
                            'sessions'
                        )
                        . '<div class="rows">'
                        . self::sourceRow('Organic search', '48,210', '46.5%', Kit::SERIES[0])
                        . self::sourceRow('Referral', '21,904', '21.1%', Kit::SERIES[1])
                        . self::sourceRow('Social', '15,382', '14.8%', Kit::SERIES[2])
                        . self::sourceRow('Email', '12,047', '11.6%', Kit::SERIES[3])
                        . self::sourceRow('Other', '6,118', '5.9%', Kit::SERIES[4])
                        . '</div></div></div>'
                        . '<div class="ft"><span>103,661 sessions</span><span>Organic up 18.4% on last month</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'cohort-retention-heatmap',
                'name' => 'Cohort retention heatmap',
                'name_ar' => 'خريطة حرارية لبقاء الأفواج',
                'tagline' => 'Monthly cohorts down, weeks since signup across.',
                'tagline_ar' => 'الأفواج الشهرية عموديًّا، والأسابيع منذ التسجيل أفقيًّا.',
                'summary' => 'Retention as a single-hue heatmap: darker is higher, and because it is one hue rather than a rainbow, the eye reads it as a quantity instead of as categories. Cells carry their own number, so the colour is never the only encoding.',
                'summary_ar' => 'معدل البقاء في خريطة حرارية بتدرّج لون واحد: الأغمق هو الأعلى، ولأنه لون واحد لا قوس قزح، تقرؤه العين كمية لا فئات. كل خلية تحمل رقمها، فلا يكون اللون وحده وسيلة الترميز.',
                'accent' => '#2563eb',
                'tags' => ['cohort', 'retention', 'heatmap', 'analytics'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['One-hue sequential ramp, never a rainbow', 'Value printed in every cell as well as encoded', 'Ink flips to white once the cell is dark'],
                'features_ar' => ['تدرّج متسلسل بلون واحد، لا قوس قزح أبدًا', 'القيمة مطبوعة في كل خلية إلى جانب ترميزها باللون', 'يتحول لون النص إلى الأبيض حين تغمق الخلية'],
                'height' => 560,
                'max' => 780,
                'css' => ".scale{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--mut)}\n.scale i{width:22px;height:10px;display:block}\n.scale i:first-of-type{border-radius:3px 0 0 3px}\n.scale i:last-of-type{border-radius:0 3px 3px 0}",
                'body' => self::wrap(
                    self::card(
                        self::head('Retention by cohort', 'Share of each signup month still active', '<span class="scale">0%'
                            . implode('', array_map(fn($step) => '<i style="background:' . $step . '"></i>', array_slice(Kit::RAMP, 0, 9)))
                            . '100%</span>')
                        . '<div class="pad">' . self::heat(
                            [
                                ['label' => 'April', 'values' => [100, 68, 54, 47, 43, 41, 39]],
                                ['label' => 'May', 'values' => [100, 71, 58, 51, 46, 44, null]],
                                ['label' => 'June', 'values' => [100, 66, 51, 44, 41, null, null]],
                                ['label' => 'July', 'values' => [100, 74, 62, 56, null, null, null]],
                                ['label' => 'August', 'values' => [100, 78, 66, null, null, null, null]],
                                ['label' => 'September', 'values' => [100, 81, null, null, null, null, null]],
                            ],
                            ['Week 0', 'W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
                            ['alt' => 'Retention heatmap by signup cohort']
                        ) . '</div>'
                        . '<div class="ft"><span>Grey cells have not happened yet</span><span>July onward improved after the onboarding change</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'realtime-visitors-panel',
                'name' => 'Realtime visitors panel',
                'name_ar' => 'لوحة الزوار المباشرين',
                'tagline' => 'Live count, per-minute bars and the pages being read now.',
                'tagline_ar' => 'عدد مباشر، وأشرطة لكل دقيقة، والصفحات المقروءة الآن.',
                'summary' => 'A live panel earns its place by answering "what is happening right now": a pulsing count, the last thirty minutes as bars, and the pages people are actually on - not a chart of yesterday with a live badge on it.',
                'summary_ar' => 'تستحق اللوحة المباشرة مكانها حين تجيب عن سؤال «ماذا يحدث الآن؟»: عدد نابض، وآخر ثلاثين دقيقة على شكل أشرطة، والصفحات التي يتصفحها الناس فعلًا، لا مخطط للأمس عليه شارة «مباشر».',
                'accent' => '#059669',
                'tags' => ['realtime', 'analytics', 'live', 'visitors'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Pulse animation tied to the live state', 'Per-minute bars for the last half hour', 'Current pages with their live counts'],
                'features_ar' => ['حركة نبض مرتبطة بالحالة المباشرة', 'أشرطة لكل دقيقة خلال آخر نصف ساعة', 'الصفحات الحالية مع أعداد زوارها المباشرة'],
                'height' => 620,
                'max' => 640,
                'theme' => 'dark',
                'css' => "@keyframes pulse{0%{opacity:1;transform:scale(1)}70%{opacity:0;transform:scale(2.6)}100%{opacity:0;transform:scale(2.6)}}\n.live{position:relative;display:inline-flex;width:9px;height:9px}\n.live i{position:absolute;inset:0;border-radius:50%;background:var(--ok)}\n.live i:first-child{animation:pulse 1.8s ease-out infinite}\n.big{font-size:46px;font-weight:750;letter-spacing:-.03em;line-height:1}\n.pages{display:grid;gap:12px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Right now', 'Updating every 5 seconds', '<span class="row" style="gap:7px;font-size:12px;color:var(--ok);font-weight:700"><span class="live"><i></i><i></i></span>Live</span>')
                        . '<div class="pad"><div class="row" style="align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap">'
                        . '<span><span class="num big">1,284</span><div class="sm mut" style="margin-top:6px">people on the site</div></span>'
                        . '<span style="flex:1;min-width:220px">' . Kit::bars([18, 22, 19, 26, 31, 28, 34, 30, 38, 42, 39, 44, 48, 45, 52, 58, 54, 61, 66, 62, 70, 74, 71, 78, 82, 79, 86, 91, 88, 94], Kit::SERIES_DARK[2], 320, 62, 29)
                        . '<div class="xs mut" style="display:flex;justify-content:space-between;margin-top:6px"><span>30 minutes ago</span><span>now</span></div></span>'
                        . '</div></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:12px">Pages being read</h3><div class="pages">'
                        . self::rank('/components', 'Component gallery', '412', 100, Kit::SERIES_DARK[0])
                        . self::rank('/drower', 'Drawing editor', '318', 77, Kit::SERIES_DARK[0])
                        . self::rank('/IconsGalary', 'Icons', '244', 59, Kit::SERIES_DARK[0])
                        . self::rank('/ImageConvert', 'Image converter', '188', 46, Kit::SERIES_DARK[0])
                        . self::rank('/', 'Home', '122', 30, Kit::SERIES_DARK[0])
                        . '</div></div>'
                        . '<div class="ft"><span>68% mobile · 30% desktop · 2% tablet</span><span>Peak today 2,104 at 20:15</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'server-health-dashboard',
                'name' => 'Server health dashboard',
                'name_ar' => 'لوحة صحة الخوادم',
                'tagline' => 'Uptime, latency, error rate and a live request chart.',
                'tagline_ar' => 'وقت التشغيل وزمن الاستجابة ومعدل الأخطاء ومخطط طلبات مباشر.',
                'summary' => 'An operations panel on a dark surface, where status colours do the work: green, amber and red are reserved for state and never reused for a data series, and each one ships with a word so the colour is not carrying the meaning alone.',
                'summary_ar' => 'لوحة عمليات على خلفية داكنة تتولى فيها ألوان الحالة المهمة: الأخضر والكهرماني والأحمر محجوزة للحالة ولا تُستخدم لأي سلسلة بيانات، وكلٌّ منها يرافقه نص كي لا يحمل اللون المعنى وحده.',
                'accent' => '#16a34a',
                'tags' => ['monitoring', 'devops', 'uptime', 'dark'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Status colours reserved and always labelled', 'Latency percentiles rather than an average', 'Incident strip along the bottom'],
                'features_ar' => ['ألوان الحالة محجوزة وموسومة دائمًا', 'مئينات زمن الاستجابة بدل المتوسط', 'شريط الحوادث في الأسفل'],
                'height' => 720,
                'max' => 980,
                'theme' => 'dark',
                'css' => ".strip{display:flex;gap:2px;height:26px}\n.strip i{flex:1;border-radius:2px;background:var(--ok)}\n.strip i.w{background:var(--warn)}\n.strip i.b{background:var(--bad)}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Platform health</h1><p class="sub">api.frugaldomain.site · production</p></div>'
                    . '<div class="row" style="gap:8px">' . Kit::pill('All systems operational', 'ok', true) . self::range('Last 24 hours') . '</div></div>',
                    self::grid(
                        200,
                        self::tile('Uptime (30d)', '99.98%', 'shield', Kit::SERIES_DARK[2], null, '+0.02pt', true, 'vs last month'),
                        self::tile('p95 latency', '184 ms', 'zap', Kit::SERIES_DARK[0], [42, 44, 40, 46, 48, 44, 50, 46, 42, 44, 41, 38], '-12 ms', true, 'vs yesterday'),
                        self::tile('Error rate', '0.14%', 'alert', Kit::SERIES_DARK[3], [8, 9, 7, 12, 10, 8, 14, 11, 9, 8, 7, 6], '-0.06pt', true, 'vs yesterday'),
                        self::tile('Requests', '4.2M / day', 'server', Kit::SERIES_DARK[1], [30, 34, 38, 36, 42, 46, 44, 50, 54, 52, 58, 62], '+8.4%', true, 'vs yesterday')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Latency percentiles', 'Milliseconds, last 12 hours', self::legend([['p50', Kit::SERIES_DARK[0]], ['p95', Kit::SERIES_DARK[1]], ['p99', Kit::SERIES_DARK[3]]]))
                        . '<div class="pad">' . self::line(
                            [
                                ['label' => 'p50', 'values' => [48, 46, 52, 49, 51, 47, 53, 50, 48, 46, 44, 45], 'colour' => Kit::SERIES_DARK[0]],
                                ['label' => 'p95', 'values' => [186, 192, 210, 198, 204, 188, 226, 208, 196, 190, 184, 182], 'colour' => Kit::SERIES_DARK[1]],
                                ['label' => 'p99', 'values' => [412, 428, 498, 460, 472, 434, 560, 488, 452, 436, 420, 414], 'colour' => Kit::SERIES_DARK[3]],
                            ],
                            ['00', '02', '04', '06', '08', '10', '12', '14', '16', '18', '20', '22'],
                            ['w' => 900, 'h' => 230, 'dark' => true, 'alt' => 'Latency percentiles over 12 hours']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<div class="row" style="justify-content:space-between;margin-bottom:9px"><h3>Last 90 days</h3><span class="xs mut">2 incidents · 1 degraded day</span></div>'
                        . '<div class="strip">' . implode('', array_map(function ($i) {
                            $class = in_array($i, [34, 61], true) ? ' class="b"' : (in_array($i, [12, 47, 48], true) ? ' class="w"' : '');

                            return '<i' . $class . '></i>';
                        }, range(1, 90))) . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'goal-progress-rings',
                'name' => 'Goal progress rings',
                'name_ar' => 'حلقات تقدم الأهداف',
                'tagline' => 'Three rings with the figure behind each percentage.',
                'tagline_ar' => 'ثلاث حلقات مع الأرقام الكامنة وراء كل نسبة.',
                'summary' => 'A ring is a percentage and nothing more, so each one is paired with the actual numbers underneath - 74% means little until you know it is 148 of 200. Days remaining sits alongside, because a target without a deadline is a wish.',
                'summary_ar' => 'الحلقة نسبة مئوية لا أكثر، لذا تقترن كل واحدة بالأرقام الفعلية أسفلها، فنسبة 74% لا تعني الكثير حتى تعرف أنها 148 من 200. وتظهر الأيام المتبقية بجانبها، لأن الهدف بلا موعد نهائي مجرد أمنية.',
                'accent' => '#d97706',
                'tags' => ['goals', 'progress', 'rings', 'targets'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Every ring paired with its underlying figures', 'Days remaining against the target date', 'Pace indicator: ahead, on track or behind'],
                'features_ar' => ['كل حلقة مقرونة بأرقامها الأساسية', 'الأيام المتبقية حتى تاريخ الهدف', 'مؤشر الوتيرة: متقدم أو في المسار أو متأخر'],
                'height' => 420,
                'max' => 900,
                'body' => self::wrap(
                    self::grid(
                        260,
                        self::goalCard('New customers', 148, 200, Kit::SERIES[0], 'ok', 'Ahead of pace', '11 days left'),
                        self::goalCard('Revenue', 182, 300, Kit::SERIES[1], 'warn', 'Slightly behind', '11 days left'),
                        self::goalCard('Support CSAT', 91, 95, Kit::SERIES[2], 'ok', 'On track', 'Measured monthly')
                    )
                ),
            ],
            [
                'slug' => 'revenue-target-columns',
                'name' => 'Actual against target columns',
                'name_ar' => 'أعمدة الفعلي مقابل المستهدف',
                'tagline' => 'Paired columns per month with a variance row underneath.',
                'tagline_ar' => 'أعمدة مزدوجة لكل شهر مع صف للفارق أسفلها.',
                'summary' => 'Two bars per month, on one scale, with the variance spelled out below rather than left to be eyeballed. This is the chart that replaces a dual-axis monstrosity in most finance decks.',
                'summary_ar' => 'عمودان لكل شهر على مقياس واحد، مع كتابة الفارق صراحةً في الأسفل بدل تركه للتقدير بالعين. هذا هو المخطط الذي يحل محل مخططات المحورين المزدوجين المربكة في معظم العروض المالية.',
                'accent' => '#1d4ed8',
                'tags' => ['columns', 'targets', 'variance', 'finance'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Grouped columns sharing one y-axis', 'Variance stated per month underneath', '2px surface gap between paired bars'],
                'features_ar' => ['أعمدة مجمّعة تتشارك محورًا صاديًّا واحدًا', 'الفارق مذكور لكل شهر في الأسفل', 'فجوة 2px بين العمودين المتجاورين'],
                'height' => 560,
                'max' => 860,
                'css' => ".vars{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;padding:0 20px 18px;text-align:center}\n.vars span{font-size:11.5px;font-variant-numeric:tabular-nums}\n.vars b{display:block;font-size:10px;color:var(--mut);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px}\n.pos{color:var(--ok);font-weight:700}\n.neg{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    self::card(
                        self::head('Actual against target', 'Second half, in thousands', self::legend([['Actual', Kit::SERIES[0]], ['Target', Kit::SERIES[1]]]))
                        . '<div class="pad">' . self::columns(
                            [
                                ['label' => 'Actual', 'values' => [188, 206, 224, 232, 241, 248]],
                                ['label' => 'Target', 'values' => [210, 215, 220, 230, 240, 250]],
                            ],
                            ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            ['w' => 800, 'h' => 240, 'unit' => '$', 'alt' => 'Actual revenue against target by month']
                        ) . '</div>'
                        . '<div class="vars">'
                        . '<span><b>Jul</b><span class="neg">-10.5%</span></span>'
                        . '<span><b>Aug</b><span class="neg">-4.2%</span></span>'
                        . '<span><b>Sep</b><span class="pos">+1.8%</span></span>'
                        . '<span><b>Oct</b><span class="pos">+0.9%</span></span>'
                        . '<span><b>Nov</b><span class="pos">+0.4%</span></span>'
                        . '<span><b>Dec</b><span class="neg">-0.8%</span></span>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'activity-feed-panel',
                'name' => 'Activity feed panel',
                'name_ar' => 'لوحة سجل النشاط',
                'tagline' => 'Timeline of workspace events with day separators.',
                'tagline_ar' => 'خط زمني لأحداث مساحة العمل مع فواصل للأيام.',
                'summary' => 'An activity panel is read newest first and skimmed, so events carry a coloured type tile, one line of plain language, and a relative timestamp. Day separators stop yesterday and today from blurring into one list.',
                'summary_ar' => 'تُقرأ لوحة النشاط من الأحدث وتُتصفح سريعًا، لذا يحمل كل حدث أيقونة ملوّنة لنوعه، وسطرًا واحدًا بلغة بسيطة، ووقتًا نسبيًّا. وتمنع فواصل الأيام اختلاط الأمس باليوم في قائمة واحدة.',
                'accent' => '#7c3aed',
                'tags' => ['activity', 'feed', 'timeline', 'events'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Type tiles that make the event kind scannable', 'Day separators between groups', 'Relative timestamps with absolute on hover'],
                'features_ar' => ['أيقونات للأنواع تجعل نوع الحدث واضحًا بلمحة', 'فواصل للأيام بين المجموعات', 'وقت نسبي مع الوقت الفعلي عند التمرير فوقه'],
                'height' => 620,
                'max' => 560,
                'css' => ".day{font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;padding:14px 0 6px}\n.feed{display:grid;gap:14px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Activity', 'Everything in this workspace', self::btn('Filter', 'filter'))
                        . '<div class="pad">'
                        . '<div class="day">Today</div><div class="feed">'
                        . self::event('upload', Kit::SERIES[0], '<b>Omar Saleh</b> published <b>Analytics table with sparklines</b>', '14 minutes ago')
                        . self::event('check', Kit::SERIES[2], '<b>Maya Rahman</b> approved 4 drawings in the review queue', '42 minutes ago')
                        . self::event('user', Kit::SERIES[4], '<b>Sara Aziz</b> joined the workspace as an Editor', '2 hours ago')
                        . '</div>'
                        . '<div class="day">Yesterday</div><div class="feed">'
                        . self::event('edit', Kit::SERIES[1], '<b>Lina Haddad</b> renamed the category <b>UI Elements</b>', '19:04')
                        . self::event('alert', Kit::SERIES[7], 'Deployment <b>v4.11.3</b> was rolled back after a failed health check', '16:31')
                        . self::event('card', Kit::SERIES[3], 'Invoice <b>INV-2281</b> was paid by Northwind Ltd', '11:22')
                        . '</div>'
                        . '<div class="day">Earlier this week</div><div class="feed">'
                        . self::event('trash', Kit::SERIES[7], '<b>Karim Nasser</b> deleted 12 draft components', 'Tuesday 09:41')
                        . self::event('settings', Kit::SERIES[6], 'Two-factor authentication was made mandatory for admins', 'Monday 08:15')
                        . '</div></div>'
                        . '<div class="ft"><span>Showing 8 of 1,482 events</span><a href="#">Full audit log</a></div>'
                    )
                ),
            ],
            [
                'slug' => 'top-products-panel',
                'name' => 'Top products panel',
                'name_ar' => 'لوحة أفضل المنتجات',
                'tagline' => 'Ranked list with share meters and revenue.',
                'tagline_ar' => 'قائمة مرتّبة مع مقاييس الحصة والإيرادات.',
                'summary' => 'A ranked list beats a bar chart when the labels are long, which product names always are. Each row carries its share as a meter, so the drop from first to fifth is visible without reading five numbers.',
                'summary_ar' => 'تتفوّق القائمة المرتّبة على المخطط الشريطي حين تكون التسميات طويلة، وأسماء المنتجات طويلة دائمًا. يحمل كل صف حصته في مقياس مرئي، فيتضح الفارق بين الأول والخامس دون قراءة خمسة أرقام.',
                'accent' => '#059669',
                'tags' => ['ranking', 'products', 'revenue', 'list'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Rank discs with a share meter per row', 'Revenue and unit count together', 'Long names truncated rather than wrapped'],
                'features_ar' => ['أقراص للترتيب مع مقياس حصة لكل صف', 'الإيرادات وعدد الوحدات معًا', 'الأسماء الطويلة تُختصر بدل أن تلتف'],
                'height' => 520,
                'max' => 560,
                'css' => ".rk{width:24px;height:24px;border-radius:8px;background:var(--soft);color:var(--mut);display:inline-flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:700;flex:none}\n.rk.top{background:var(--acc-soft);color:var(--acc)}\n.list{display:grid;gap:16px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Top products', 'By revenue, last 30 days', self::range())
                        . '<div class="pad"><div class="list">'
                        . self::rank('Nimbus Chair', '932 units · $150 average', '$139,800', 100, Kit::SERIES[0], '<span class="rk top">1</span>')
                        . self::rank('Halo Floor Lamp', '2,140 units · $40 average', '$85,600', 61, Kit::SERIES[0], '<span class="rk top">2</span>')
                        . self::rank('Vista Shelving', '405 units · $150 average', '$60,750', 43, Kit::SERIES[0], '<span class="rk top">3</span>')
                        . self::rank('Aurora Desk Lamp', '1,204 units · $40 average', '$48,160', 34, Kit::SERIES[0], '<span class="rk">4</span>')
                        . self::rank('Terra Side Table', '618 units · $50 average', '$30,900', 22, Kit::SERIES[0], '<span class="rk">5</span>')
                        . '</div></div>'
                        . '<div class="ft"><span>Top 5 of 412 products</span><span>They are 62% of revenue</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'conversion-rate-card',
                'name' => 'Conversion rate card',
                'name_ar' => 'بطاقة معدل التحويل',
                'tagline' => 'One hero number with its trend and a device split.',
                'tagline_ar' => 'رقم رئيسي واحد مع اتجاهه وتوزيعه حسب الجهاز.',
                'summary' => 'Sometimes the right chart is no chart: one number, set large, with the sparkline as supporting evidence and the device split underneath explaining where the movement came from.',
                'summary_ar' => 'أحيانًا يكون المخطط الأنسب هو غياب المخطط: رقم واحد بخط كبير، ومخطط مصغّر (sparkline) كدليل داعم، وتوزيع حسب الجهاز في الأسفل يوضح مصدر التغيّر.',
                'accent' => '#0891b2',
                'tags' => ['conversion', 'hero-number', 'metric', 'devices'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Hero number with a supporting sparkline', 'Device split explaining the movement', 'Comparison period stated explicitly'],
                'features_ar' => ['رقم رئيسي مع مخطط مصغّر (sparkline) داعم', 'توزيع حسب الجهاز يفسّر التغيّر', 'فترة المقارنة مذكورة صراحةً'],
                'height' => 460,
                'max' => 460,
                'css' => ".hero{font-size:56px;font-weight:750;letter-spacing:-.035em;line-height:1}\n.dev{display:grid;gap:12px;padding-top:16px;border-top:1px solid var(--bd);margin-top:16px}\n.devrow{display:grid;grid-template-columns:20px 1fr auto;gap:10px;align-items:center;font-size:13px}",
                'body' => self::wrap(
                    self::card(
                        '<div class="pad">'
                        . '<span class="xs mut" style="text-transform:uppercase;letter-spacing:.06em;font-weight:700">Conversion rate</span>'
                        . '<div class="row" style="align-items:flex-end;gap:14px;margin-top:10px;flex-wrap:wrap">'
                        . '<span class="num hero">3.45%</span>'
                        . '<span style="padding-bottom:6px">' . Kit::delta('+0.42pt', true, 'vs last 30 days') . '</span></div>'
                        . '<div style="margin-top:16px">' . Kit::spark([2.8, 2.9, 2.7, 3.0, 3.1, 2.95, 3.2, 3.15, 3.3, 3.28, 3.4, 3.45], Kit::SERIES[0], 400, 66) . '</div>'
                        . '<div class="dev">'
                        . '<div class="devrow"><span style="color:' . Kit::SERIES[0] . '">' . Kit::icon('grid', 17) . '</span><span>Desktop</span><b class="num">4.81%</b></div>'
                        . '<div class="devrow"><span style="color:' . Kit::SERIES[1] . '">' . Kit::icon('phone', 17) . '</span><span>Mobile</span><b class="num">2.94%</b></div>'
                        . '<div class="devrow"><span style="color:' . Kit::SERIES[2] . '">' . Kit::icon('image', 17) . '</span><span>Tablet</span><b class="num">3.12%</b></div>'
                        . '</div></div>'
                        . '<div class="ft"><span>16,642 of 482,104 sessions</span><span>Mobile drove the gain</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'churn-analysis-card',
                'name' => 'Churn analysis card',
                'name_ar' => 'بطاقة تحليل التسرب',
                'tagline' => 'Churn trend with the stated reasons underneath.',
                'tagline_ar' => 'اتجاه التسرب مع أسبابه المعلنة أسفله.',
                'summary' => 'The rate tells you there is a problem; the reasons tell you which one. Both live on one card, with the reasons ranked so the largest cause is unmissable and voluntary churn separated from involuntary.',
                'summary_ar' => 'يخبرك المعدل بوجود مشكلة، وتخبرك الأسباب بماهيتها. كلاهما على بطاقة واحدة، والأسباب مرتّبة كي لا يفوتك السبب الأكبر، مع فصل التسرب الطوعي عن غير الطوعي.',
                'accent' => '#e11d48',
                'tags' => ['churn', 'retention', 'saas', 'reasons'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Voluntary and involuntary churn told apart', 'Reasons ranked with share meters', 'Recoverable churn called out'],
                'features_ar' => ['التمييز بين التسرب الطوعي وغير الطوعي', 'الأسباب مرتّبة مع مقاييس الحصة', 'إبراز التسرب القابل للاسترداد'],
                'height' => 620,
                'max' => 560,
                'css' => ".two{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:18px 20px}\n.two .k{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700}\n.two .v{font-size:24px;font-weight:700;margin-top:5px}\n.list{display:grid;gap:14px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Churn', 'Monthly, last 12 months', self::range('Last 12 months'))
                        . '<div class="two">'
                        . '<div><span class="k">Voluntary</span><div class="v num">1.6%</div><span class="xs mut">They chose to leave</span></div>'
                        . '<div><span class="k">Involuntary</span><div class="v num">0.8%</div><span class="xs mut">Failed payments - recoverable</span></div>'
                        . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">' . self::line(
                            [['label' => 'Churn', 'values' => [3.4, 3.2, 3.3, 3.0, 2.9, 3.1, 2.8, 2.7, 2.9, 2.6, 2.5, 2.4]]],
                            ['O', 'N', 'D', 'J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S'],
                            ['w' => 480, 'h' => 150, 'unit' => '%', 'area' => true, 'alt' => 'Monthly churn rate']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:13px">Why they left</h3><div class="list">'
                        . self::rank('Too expensive', '41 accounts', '34%', 100, Kit::SERIES[0])
                        . self::rank('Missing a feature', '32 accounts', '27%', 78, Kit::SERIES[0])
                        . self::rank('Card declined', '28 accounts · recoverable', '23%', 68, Kit::SERIES[3])
                        . self::rank('Moved to a competitor', '12 accounts', '10%', 29, Kit::SERIES[0])
                        . self::rank('No reason given', '7 accounts', '6%', 17, Kit::SERIES[0])
                        . '</div></div>'
                        . '<div class="ft"><span>120 accounts lost this month</span><span class="bold">23% is card failures worth chasing</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'subscription-metrics-grid',
                'name' => 'Subscription metrics grid',
                'name_ar' => 'شبكة مؤشرات الاشتراكات',
                'tagline' => 'MRR, ARR, expansion and net revenue retention.',
                'tagline_ar' => 'MRR وARR والتوسّع وصافي الاحتفاظ بالإيرادات.',
                'summary' => 'The six numbers a subscription business is actually run on, on one screen, each with its comparison period. Net revenue retention gets the largest tile because it is the one that decides whether the others matter.',
                'summary_ar' => 'الأرقام الستة التي تُدار بها أعمال الاشتراكات فعلًا، على شاشة واحدة، ولكلٍّ منها فترة مقارنة. يحظى صافي الاحتفاظ بالإيرادات بأكبر بطاقة، لأنه ما يحدد إن كانت البقية ذات أهمية.',
                'accent' => '#4f46e5',
                'tags' => ['saas', 'mrr', 'metrics', 'subscriptions'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Six core subscription metrics in one grid', 'MRR movement broken into new, expansion and churn', 'Net revenue retention given the most weight'],
                'features_ar' => ['ستة مؤشرات اشتراك أساسية في شبكة واحدة', 'حركة MRR مقسّمة إلى جديد وتوسّع وتسرب', 'صافي الاحتفاظ بالإيرادات يحظى بالوزن الأكبر'],
                'height' => 700,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Subscriptions</h1><p class="sub">September 2026 · 312 active accounts</p></div>'
                    . self::range('This month') . '</div>',
                    self::grid(
                        210,
                        self::tile('MRR', '$48,120', 'refresh', Kit::SERIES[0], [30, 32, 34, 33, 36, 38, 40, 42, 41, 44, 46, 48], '+6.2%', true),
                        self::tile('ARR', '$577,440', 'chart', Kit::SERIES[1], null, '+18.9%', true, 'vs last year'),
                        self::tile('Average revenue per account', '$154', 'user', Kit::SERIES[2], null, '+2.1%', true),
                        self::tile('Net revenue retention', '112%', 'trend-up', Kit::SERIES[3], [96, 98, 101, 100, 104, 106, 105, 108, 110, 109, 111, 112], '+4pt', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        320,
                        self::card(
                            self::head('MRR movement', 'What changed this month')
                            . '<div class="pad" style="display:grid;gap:14px">'
                            . self::rank('New business', '18 accounts', '+$4,820', 100, Kit::SERIES[2])
                            . self::rank('Expansion', '24 upgrades', '+$2,140', 44, Kit::SERIES[2])
                            . self::rank('Contraction', '9 downgrades', '-$880', 18, Kit::SERIES[3])
                            . self::rank('Churn', '7 cancellations', '-$1,260', 26, Kit::SERIES[7])
                            . '</div>'
                            . '<div class="ft"><span>Net movement</span><span class="bold" style="color:var(--ok)">+$4,820</span></div>'
                        ),
                        self::card(
                            self::head('Plan mix', 'Accounts by plan')
                            . '<div class="pad" style="display:flex;justify-content:center">'
                            . Kit::donut([[42, Kit::SERIES[0]], [188, Kit::SERIES[1]], [82, Kit::SERIES[2]]], 150, '312', 'accounts')
                            . '</div>'
                            . '<div class="pad" style="padding-top:0">' . self::legend([['Agency · 42', Kit::SERIES[0]], ['Studio · 188', Kit::SERIES[1]], ['Starter · 82', Kit::SERIES[2]]]) . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'support-sla-dashboard',
                'name' => 'Support SLA dashboard',
                'name_ar' => 'لوحة مستوى خدمة الدعم',
                'tagline' => 'Response times, backlog and satisfaction in one view.',
                'tagline_ar' => 'أزمنة الاستجابة والتذاكر المتراكمة والرضا في عرض واحد.',
                'summary' => 'A support lead needs three things on one screen: is the queue growing, are we answering inside the SLA, and are people happy afterwards. Each gets its own tile, with the breach count in status red rather than as a number to interpret.',
                'summary_ar' => 'يحتاج قائد الدعم إلى ثلاثة أمور على شاشة واحدة: هل الطابور يكبر، وهل نردّ ضمن مستوى الخدمة المتفق عليه، وهل العملاء راضون بعد ذلك. لكلٍّ منها بطاقته، وعدد الانتهاكات بأحمر الحالة لا رقمًا يحتاج إلى تفسير.',
                'accent' => '#e11d48',
                'tags' => ['support', 'sla', 'helpdesk', 'metrics'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Backlog trend against new-ticket volume', 'SLA attainment with the breach count in status red', 'Satisfaction shown as a distribution, not an average'],
                'features_ar' => ['اتجاه التذاكر المتراكمة مقابل حجم التذاكر الجديدة', 'نسبة الالتزام بمستوى الخدمة مع عدد الانتهاكات بأحمر الحالة', 'الرضا معروض كتوزيع لا كمتوسط'],
                'height' => 720,
                'max' => 980,
                'css' => ".csat{display:grid;gap:9px}\n.csatrow{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;font-size:12.5px}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Support</h1><p class="sub">Last 7 days · 6 agents</p></div>'
                    . self::range('Last 7 days') . '</div>',
                    self::grid(
                        210,
                        self::tile('First response', '1h 42m', 'clock', Kit::SERIES[0], [58, 54, 60, 48, 52, 44, 42], '-18m', true, 'vs last week'),
                        self::tile('Resolution', '9h 04m', 'check', Kit::SERIES[2], [70, 74, 68, 66, 62, 58, 54], '-1h 12m', true, 'vs last week'),
                        self::tile('Open backlog', '74 tickets', 'list', Kit::SERIES[1], [48, 52, 58, 61, 66, 70, 74], '+26', false, 'vs last week'),
                        self::tile('SLA breaches', '4', 'alert', Kit::SERIES[7], null, '-3', true, 'vs last week')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Tickets opened and closed', 'Daily', self::legend([['Opened', Kit::SERIES[0]], ['Closed', Kit::SERIES[2]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Opened', 'values' => [42, 38, 51, 44, 48, 22, 18]],
                                    ['label' => 'Closed', 'values' => [38, 41, 44, 40, 46, 26, 21], 'colour' => Kit::SERIES[2]],
                                ],
                                ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                                ['w' => 480, 'h' => 200, 'alt' => 'Tickets opened and closed per day']
                            ) . '</div>'
                            . '<div class="ft"><span>263 opened · 256 closed</span><span>Backlog grew by 7</span></div>'
                        ),
                        self::card(
                            self::head('Satisfaction', '412 ratings this week')
                            . '<div class="pad"><div class="csat">'
                            . self::csatRow(5, 284, 69, Kit::SERIES[2])
                            . self::csatRow(4, 78, 19, Kit::SERIES[2])
                            . self::csatRow(3, 26, 6, Kit::SERIES[3])
                            . self::csatRow(2, 14, 3, Kit::SERIES[7])
                            . self::csatRow(1, 10, 2, Kit::SERIES[7])
                            . '</div></div>'
                            . '<div class="ft"><span>Average 4.5 of 5</span><span>88% rated 4 or better</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'social-media-stats',
                'name' => 'Social media stats',
                'name_ar' => 'لوحة إحصاءات التواصل',
                'tagline' => 'Per-network followers, reach and engagement.',
                'tagline_ar' => 'المتابعون والوصول والتفاعل لكل شبكة.',
                'summary' => 'One row per network, each with its own colour tile, follower count and growth. Engagement rate is the column that matters and is therefore the one that is emphasised, not the follower vanity number.',
                'summary_ar' => 'صف لكل شبكة، ولكلٍّ منها أيقونة بلونها وعدد متابعيها ونموّها. معدل التفاعل هو العمود المهم، ولذلك هو المُبرَز، لا عدد المتابعين الذي يغري بالتباهي.',
                'accent' => '#db2777',
                'tags' => ['social', 'marketing', 'engagement', 'networks'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Engagement rate emphasised over follower counts', 'Per-network growth sparkline', 'Best performing post called out'],
                'features_ar' => ['إبراز معدل التفاعل على حساب أعداد المتابعين', 'مخطط نمو مصغّر (sparkline) لكل شبكة', 'إبراز المنشور الأفضل أداءً'],
                'height' => 640,
                'max' => 720,
                'css' => ".net{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)}\n.net:last-child{border-bottom:0}\n.net .eng{font-size:17px;font-weight:700;font-variant-numeric:tabular-nums}\n@media (max-width:540px){.net{grid-template-columns:auto 1fr auto}.net svg.sp{display:none}}",
                'body' => self::wrap(
                    self::card(
                        self::head('Social', 'Last 30 days across 4 networks', self::range())
                        . '<div class="pad">'
                        . self::netRow('globe', Kit::SERIES[0], 'X / Twitter', '48.2k followers', [30, 32, 31, 34, 36, 35, 38, 40], '4.8%', '+1,204')
                        . self::netRow('image', Kit::SERIES[4], 'Instagram', '88.4k followers', [40, 42, 45, 44, 48, 50, 52, 56], '6.2%', '+3,810')
                        . self::netRow('users', Kit::SERIES[1], 'LinkedIn', '22.1k followers', [18, 19, 21, 22, 24, 25, 27, 29], '3.1%', '+980')
                        . self::netRow('play', Kit::SERIES[7], 'YouTube', '12.8k subscribers', [10, 11, 11, 13, 14, 14, 16, 18], '8.4%', '+644')
                        . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<h3 style="margin-bottom:10px">Best post this month</h3>'
                        . '<div class="row" style="gap:12px;align-items:flex-start">' . Kit::iconTile('image', Kit::SERIES[4], 42)
                        . '<span><span class="sm" style="display:block">&ldquo;Two hundred single-file components, free to copy&rdquo;</span>'
                        . '<span class="xs mut">Instagram · 14 Sep · 184k reach · 12.4k engagements</span></span></div></div>'
                        . '<div class="ft"><span>171.5k total followers</span><span>+6,638 this month</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'ecommerce-orders-overview',
                'name' => 'Orders overview dashboard',
                'name_ar' => 'لوحة نظرة عامة على الطلبات',
                'tagline' => 'Order volume, fulfilment states and the day pattern.',
                'tagline_ar' => 'حجم الطلبات وحالات التنفيذ ونمط اليوم.',
                'summary' => 'Operations rather than revenue: how many orders are waiting, where they are stuck, and what time of day the volume arrives - which is what staffing a warehouse actually depends on.',
                'summary_ar' => 'العمليات لا الإيرادات: كم طلبًا ينتظر، وأين تتعثر الطلبات، وفي أي ساعة من اليوم يصل الحجم الأكبر، وهذا ما يعتمد عليه توزيع طاقم المستودع فعلًا.',
                'accent' => '#d97706',
                'tags' => ['ecommerce', 'orders', 'fulfilment', 'operations'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Fulfilment pipeline with counts per state', 'Hour-of-day column chart for staffing', 'Ageing orders flagged in status colour'],
                'features_ar' => ['مسار التنفيذ مع العدد في كل حالة', 'مخطط أعمدة حسب ساعات اليوم لتخطيط الطاقم', 'الطلبات المتأخرة مميّزة بلون الحالة'],
                'height' => 720,
                'max' => 980,
                'css' => ".pipe{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:2px}\n.pipe div{padding:14px;background:var(--soft);text-align:center}\n.pipe div:first-child{border-radius:12px 0 0 12px}\n.pipe div:last-child{border-radius:0 12px 12px 0}\n.pipe .n{font-size:22px;font-weight:700;font-variant-numeric:tabular-nums}\n.pipe .l{font-size:11px;color:var(--mut);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-top:3px}\n.pipe .stuck .n{color:var(--bad)}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Orders</h1><p class="sub">Today · updated 2 minutes ago</p></div>'
                    . '<div class="row" style="gap:8px">' . Kit::pill('3 orders ageing', 'bad', true) . self::btn('Export', 'download') . '</div></div>',
                    self::card('<div class="pipe">'
                        . '<div><div class="n num">42</div><div class="l">Paid</div></div>'
                        . '<div><div class="n num">28</div><div class="l">Picking</div></div>'
                        . '<div><div class="n num">18</div><div class="l">Packed</div></div>'
                        . '<div><div class="n num">64</div><div class="l">Shipped</div></div>'
                        . '<div class="stuck"><div class="n num">3</div><div class="l">Ageing</div></div>'
                        . '</div>'),
                    '<div style="height:14px"></div>',
                    self::grid(
                        320,
                        self::card(
                            self::head('Orders by hour', 'Today, against the weekday average', self::legend([['Today', Kit::SERIES[0]], ['Average', Kit::SERIES[1]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Today', 'values' => [4, 2, 6, 18, 34, 42, 38, 46, 52, 44, 28, 12]],
                                    ['label' => 'Average', 'values' => [5, 3, 7, 16, 30, 38, 40, 42, 46, 40, 26, 14]],
                                ],
                                ['00', '02', '04', '06', '08', '10', '12', '14', '16', '18', '20', '22'],
                                ['w' => 500, 'h' => 200, 'alt' => 'Orders per hour today against average']
                            ) . '</div>'
                            . '<div class="ft"><span>326 orders today</span><span>Peak 16:00 - 17:00</span></div>'
                        ),
                        self::card(
                            self::head('Where orders come from', 'Last 30 days')
                            . '<div class="pad" style="display:flex;justify-content:center">'
                            . Kit::donut([[62, Kit::SERIES[0]], [24, Kit::SERIES[1]], [9, Kit::SERIES[2]], [5, Kit::SERIES[3]]], 150, '3,482', 'orders')
                            . '</div>'
                            . '<div class="pad" style="padding-top:0">' . self::legend([['Web · 62%', Kit::SERIES[0]], ['App · 24%', Kit::SERIES[1]], ['Marketplace · 9%', Kit::SERIES[2]], ['Phone · 5%', Kit::SERIES[3]]]) . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'cashflow-dashboard',
                'name' => 'Cash flow dashboard',
                'name_ar' => 'لوحة التدفق النقدي',
                'tagline' => 'Money in and out with the runway stated plainly.',
                'tagline_ar' => 'الأموال الداخلة والخارجة مع مدة الاستمرار بوضوح.',
                'summary' => 'Cash in and cash out on one scale, with the closing balance as the headline and the runway spelled out in months. Runway is the number that gets a company killed, so it is not left as arithmetic on a chart.',
                'summary_ar' => 'النقد الداخل والخارج على مقياس واحد، والرصيد الختامي هو العنوان الرئيسي، ومدة الاستمرار مكتوبة بالأشهر. هذه المدة هي الرقم الذي قد يقضي على شركة، فلا تُترك عملية حسابية على مخطط.',
                'accent' => '#059669',
                'tags' => ['finance', 'cashflow', 'runway', 'accounting'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Inflow and outflow on a single shared scale', 'Runway stated in months, not implied', 'Upcoming commitments listed beneath'],
                'features_ar' => ['التدفق الداخل والخارج على مقياس مشترك واحد', 'مدة الاستمرار مذكورة بالأشهر لا ضمنيًّا', 'الالتزامات القادمة مدرجة في الأسفل'],
                'height' => 720,
                'max' => 880,
                'css' => ".bal{display:flex;gap:26px;flex-wrap:wrap;padding:18px 20px;border-bottom:1px solid var(--bd)}\n.bal .k{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700}\n.bal .v{font-size:26px;font-weight:700;margin-top:5px;font-variant-numeric:tabular-nums}\n.due{display:grid;gap:12px}\n.duerow{display:grid;grid-template-columns:auto 1fr auto;gap:11px;align-items:center;font-size:13px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Cash flow', 'Last 6 months', self::legend([['In', Kit::SERIES[2]], ['Out', Kit::SERIES[1]]]))
                        . '<div class="bal">'
                        . '<div><div class="k">Closing balance</div><div class="v">$684,200</div></div>'
                        . '<div><div class="k">Net this month</div><div class="v" style="color:var(--ok)">+$62,400</div></div>'
                        . '<div><div class="k">Runway</div><div class="v">14 months</div></div>'
                        . '</div>'
                        . '<div class="pad">' . self::columns(
                            [
                                ['label' => 'In', 'values' => [188, 206, 224, 232, 241, 248], 'colour' => Kit::SERIES[2]],
                                ['label' => 'Out', 'values' => [160, 172, 168, 180, 176, 186], 'colour' => Kit::SERIES[1]],
                            ],
                            ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                            ['w' => 800, 'h' => 220, 'unit' => '$', 'alt' => 'Cash in and out by month']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:13px">Due in the next 30 days</h3><div class="due">'
                        . '<div class="duerow">' . Kit::iconTile('users', Kit::SERIES[1], 34) . '<span>Payroll · 28 September</span><b class="num">-$128,400</b></div>'
                        . '<div class="duerow">' . Kit::iconTile('server', Kit::SERIES[1], 34) . '<span>Cloud hosting · 01 October</span><b class="num">-$18,200</b></div>'
                        . '<div class="duerow">' . Kit::iconTile('card', Kit::SERIES[2], 34) . '<span>Northwind Ltd invoice · 20 October</span><b class="num" style="color:var(--ok)">+$180,000</b></div>'
                        . '<div class="duerow">' . Kit::iconTile('home', Kit::SERIES[1], 34) . '<span>Office rent · 01 October</span><b class="num">-$18,400</b></div>'
                        . '</div></div>'
                        . '<div class="ft"><span>Figures in thousands on the chart</span><span>Projected balance 20 Oct: $699,200</span></div>'
                    )
                ),
            ],
        ];
    }

    /* ---------------------------------------------------------------- */
    /* Small builders used by the definitions above                      */
    /* ---------------------------------------------------------------- */

    private static function funnelStage(string $label, string $value, float $share, string $colour): string
    {
        return '<div class="stage"><div class="top"><span class="bold">' . $label . '</span><span class="num mut">' . $value . '</span></div>'
            . '<div class="bar" style="width:' . max(18, $share) . '%;background:' . $colour . '">' . $share . '%</div></div>';
    }

    private static function funnelDrop(string $text, bool $worst): string
    {
        return '<div class="drop' . ($worst ? ' worst' : '') . '">' . Kit::icon('arrow-down', 13) . $text . '</div>';
    }

    private static function sourceRow(string $label, string $value, string $share, string $colour): string
    {
        return '<div class="rowi"><span class="dot" style="background:' . $colour . '"></span>'
            . '<span>' . $label . '</span><span class="num bold">' . $value . ' <span class="mut" style="font-weight:500">' . $share . '</span></span></div>';
    }

    private static function goalCard(string $label, int $done, int $target, string $colour, string $tone, string $pace, string $when): string
    {
        $percent = round($done / $target * 100);

        return self::card(
            '<div class="pad" style="text-align:center">'
            . '<div style="display:flex;justify-content:center">' . Kit::ring($percent, $colour, 118) . '</div>'
            . '<h3 style="margin-top:14px">' . $label . '</h3>'
            . '<div class="num" style="font-size:15px;font-weight:650;margin-top:5px">' . number_format($done) . ' of ' . number_format($target) . '</div>'
            . '<div style="margin-top:12px;display:flex;justify-content:center">' . Kit::pill($pace, $tone, true) . '</div>'
            . '<div class="xs mut" style="margin-top:8px">' . $when . '</div></div>'
        );
    }

    private static function csatRow(int $stars, int $count, int $percent, string $colour): string
    {
        return '<div class="csatrow"><span class="row" style="gap:4px;width:34px">' . $stars . Kit::icon('star', 12, 2.4) . '</span>'
            . Kit::meter($percent, $colour, 8) . '<span class="num mut" style="width:52px;text-align:right">' . $count . '</span></div>';
    }

    private static function netRow(string $icon, string $colour, string $name, string $followers, array $trend, string $engagement, string $growth): string
    {
        return '<div class="net">' . Kit::iconTile($icon, $colour, 40)
            . '<span><span class="bold" style="display:block">' . $name . '</span><span class="xs mut">' . $followers . ' · ' . $growth . ' this month</span></span>'
            . '<span class="sp">' . Kit::spark($trend, $colour, 90, 34, false) . '</span>'
            . '<span style="text-align:right"><span class="eng">' . $engagement . '</span><span class="xs mut" style="display:block">engagement</span></span></div>';
    }

    /** @return array<int,array<string,mixed>> */
    private static function setTwo(): array
    {
        return [
            [
                'slug' => 'marketing-campaign-dashboard',
                'name' => 'Campaign performance dashboard',
                'name_ar' => 'لوحة أداء الحملات',
                'tagline' => 'Spend, return and cost per acquisition per channel.',
                'tagline_ar' => 'الإنفاق والعائد وتكلفة الاكتساب لكل قناة.',
                'summary' => 'Marketing spend judged on return rather than reach: every channel carries its cost per acquisition beside its spend, and the chart is spend and revenue on one scale so a channel that costs more than it returns is visibly upside down.',
                'summary_ar' => 'إنفاق تسويقي يُقاس بالعائد لا بالوصول: كل قناة تعرض تكلفة الاكتساب بجانب إنفاقها، والمخطط يضع الإنفاق والإيرادات على مقياس واحد، فتظهر القناة التي تكلّف أكثر مما تعود به مقلوبة بوضوح.',
                'accent' => '#db2777',
                'tags' => ['marketing', 'campaigns', 'roas', 'spend'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Spend and revenue compared on one shared scale', 'Cost per acquisition per channel', 'Unprofitable channels flagged in status colour'],
                'features_ar' => ['مقارنة الإنفاق والإيرادات على مقياس مشترك واحد', 'تكلفة الاكتساب لكل قناة', 'القنوات الخاسرة مميّزة بلون الحالة'],
                'height' => 720,
                'max' => 980,
                'css' => ".chan{display:grid;grid-template-columns:auto 1fr repeat(3,minmax(72px,auto));gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid var(--bd);font-size:13px}\n.chan:last-child{border-bottom:0}\n.chan .k{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);font-weight:700;display:block}\n@media (max-width:620px){.chan{grid-template-columns:auto 1fr auto}}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Campaigns</h1><p class="sub">September 2026 · 5 active channels</p></div>'
                    . self::range() . '</div>',
                    self::grid(
                        210,
                        self::tile('Spend', '$48,200', 'wallet', Kit::SERIES[1], null, '+12.4%', false),
                        self::tile('Attributed revenue', '$186,400', 'trend-up', Kit::SERIES[2], null, '+24.1%', true),
                        self::tile('Return on ad spend', '3.87x', 'zap', Kit::SERIES[0], null, '+0.42x', true),
                        self::tile('Cost per acquisition', '$28.40', 'user', Kit::SERIES[3], null, '-$4.10', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Spend against attributed revenue', 'By channel, in thousands', self::legend([['Spend', Kit::SERIES[1]], ['Revenue', Kit::SERIES[2]]]))
                        . '<div class="pad">' . self::columns(
                            [
                                ['label' => 'Spend', 'values' => [18.2, 12.4, 8.8, 5.4, 3.4], 'colour' => Kit::SERIES[1]],
                                ['label' => 'Revenue', 'values' => [82.4, 41.2, 28.6, 22.8, 11.4], 'colour' => Kit::SERIES[2]],
                            ],
                            ['Search', 'Social', 'Display', 'Email', 'Affiliate'],
                            ['w' => 880, 'h' => 220, 'unit' => '$', 'alt' => 'Spend against revenue by channel']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . self::channelRow('search', Kit::SERIES[0], 'Paid search', '$18,200', '4.53x', '$22.10', true)
                        . self::channelRow('users', Kit::SERIES[4], 'Paid social', '$12,400', '3.32x', '$31.80', true)
                        . self::channelRow('image', Kit::SERIES[3], 'Display', '$8,800', '3.25x', '$36.40', true)
                        . self::channelRow('mail', Kit::SERIES[2], 'Email', '$5,400', '4.22x', '$14.20', true)
                        . self::channelRow('link', Kit::SERIES[7], 'Affiliate', '$3,400', '3.35x', '$48.90', false)
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'seo-performance-panel',
                'name' => 'SEO performance panel',
                'name_ar' => 'لوحة أداء السيو',
                'tagline' => 'Impressions, clicks, CTR and average position.',
                'tagline_ar' => 'مرات الظهور والنقرات ونسبة النقر ومتوسط الترتيب.',
                'summary' => 'Search console in miniature: clicks and impressions on one indexed scale rather than two axes, and average position shown inverted so that up always means better - the one chart where a rising line would otherwise be bad news.',
                'summary_ar' => 'Search Console مصغّرًا: النقرات ومرات الظهور على مقياس مفهرس واحد بدل محورين، ومتوسط الترتيب معروض معكوسًا ليعني الصعود دائمًا الأفضل، فهذا هو المخطط الوحيد الذي يكون فيه الخط الصاعد خبرًا سيئًا لولا ذلك.',
                'accent' => '#16a34a',
                'tags' => ['seo', 'search', 'traffic', 'rankings'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Clicks and impressions indexed to one scale', 'Position axis inverted so up is better', 'Top queries ranked underneath'],
                'features_ar' => ['النقرات ومرات الظهور مفهرسة على مقياس واحد', 'محور الترتيب معكوس ليكون الأعلى هو الأفضل', 'أهم عبارات البحث مرتّبة في الأسفل'],
                'height' => 740,
                'max' => 880,
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Clicks', '48,210', 'arrow-right', Kit::SERIES[0], [30, 33, 32, 36, 38, 41, 44, 48], '+18.4%', true),
                        self::tile('Impressions', '1.24M', 'eye', Kit::SERIES[1], [60, 64, 62, 70, 74, 78, 82, 88], '+22.1%', true),
                        self::tile('Click-through rate', '3.9%', 'zap', Kit::SERIES[2], [34, 35, 34, 36, 35, 37, 38, 39], '-0.2pt', false),
                        self::tile('Average position', '10.8', 'trend-up', Kit::SERIES[3], [18, 17, 16, 15, 14, 13, 12, 11], '-2.4', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Clicks and impressions', 'Indexed to 100 at the start of the period', self::legend([['Clicks', Kit::SERIES[0]], ['Impressions', Kit::SERIES[1]]]))
                        . '<div class="pad">' . self::line(
                            [
                                ['label' => 'Clicks', 'values' => [100, 104, 102, 112, 118, 124, 131, 138, 142, 148, 154, 162]],
                                ['label' => 'Impressions', 'values' => [100, 106, 110, 114, 122, 128, 134, 141, 148, 152, 158, 166]],
                            ],
                            ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8', 'W9', 'W10', 'W11', 'W12'],
                            ['w' => 800, 'h' => 220, 'min' => 90, 'alt' => 'Clicks and impressions indexed over twelve weeks']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:13px">Top queries</h3><div style="display:grid;gap:14px">'
                        . self::rank('free svg editor online', 'position 3 · CTR 8.2%', '12,480', 100, Kit::SERIES[0])
                        . self::rank('convert image to webp', 'position 2 · CTR 11.4%', '9,820', 79, Kit::SERIES[0])
                        . self::rank('html table template', 'position 7 · CTR 4.1%', '6,140', 49, Kit::SERIES[0])
                        . self::rank('icon library download', 'position 14 · CTR 1.8%', '3,220', 26, Kit::SERIES[0])
                        . '</div></div>'
                        . '<div class="ft"><span>412 queries tracked</span><span>Position improved on 284 of them</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'app-usage-analytics',
                'name' => 'App usage analytics',
                'name_ar' => 'لوحة تحليلات استخدام التطبيق',
                'tagline' => 'Active users, session length and feature adoption.',
                'tagline_ar' => 'المستخدمون النشطون وطول الجلسة واعتماد الميزات.',
                'summary' => 'Product analytics answering whether the thing that shipped is being used: daily over monthly actives as a stickiness ratio, and feature adoption as a ranked list rather than a wall of event counts.',
                'summary_ar' => 'تحليلات منتج تجيب عن سؤال: هل يُستخدم ما أطلقناه؟ النشطون يوميًّا إلى النشطين شهريًّا كنسبة التصاق، واعتماد الميزات كقائمة مرتّبة بدل جدار من أعداد الأحداث.',
                'accent' => '#4f46e5',
                'tags' => ['product', 'analytics', 'usage', 'adoption'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Stickiness as daily over monthly actives', 'Feature adoption ranked by reach', 'Session length distribution rather than an average'],
                'features_ar' => ['الالتصاق كنسبة النشطين يوميًّا إلى الشهريين', 'اعتماد الميزات مرتّب حسب مدى الوصول', 'توزيع طول الجلسات بدل المتوسط'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Product usage</h1><p class="sub">Last 30 days · web and mobile</p></div>'
                    . self::range() . '</div>',
                    self::grid(
                        200,
                        self::tile('Daily active', '18,420', 'users', Kit::SERIES[0], [30, 32, 34, 33, 36, 38, 41, 44], '+9.4%', true),
                        self::tile('Monthly active', '84,210', 'user', Kit::SERIES[1], [60, 62, 66, 68, 72, 76, 80, 84], '+12.1%', true),
                        self::tile('Stickiness (DAU/MAU)', '21.9%', 'zap', Kit::SERIES[2], [18, 19, 19, 20, 20, 21, 21, 22], '+1.4pt', true),
                        self::tile('Median session', '6m 42s', 'clock', Kit::SERIES[3], [28, 30, 29, 32, 34, 33, 36, 38], '+48s', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Feature adoption', 'Share of monthly actives who used it')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Component gallery', '64,180 users', '76%', 76, Kit::SERIES[0])
                            . self::rank('Drawing editor', '41,220 users', '49%', 49, Kit::SERIES[0])
                            . self::rank('Icon library', '32,840 users', '39%', 39, Kit::SERIES[0])
                            . self::rank('File conversion', '18,940 users', '22%', 22, Kit::SERIES[0])
                            . self::rank('Templates panel', '9,260 users', '11%', 11, Kit::SERIES[3])
                            . '</div>'
                            . '<div class="ft"><span>5 of 14 features</span><span>Templates shipped 12 days ago</span></div>'
                        ),
                        self::card(
                            self::head('Session length', 'Distribution across 482k sessions')
                            . '<div class="pad">' . self::columns(
                                [['label' => 'Sessions', 'values' => [128, 164, 92, 54, 28, 16]]],
                                ['<1m', '1-5m', '5-15m', '15-30m', '30-60m', '60m+'],
                                ['w' => 460, 'h' => 220, 'alt' => 'Session length distribution']
                            ) . '</div>'
                            . '<div class="ft"><span>In thousands of sessions</span><span>34% last longer than five minutes</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'device-breakdown-panel',
                'name' => 'Device and browser breakdown',
                'name_ar' => 'لوحة توزع الأجهزة والمتصفحات',
                'tagline' => 'Device split, browsers and screen widths.',
                'tagline_ar' => 'توزيع الأجهزة والمتصفحات وعروض الشاشات.',
                'summary' => 'The panel that settles arguments about which breakpoints matter: real screen-width buckets alongside the device split, so a decision about mobile layout rests on the traffic rather than on instinct.',
                'summary_ar' => 'اللوحة التي تحسم الجدل حول نقاط التوقف المهمة: فئات حقيقية لعروض الشاشات بجانب توزيع الأجهزة، فيستند قرار تخطيط الجوال إلى الزيارات الفعلية لا إلى الحدس.',
                'accent' => '#0891b2',
                'tags' => ['devices', 'browsers', 'breakpoints', 'analytics'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Screen-width buckets that map to breakpoints', 'Browser share with version spread', 'Device split as a donut with values listed'],
                'features_ar' => ['فئات عروض الشاشة مطابقة لنقاط التوقف', 'حصة المتصفحات مع توزّع الإصدارات', 'توزيع الأجهزة في مخطط دائري مجوّف مع إدراج القيم'],
                'height' => 700,
                'max' => 880,
                'css' => ".rows{display:grid;gap:12px}\n.rowi{display:grid;grid-template-columns:12px 1fr auto;gap:10px;align-items:center;font-size:13px}\n.dot{width:12px;height:12px;border-radius:4px}",
                'body' => self::wrap(
                    self::grid(
                        320,
                        self::card(
                            self::head('Devices', 'Sessions, last 30 days')
                            . '<div class="pad" style="display:flex;justify-content:center">'
                            . Kit::donut([[68, Kit::SERIES[0]], [28, Kit::SERIES[1]], [4, Kit::SERIES[2]]], 158, '103.6k', 'sessions')
                            . '</div>'
                            . '<div class="pad" style="padding-top:0"><div class="rows">'
                            . self::sourceRow('Mobile', '70,489', '68.0%', Kit::SERIES[0])
                            . self::sourceRow('Desktop', '29,025', '28.0%', Kit::SERIES[1])
                            . self::sourceRow('Tablet', '4,147', '4.0%', Kit::SERIES[2])
                            . '</div></div>'
                        ),
                        self::card(
                            self::head('Browsers', 'Share of sessions')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Chrome', '128 - 141', '58.2%', 58, Kit::SERIES[0])
                            . self::rank('Safari', '17 - 18', '24.1%', 24, Kit::SERIES[0])
                            . self::rank('Edge', '126 - 140', '9.4%', 9, Kit::SERIES[0])
                            . self::rank('Firefox', '128 - 133', '6.1%', 6, Kit::SERIES[0])
                            . self::rank('Other', 'Samsung Internet, Opera', '2.2%', 2, Kit::SERIES[0])
                            . '</div>'
                        )
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Screen widths', 'Where your breakpoints should be')
                        . '<div class="pad">' . self::columns(
                            [['label' => 'Sessions', 'values' => [8.2, 32.4, 22.1, 12.8, 18.4, 9.1]]],
                            ['<360', '360-413', '414-767', '768-1023', '1024-1439', '1440+'],
                            ['w' => 820, 'h' => 200, 'alt' => 'Sessions by screen width bucket']
                        ) . '</div>'
                        . '<div class="ft"><span>Percentage of sessions</span><span>62.7% of traffic is under 768px wide</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'regional-sales-panel',
                'name' => 'Regional sales panel',
                'name_ar' => 'لوحة المبيعات حسب المنطقة',
                'tagline' => 'Country rows with share meters and growth.',
                'tagline_ar' => 'صفوف للدول مع مقاييس الحصة والنمو.',
                'summary' => 'A map looks impressive and answers nothing, so this is a ranked country list instead: share, value and growth per row, which is what a regional review is actually conducted from.',
                'summary_ar' => 'الخريطة تبدو مبهرة لكنها لا تجيب عن شيء، لذا هذه قائمة دول مرتّبة: الحصة والقيمة والنمو في كل صف، وهذا ما تُجرى عليه المراجعات الإقليمية فعلًا.',
                'accent' => '#0f766e',
                'tags' => ['sales', 'regions', 'geography', 'ranking'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Ranked countries with share meters', 'Growth stated per market', 'Regional subtotals in the footer'],
                'features_ar' => ['دول مرتّبة مع مقاييس الحصة', 'النمو مذكور لكل سوق', 'مجاميع فرعية للمناطق في التذييل'],
                'height' => 680,
                'max' => 720,
                'css' => ".ctry{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid var(--bd)}\n.ctry:last-child{border-bottom:0}\n.flagish{width:34px;height:24px;border-radius:5px;flex:none}",
                'body' => self::wrap(
                    self::card(
                        self::head('Sales by country', 'Last 90 days', self::range('Last 90 days'))
                        . '<div class="pad">'
                        . self::countryRow('#0f766e', 'Saudi Arabia', 'Riyadh, Jeddah, Dammam', '$248,120', 100, '+18.4%', true)
                        . self::countryRow('#1d4ed8', 'United Arab Emirates', 'Dubai, Abu Dhabi', '$142,800', 58, '+24.1%', true)
                        . self::countryRow('#b45309', 'Jordan', 'Amman, Irbid', '$88,400', 36, '+6.2%', true)
                        . self::countryRow('#7c3aed', 'Egypt', 'Cairo, Alexandria', '$64,200', 26, '-4.8%', false)
                        . self::countryRow('#be123c', 'Qatar', 'Doha', '$42,100', 17, '+11.9%', true)
                        . self::countryRow('#475569', 'Rest of world', '14 countries', '$28,640', 12, '+2.4%', true)
                        . '</div>'
                        . '<div class="ft"><span>Gulf region is 71% of revenue</span><span class="bold num">$614,260 total</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'weather-forecast-card',
                'name' => 'Weather forecast card',
                'name_ar' => 'بطاقة توقعات الطقس',
                'tagline' => 'Current conditions with an hourly temperature curve.',
                'tagline_ar' => 'الأحوال الحالية مع منحنى درجات الحرارة بالساعة.',
                'summary' => 'A forecast card where the temperature curve is the centrepiece and the daily rows are secondary. The sun and moon marks are drawn inline, so the card carries no weather-icon dependency at all.',
                'summary_ar' => 'بطاقة توقعات يكون فيها منحنى الحرارة محور الاهتمام والصفوف اليومية ثانوية. رموز الشمس والقمر مرسومة مباشرة في الصفحة، فلا تعتمد البطاقة على أي مكتبة أيقونات للطقس.',
                'accent' => '#0ea5e9',
                'tags' => ['weather', 'forecast', 'widget', 'card'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Hourly temperature curve as an area chart', 'Seven-day rows with high and low bars', 'Weather marks drawn inline, no icon set'],
                'features_ar' => ['منحنى الحرارة بالساعة كمخطط مساحي', 'صفوف لسبعة أيام مع أشرطة للعظمى والصغرى', 'رموز الطقس مرسومة مباشرة دون مجموعة أيقونات'],
                'height' => 700,
                'max' => 460,
                'theme' => 'dark',
                'css' => ".now{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 20px}\n.temp{font-size:56px;font-weight:700;letter-spacing:-.04em;line-height:1}\n.days{display:grid;gap:2px;padding:6px 20px 16px}\n.day{display:grid;grid-template-columns:44px auto 1fr auto;gap:12px;align-items:center;padding:9px 0;font-size:13px}\n.range{height:6px;border-radius:999px;background:linear-gradient(90deg,#38bdf8,#fbbf24);position:relative}",
                'body' => self::wrap(
                    self::card(
                        '<div class="now"><div><div class="sm mut">Riyadh</div>'
                        . '<div class="temp num">34°</div>'
                        . '<div class="sm" style="margin-top:6px">Clear · feels like 37°</div></div>'
                        . '<span style="color:#fbbf24">' . Kit::icon('sun', 64, 1.4) . '</span></div>',
                        ''
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Today', 'Hourly temperature')
                        . '<div class="pad">' . self::line(
                            [['label' => 'Temperature', 'values' => [26, 25, 28, 32, 36, 39, 41, 40, 37, 33, 30, 28]]],
                            ['00', '02', '04', '06', '08', '10', '12', '14', '16', '18', '20', '22'],
                            ['w' => 400, 'h' => 170, 'area' => true, 'min' => 20, 'dark' => true, 'alt' => 'Hourly temperature today']
                        ) . '</div>'
                        . '<div class="days">'
                        . self::forecastDay('Sun', 'sun', 41, 26, 0)
                        . self::forecastDay('Mon', 'sun', 39, 25, 0)
                        . self::forecastDay('Tue', 'moon', 36, 24, 10)
                        . self::forecastDay('Wed', 'sun', 38, 25, 0)
                        . self::forecastDay('Thu', 'sun', 40, 27, 0)
                        . self::forecastDay('Fri', 'moon', 34, 23, 30)
                        . self::forecastDay('Sat', 'sun', 35, 22, 5)
                        . '</div>'
                        . '<div class="ft"><span>Sunrise 05:48 · sunset 18:12</span><span>Updated 10 minutes ago</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'fitness-activity-dashboard',
                'name' => 'Fitness activity dashboard',
                'name_ar' => 'لوحة النشاط الرياضي',
                'tagline' => 'Move, exercise and stand rings with a weekly bar chart.',
                'tagline_ar' => 'حلقات الحركة والتمرين والوقوف مع مخطط أسبوعي بالأشرطة.',
                'summary' => 'Three concentric goals shown as separate rings rather than nested ones, because nested rings make three percentages impossible to compare. The week below shows which days carried the total.',
                'summary_ar' => 'ثلاثة أهداف معروضة في حلقات منفصلة لا متداخلة، لأن الحلقات المتداخلة تجعل مقارنة النسب الثلاث مستحيلة. ويُظهر الأسبوع في الأسفل الأيام التي صنعت المجموع.',
                'accent' => '#e11d48',
                'tags' => ['fitness', 'health', 'rings', 'activity'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Separate rings so the three goals stay comparable', 'Weekly bars with today highlighted', 'Streak and personal best in the footer'],
                'features_ar' => ['حلقات منفصلة لتبقى الأهداف الثلاثة قابلة للمقارنة', 'أشرطة أسبوعية مع إبراز اليوم الحالي', 'سلسلة الأيام المتتالية وأفضل رقم شخصي في التذييل'],
                'height' => 680,
                'max' => 560,
                'theme' => 'dark',
                'css' => ".rings{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;padding:20px}\n.rings h4{margin-top:10px}\n.rings .v{font-size:12.5px;color:var(--mut);margin-top:3px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Today', 'Sunday 20 September', Kit::pill('Goal met', 'ok', true))
                        . '<div class="rings">'
                        . '<div>' . Kit::ring(104, Kit::SERIES_DARK[7], 104) . '<h4>Move</h4><div class="v">624 / 600 kcal</div></div>'
                        . '<div>' . Kit::ring(83, Kit::SERIES_DARK[2], 104) . '<h4>Exercise</h4><div class="v">25 / 30 min</div></div>'
                        . '<div>' . Kit::ring(92, Kit::SERIES_DARK[0], 104) . '<h4>Stand</h4><div class="v">11 / 12 hours</div></div>'
                        . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<div class="row" style="justify-content:space-between;margin-bottom:10px"><h3>This week</h3><span class="xs mut">Active calories</span></div>'
                        . self::columns(
                            [['label' => 'Calories', 'values' => [480, 620, 540, 710, 390, 660, 624], 'colour' => Kit::SERIES_DARK[7]]],
                            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            ['w' => 480, 'h' => 180, 'dark' => true, 'alt' => 'Active calories per day this week']
                        ) . '</div>'
                        . '<div class="ft"><span>12-day streak</span><span>Best this month: 810 kcal on the 6th</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'project-status-dashboard',
                'name' => 'Project status dashboard',
                'name_ar' => 'لوحة حالة المشاريع',
                'tagline' => 'Project cards with progress, risk and next milestone.',
                'tagline_ar' => 'بطاقات مشاريع مع التقدم والمخاطر والمعلم التالي.',
                'summary' => 'A portfolio view where risk is a status chip rather than a colour on a bar, and every project names its next milestone with a date. A project without a next date is the definition of a stalled project, and it shows here.',
                'summary_ar' => 'عرض لمحفظة المشاريع تكون فيه المخاطر شارة حالة لا لونًا على شريط، ويذكر كل مشروع معلمه التالي مع تاريخه. المشروع الذي لا تاريخ تاليًا له هو بالتعريف مشروع متعثر، وهنا يظهر ذلك.',
                'accent' => '#7c3aed',
                'tags' => ['projects', 'status', 'portfolio', 'milestones'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Risk as a labelled status chip, never colour alone', 'Next milestone and date on every project', 'Team shown as an avatar stack'],
                'features_ar' => ['المخاطر كشارة حالة موسومة، لا لونًا وحده أبدًا', 'المعلم التالي وتاريخه في كل مشروع', 'الفريق معروض كصور رمزية متراصّة'],
                'height' => 700,
                'max' => 980,
                'css' => ".proj{display:grid;gap:13px}\n.proj .top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}\n.proj .mile{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--mut);padding-top:12px;border-top:1px solid var(--bd)}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Projects</h1><p class="sub">8 active · 2 at risk</p></div>'
                    . self::btn('New project', 'plus', 'pri') . '</div>',
                    self::grid(
                        300,
                        self::projectCard('Component library v2', 'Design system', 78, Kit::SERIES[0], 'On track', 'ok', 'Beta release · 28 Sep', ['Lina Haddad', 'Omar Saleh', 'Sara Aziz']),
                        self::projectCard('Drawing editor rewrite', 'Platform', 42, Kit::SERIES[1], 'At risk', 'bad', 'Canvas migration · 02 Oct', ['Maya Rahman', 'Nour Sabbagh']),
                        self::projectCard('Arabic localisation', 'Content', 91, Kit::SERIES[2], 'On track', 'ok', 'Final review · 24 Sep', ['Sara Aziz', 'Karim Nasser']),
                        self::projectCard('Billing migration', 'Finance systems', 24, Kit::SERIES[3], 'Needs attention', 'warn', 'Vendor decision · 30 Sep', ['Omar Saleh']),
                        self::projectCard('Mobile app beta', 'Mobile', 61, Kit::SERIES[4], 'On track', 'ok', 'TestFlight build · 26 Sep', ['Nour Sabbagh', 'Lina Haddad']),
                        self::projectCard('Search rebuild', 'Platform', 8, Kit::SERIES[6], 'Not started', 'neutral', 'Kick-off · 05 Oct', ['Maya Rahman'])
                    )
                ),
            ],
            [
                'slug' => 'team-workload-panel',
                'name' => 'Team workload panel',
                'name_ar' => 'لوحة أعباء الفريق',
                'tagline' => 'Capacity per person with overallocation flagged.',
                'tagline_ar' => 'السعة لكل شخص مع إبراز التحميل الزائد.',
                'summary' => 'Workload as assigned hours against capacity, so anyone over their week is visible instead of discovered on Friday. The meter turns red past 100%, which is the only honest way to draw an overallocated person.',
                'summary_ar' => 'عبء العمل كساعات مُسندة مقابل السعة، فيظهر من تجاوز أسبوعه بدل أن يُكتشف يوم الجمعة. يتحول المقياس إلى الأحمر بعد 100%، وهي الطريقة الصادقة الوحيدة لتمثيل شخص محمّل فوق طاقته.',
                'accent' => '#4f46e5',
                'tags' => ['team', 'capacity', 'workload', 'planning'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Assigned hours against weekly capacity', 'Overallocation flagged past 100%', 'Task count and next deadline per person'],
                'features_ar' => ['الساعات المُسندة مقابل السعة الأسبوعية', 'إبراز التحميل الزائد بعد 100%', 'عدد المهام والموعد النهائي التالي لكل شخص'],
                'height' => 640,
                'max' => 720,
                'css' => ".ppl{display:grid;gap:18px}\n.per{display:grid;gap:9px}\n.per .top{display:flex;justify-content:space-between;gap:12px;align-items:center}\n.over{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    self::card(
                        self::head('Workload', 'Week 38 · 15 - 21 September', self::btn('Rebalance', 'sliders'))
                        . '<div class="pad"><div class="ppl">'
                        . self::workloadRow('Omar Saleh', 'Engineering · 6 tasks', 52, 40, Kit::SERIES[0])
                        . self::workloadRow('Lina Haddad', 'Design · 4 tasks', 36, 40, Kit::SERIES[0])
                        . self::workloadRow('Maya Rahman', 'QA · 9 tasks', 44, 40, Kit::SERIES[0])
                        . self::workloadRow('Sara Aziz', 'Content · 3 tasks', 22, 40, Kit::SERIES[0])
                        . self::workloadRow('Karim Nasser', 'Support · 5 tasks', 38, 40, Kit::SERIES[0])
                        . '</div></div>'
                        . '<div class="ft"><span>2 people over capacity</span><span>Team utilisation 96%</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'inventory-health-dashboard',
                'name' => 'Inventory health dashboard',
                'name_ar' => 'لوحة صحة المخزون',
                'tagline' => 'Stock value, cover days and items needing a reorder.',
                'tagline_ar' => 'قيمة المخزون وأيام التغطية والأصناف التي تحتاج إعادة طلب.',
                'summary' => 'Inventory judged on cover rather than count: days of stock left at the current rate of sale is the number that decides a purchase order, and the items below it are listed in the order they will run out.',
                'summary_ar' => 'مخزون يُقاس بالتغطية لا بالعدد: أيام المخزون المتبقية بمعدل البيع الحالي هي الرقم الذي يحسم أمر الشراء، والأصناف الأدنى منها مدرجة بترتيب نفادها.',
                'accent' => '#0d9488',
                'tags' => ['inventory', 'stock', 'supply-chain', 'reorder'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Days of cover rather than raw stock counts', 'Reorder list ordered by when it runs out', 'Dead stock called out separately'],
                'features_ar' => ['أيام التغطية بدل أعداد المخزون الخام', 'قائمة إعادة الطلب مرتّبة حسب موعد النفاد', 'المخزون الراكد مُبرَز على حدة'],
                'height' => 720,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Inventory</h1><p class="sub">Warehouse A · 412 SKUs</p></div>'
                    . '<div class="row" style="gap:8px">' . Kit::pill('7 need reordering', 'warn', true) . self::btn('Purchase order', 'plus', 'pri') . '</div></div>',
                    self::grid(
                        200,
                        self::tile('Stock value', '$1.24M', 'box', Kit::SERIES[0], null, '+4.2%', true),
                        self::tile('Days of cover', '38 days', 'clock', Kit::SERIES[2], [52, 48, 46, 44, 42, 41, 39, 38], '-6 days', false),
                        self::tile('Stock turns', '9.6 / year', 'refresh', Kit::SERIES[1], null, '+0.8', true),
                        self::tile('Dead stock', '$84,200', 'alert', Kit::SERIES[7], null, '+$12,400', false)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Running out first', 'At the current rate of sale')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Vista Shelving', 'SHL-0302 · out of stock', '0 days', 0, Kit::SERIES[7])
                            . self::rank('Halo Floor Lamp', 'LMP-0088 · 4 left', '2 days', 5, Kit::SERIES[7])
                            . self::rank('Nimbus Chair', 'CHR-2210 · 57 left', '9 days', 24, Kit::SERIES[3])
                            . self::rank('Terra Side Table', 'TBL-1187 · 188 left', '26 days', 68, Kit::SERIES[2])
                            . self::rank('Aurora Desk Lamp', 'LMP-0041 · 412 left', '41 days', 100, Kit::SERIES[2])
                            . '</div>'
                            . '<div class="ft"><span>Lead time averages 18 days</span><span class="bold" style="color:var(--bad)">2 already late to order</span></div>'
                        ),
                        self::card(
                            self::head('Stock value by category', 'In thousands')
                            . '<div class="pad">' . self::columns(
                                [['label' => 'Value', 'values' => [412, 388, 214, 148, 78]]],
                                ['Lighting', 'Seating', 'Tables', 'Storage', 'Textiles'],
                                ['w' => 460, 'h' => 220, 'unit' => '$', 'alt' => 'Stock value by category']
                            ) . '</div>'
                            . '<div class="ft"><span>5 categories</span><span>Lighting is 33% of value</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'delivery-operations-dashboard',
                'name' => 'Delivery operations dashboard',
                'name_ar' => 'لوحة عمليات التوصيل',
                'tagline' => 'On-time rate, active routes and driver performance.',
                'tagline_ar' => 'نسبة التسليم في الموعد والمسارات النشطة وأداء السائقين.',
                'summary' => 'Last-mile operations at a glance: on-time delivery as the headline, live route states in the middle, and drivers ranked by completed drops - with late deliveries broken out by cause, since traffic and wrong addresses need different fixes.',
                'summary_ar' => 'عمليات الميل الأخير بلمحة: التسليم في الموعد هو العنوان، وحالات المسارات المباشرة في الوسط، والسائقون مرتّبون حسب التسليمات المنجزة، مع تقسيم التأخيرات حسب السبب، لأن الازدحام والعناوين الخاطئة يحتاجان حلولًا مختلفة.',
                'accent' => '#7c3aed',
                'tags' => ['logistics', 'delivery', 'routes', 'operations'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['On-time rate with the failure causes broken out', 'Live route states with drop counts', 'Driver leaderboard by completed drops'],
                'features_ar' => ['نسبة التسليم في الموعد مع تقسيم أسباب الإخفاق', 'حالات المسارات المباشرة مع أعداد التسليمات', 'ترتيب السائقين حسب التسليمات المنجزة'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Deliveries</h1><p class="sub">Today · 18 routes · 412 drops</p></div>'
                    . '<div class="row" style="gap:8px">' . Kit::pill('14 routes running', 'ok', true) . self::range('Today') . '</div></div>',
                    self::grid(
                        200,
                        self::tile('On time', '94.2%', 'check', Kit::SERIES[2], [88, 90, 89, 92, 91, 93, 94, 94], '+1.8pt', true, 'vs yesterday'),
                        self::tile('Drops completed', '318 / 412', 'box', Kit::SERIES[0], null, '77% done', true, 'by 15:40'),
                        self::tile('Average per drop', '11m 20s', 'clock', Kit::SERIES[1], [14, 13, 14, 12, 13, 12, 11, 11], '-1m 40s', true, 'vs yesterday'),
                        self::tile('Failed', '6', 'alert', Kit::SERIES[7], null, '-4', true, 'vs yesterday')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Why deliveries were late', 'Last 30 days')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Traffic', '188 deliveries', '42%', 100, Kit::SERIES[0])
                            . self::rank('Nobody at the address', '124 deliveries', '28%', 66, Kit::SERIES[0])
                            . self::rank('Wrong or incomplete address', '82 deliveries', '18%', 44, Kit::SERIES[0])
                            . self::rank('Vehicle problem', '34 deliveries', '8%', 18, Kit::SERIES[0])
                            . self::rank('Other', '18 deliveries', '4%', 10, Kit::SERIES[0])
                            . '</div>'
                            . '<div class="ft"><span>446 late of 7,680</span><span>Address quality is fixable</span></div>'
                        ),
                        self::card(
                            self::head('Drivers today', 'Completed drops')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Omar Saleh', 'Route A · North', '42', 100, Kit::SERIES[2], Kit::avatar('Omar Saleh', 30))
                            . self::rank('Karim Nasser', 'Route C · East', '38', 90, Kit::SERIES[2], Kit::avatar('Karim Nasser', 30))
                            . self::rank('Nour Sabbagh', 'Route B · West', '34', 81, Kit::SERIES[2], Kit::avatar('Nour Sabbagh', 30))
                            . self::rank('Yusuf Barak', 'Route D · South', '28', 67, Kit::SERIES[2], Kit::avatar('Yusuf Barak', 30))
                            . '</div>'
                            . '<div class="ft"><span>14 drivers out today</span><span>Average 22 drops each</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'restaurant-sales-dashboard',
                'name' => 'Restaurant sales dashboard',
                'name_ar' => 'لوحة مبيعات المطعم',
                'tagline' => 'Covers, average spend and sales by service.',
                'tagline_ar' => 'عدد الروّاد ومتوسط الإنفاق والمبيعات حسب الوجبة.',
                'summary' => 'A restaurant is run by service, not by day, so lunch and dinner are separated everywhere. Average spend per cover sits beside total sales, because a busy night at a low average is a different problem from a quiet one.',
                'summary_ar' => 'يُدار المطعم بالوجبة لا باليوم، لذا يُفصل الغداء عن العشاء في كل مكان. ويظهر متوسط الإنفاق لكل زبون بجانب إجمالي المبيعات، لأن ليلة مزدحمة بمتوسط منخفض مشكلة تختلف عن ليلة هادئة.',
                'accent' => '#c2410c',
                'tags' => ['restaurant', 'hospitality', 'sales', 'covers'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Lunch and dinner separated throughout', 'Average spend per cover alongside totals', 'Top dishes ranked by contribution'],
                'features_ar' => ['الغداء والعشاء مفصولان في كل الأقسام', 'متوسط الإنفاق لكل زبون بجانب الإجماليات', 'أفضل الأطباق مرتّبة حسب مساهمتها'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Marjan Kitchen</h1><p class="sub">Week 38 · 15 - 21 September</p></div>'
                    . self::range('This week') . '</div>',
                    self::grid(
                        200,
                        self::tile('Sales', 'SAR 184,200', 'wallet', Kit::SERIES[0], [24, 26, 25, 30, 34, 42, 38], '+12.4%', true, 'vs last week'),
                        self::tile('Covers', '1,482', 'users', Kit::SERIES[1], [180, 190, 186, 210, 240, 290, 260], '+6.1%', true, 'vs last week'),
                        self::tile('Average spend', 'SAR 124', 'tag', Kit::SERIES[2], null, '+SAR 8', true, 'vs last week'),
                        self::tile('Table turns', '2.4', 'refresh', Kit::SERIES[3], null, '+0.2', true, 'vs last week')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Sales by service', 'In thousands of riyals', self::legend([['Lunch', Kit::SERIES[3]], ['Dinner', Kit::SERIES[0]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Lunch', 'values' => [8.2, 8.8, 8.4, 10.1, 12.4, 14.8, 13.2], 'colour' => Kit::SERIES[3]],
                                    ['label' => 'Dinner', 'values' => [14.1, 15.2, 14.8, 18.4, 22.1, 28.4, 24.8], 'colour' => Kit::SERIES[0]],
                                ],
                                ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                                ['w' => 480, 'h' => 220, 'alt' => 'Sales by service and day']
                            ) . '</div>'
                            . '<div class="ft"><span>Dinner is 64% of sales</span><span>Saturday is the strongest night</span></div>'
                        ),
                        self::card(
                            self::head('Top dishes', 'By contribution this week')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Dry-aged ribeye', '182 sold · SAR 42', 'SAR 7,644', 100, Kit::SERIES[0])
                            . self::rank('Wild mushroom risotto', '241 sold · SAR 26', 'SAR 6,266', 82, Kit::SERIES[0])
                            . self::rank('Charred octopus', '168 sold · SAR 18', 'SAR 3,024', 40, Kit::SERIES[0])
                            . self::rank('Burrata', '204 sold · SAR 14', 'SAR 2,856', 37, Kit::SERIES[0])
                            . self::rank('Sea bass', '86 sold · SAR 34', 'SAR 2,924', 38, Kit::SERIES[0])
                            . '</div>'
                            . '<div class="ft"><span>Top 5 of 34 dishes</span><span>They are 38% of food sales</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'hotel-occupancy-dashboard',
                'name' => 'Hotel occupancy dashboard',
                'name_ar' => 'لوحة إشغال الفندق',
                'tagline' => 'Occupancy, average rate and revenue per room.',
                'tagline_ar' => 'نسبة الإشغال ومتوسط السعر والإيراد لكل غرفة.',
                'summary' => 'The three numbers hotels are actually compared on - occupancy, average daily rate and revenue per available room - with the forward-booking curve underneath, which is the only part of the data that is about the future.',
                'summary_ar' => 'الأرقام الثلاثة التي تُقارن بها الفنادق فعلًا: الإشغال، ومتوسط السعر اليومي، والإيراد لكل غرفة متاحة، مع منحنى الحجوزات المستقبلية أسفلها، وهو الجزء الوحيد من البيانات الذي يتحدث عن المستقبل.',
                'accent' => '#b45309',
                'tags' => ['hotel', 'occupancy', 'revpar', 'hospitality'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Occupancy, ADR and RevPAR together', 'Forward booking curve for the next 30 days', 'Channel mix with commission cost'],
                'features_ar' => ['الإشغال وADR وRevPAR معًا', 'منحنى الحجوزات المستقبلية للأيام الـ 30 القادمة', 'مزيج القنوات مع تكلفة العمولات'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Marjan Hotel</h1><p class="sub">214 rooms · September 2026</p></div>'
                    . self::range('This month') . '</div>',
                    self::grid(
                        200,
                        self::tile('Occupancy', '78.4%', 'home', Kit::SERIES[0], [62, 66, 64, 70, 72, 74, 76, 78], '+6.2pt', true, 'vs last year'),
                        self::tile('Average daily rate', 'SAR 442', 'tag', Kit::SERIES[1], null, '+SAR 28', true, 'vs last year'),
                        self::tile('RevPAR', 'SAR 347', 'chart', Kit::SERIES[2], null, '+SAR 48', true, 'vs last year'),
                        self::tile('Length of stay', '2.8 nights', 'calendar', Kit::SERIES[3], null, '+0.3', true, 'vs last year')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Forward bookings', 'Occupancy already on the books', self::legend([['On the books', Kit::SERIES[0]], ['Same time last year', Kit::SERIES[1]]]))
                            . '<div class="pad">' . self::line(
                                [
                                    ['label' => 'On the books', 'values' => [88, 84, 76, 68, 62, 54, 48, 41, 34, 28]],
                                    ['label' => 'Last year', 'values' => [82, 78, 70, 61, 56, 49, 42, 36, 30, 24]],
                                ],
                                ['+3d', '+6d', '+9d', '+12d', '+15d', '+18d', '+21d', '+24d', '+27d', '+30d'],
                                ['w' => 480, 'h' => 210, 'unit' => '%', 'alt' => 'Forward booking curve']
                            ) . '</div>'
                            . '<div class="ft"><span>Pace is 6 points ahead</span><span>Next weekend sold out</span></div>'
                        ),
                        self::card(
                            self::head('Booking channels', 'Share and commission cost')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Direct website', 'no commission', '38%', 100, Kit::SERIES[2])
                            . self::rank('Booking.com', '15% commission', '31%', 82, Kit::SERIES[3])
                            . self::rank('Expedia', '18% commission', '14%', 37, Kit::SERIES[3])
                            . self::rank('Corporate contracts', 'negotiated rates', '12%', 32, Kit::SERIES[2])
                            . self::rank('Walk-in and phone', 'no commission', '5%', 13, Kit::SERIES[2])
                            . '</div>'
                            . '<div class="ft"><span>Commission paid SAR 98,400</span><span>Direct share up 4 points</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'hospital-ward-dashboard',
                'name' => 'Hospital ward dashboard',
                'name_ar' => 'لوحة حالة الأجنحة',
                'tagline' => 'Bed occupancy, admissions and staffing per ward.',
                'tagline_ar' => 'إشغال الأسرّة والإدخالات والطاقم لكل جناح.',
                'summary' => 'A bed-management board: occupancy per ward with free beds stated as a count rather than a percentage, because a ward at 94% means nothing until you know whether that is one bed or eleven.',
                'summary_ar' => 'لوحة لإدارة الأسرّة: الإشغال لكل جناح مع الأسرّة الشاغرة كعدد لا كنسبة، لأن جناحًا بنسبة 94% لا يعني شيئًا حتى تعرف أهي سرير واحد أم أحد عشر.',
                'accent' => '#0891b2',
                'tags' => ['healthcare', 'hospital', 'beds', 'operations'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Free beds as a count, not just a percentage', 'Ward rows with staffing ratio', 'Admissions and discharges for the day'],
                'features_ar' => ['الأسرّة الشاغرة كعدد لا كنسبة فقط', 'صفوف الأجنحة مع نسبة الطاقم', 'الإدخالات والخروج لليوم'],
                'height' => 720,
                'max' => 900,
                'css' => ".ward{display:grid;grid-template-columns:1fr auto auto;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)}\n.ward:last-child{border-bottom:0}\n.ward .beds{font-size:17px;font-weight:700;font-variant-numeric:tabular-nums}\n.ward .meter{grid-column:1 / -1}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Bed management</h1><p class="sub">20 September · 08:00 round</p></div>'
                    . Kit::pill('11 beds free', 'ok', true) . '</div>',
                    self::grid(
                        200,
                        self::tile('Occupancy', '92.4%', 'home', Kit::SERIES[0], [86, 88, 87, 90, 91, 92, 92, 92], '+2.1pt', false, 'vs last week'),
                        self::tile('Admissions today', '24', 'arrow-down', Kit::SERIES[1], null, '+6', false, 'vs average'),
                        self::tile('Discharges planned', '18', 'arrow-up', Kit::SERIES[2], null, '9 before noon', true, ''),
                        self::tile('Average stay', '4.2 days', 'clock', Kit::SERIES[3], null, '-0.4', true, 'vs last month')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Wards', 'Beds occupied of total')
                        . '<div class="pad">'
                        . self::wardRow('Ward A · General medicine', 38, 40, '1 nurse per 6 beds', 'warn')
                        . self::wardRow('Ward B · Surgical', 16, 18, '1 nurse per 5 beds', 'ok')
                        . self::wardRow('Ward C · Paediatrics', 12, 20, '1 nurse per 4 beds', 'ok')
                        . self::wardRow('ICU', 11, 12, '1 nurse per 1 bed', 'bad')
                        . self::wardRow('Maternity', 14, 16, '1 midwife per 4 beds', 'ok')
                        . '</div>'
                        . '<div class="ft"><span>91 of 106 beds occupied</span><span>ICU at capacity - divert protocol on standby</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'school-performance-dashboard',
                'name' => 'School performance dashboard',
                'name_ar' => 'لوحة أداء المدرسة',
                'tagline' => 'Attendance, grade distribution and subject averages.',
                'tagline_ar' => 'الحضور وتوزيع الدرجات ومتوسطات المواد.',
                'summary' => 'Grades shown as a distribution rather than an average, because an average of 74 hides whether that is everyone at 74 or half the class failing. Attendance sits beside it, since the two move together more often than not.',
                'summary_ar' => 'الدرجات معروضة كتوزيع لا كمتوسط، لأن متوسط 74 يخفي إن كان الجميع عند 74 أم نصف الصف راسبًا. ويظهر الحضور بجانبها، فهما يتحركان معًا في أغلب الأحيان.',
                'accent' => '#4f46e5',
                'tags' => ['education', 'school', 'grades', 'attendance'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Grade distribution instead of a class average', 'Attendance trend by week', 'Subject averages ranked'],
                'features_ar' => ['توزيع الدرجات بدل متوسط الصف', 'اتجاه الحضور أسبوعيًّا', 'متوسطات المواد مرتّبة'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Grade 10</h1><p class="sub">Term 2 · 284 students</p></div>'
                    . self::range('This term') . '</div>',
                    self::grid(
                        200,
                        self::tile('Attendance', '94.1%', 'users', Kit::SERIES[0], [92, 93, 92, 94, 95, 94, 94, 94], '+1.2pt', true, 'vs term 1'),
                        self::tile('Term average', '74.6', 'chart', Kit::SERIES[1], null, '+2.4', true, 'vs term 1'),
                        self::tile('Pass rate', '88.2%', 'check', Kit::SERIES[2], null, '+3.1pt', true, 'vs term 1'),
                        self::tile('At risk', '18 students', 'alert', Kit::SERIES[7], null, '-6', true, 'vs term 1')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Grade distribution', 'All subjects, this term')
                            . '<div class="pad">' . self::columns(
                                [['label' => 'Students', 'values' => [42, 88, 96, 41, 17]]],
                                ['A', 'B', 'C', 'D', 'F'],
                                ['w' => 460, 'h' => 220, 'alt' => 'Grade distribution']
                            ) . '</div>'
                            . '<div class="ft"><span>284 students</span><span>Median grade C</span></div>'
                        ),
                        self::card(
                            self::head('Subject averages', 'Out of 100')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Arabic', '284 students', '82.4', 82, Kit::SERIES[0])
                            . self::rank('Biology', '284 students', '78.1', 78, Kit::SERIES[0])
                            . self::rank('English', '284 students', '76.8', 77, Kit::SERIES[0])
                            . self::rank('Physics', '284 students', '71.2', 71, Kit::SERIES[0])
                            . self::rank('Mathematics', '284 students', '64.6', 65, Kit::SERIES[3])
                            . '</div>'
                            . '<div class="ft"><span>5 core subjects</span><span>Maths needs intervention</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'energy-usage-dashboard',
                'name' => 'Energy usage dashboard',
                'name_ar' => 'لوحة استهلاك الطاقة',
                'tagline' => 'Consumption, cost and generation against the grid.',
                'tagline_ar' => 'الاستهلاك والتكلفة والتوليد مقابل الشبكة.',
                'summary' => 'Consumption and solar generation on one chart and one scale, so self-sufficiency is the gap between the lines rather than a number to be taken on trust. Cost is shown per day, since that is what a bill is made of.',
                'summary_ar' => 'الاستهلاك والتوليد الشمسي على مخطط واحد ومقياس واحد، فيكون الاكتفاء الذاتي هو الفجوة بين الخطين لا رقمًا يُؤخذ على الثقة. وتُعرض التكلفة يوميًّا، لأن الفاتورة تتكوّن من ذلك.',
                'accent' => '#d97706',
                'tags' => ['energy', 'solar', 'consumption', 'utilities'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Consumption and generation on one scale', 'Self-sufficiency as the readable gap between lines', 'Cost per day with the tariff stated'],
                'features_ar' => ['الاستهلاك والتوليد على مقياس واحد', 'الاكتفاء الذاتي كفجوة مقروءة بين الخطين', 'التكلفة اليومية مع ذكر التعرفة'],
                'height' => 720,
                'max' => 880,
                'theme' => 'dark',
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Energy</h1><p class="sub">Main building · September 2026</p></div>'
                    . self::range('This month') . '</div>',
                    self::grid(
                        200,
                        self::tile('Consumption', '4,820 kWh', 'zap', Kit::SERIES_DARK[1], [42, 44, 41, 46, 48, 44, 42, 40], '-6.4%', true, 'vs August'),
                        self::tile('Solar generation', '3,140 kWh', 'sun', Kit::SERIES_DARK[3], [28, 30, 32, 34, 33, 36, 38, 40], '+12.1%', true, 'vs August'),
                        self::tile('Self-sufficiency', '65.1%', 'shield', Kit::SERIES_DARK[2], null, '+8.4pt', true, 'vs August'),
                        self::tile('Cost', 'SAR 1,284', 'wallet', Kit::SERIES_DARK[0], null, '-SAR 214', true, 'vs August')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Consumption against generation', 'kWh per day', self::legend([['Used', Kit::SERIES_DARK[1]], ['Generated', Kit::SERIES_DARK[3]]]))
                        . '<div class="pad">' . self::line(
                            [
                                ['label' => 'Used', 'values' => [168, 172, 164, 180, 188, 176, 162, 158, 166, 174, 170, 160], 'colour' => Kit::SERIES_DARK[1]],
                                ['label' => 'Generated', 'values' => [96, 104, 112, 108, 118, 124, 132, 128, 134, 126, 118, 122], 'colour' => Kit::SERIES_DARK[3]],
                            ],
                            ['1', '3', '5', '7', '9', '11', '13', '15', '17', '19', '21', '23'],
                            ['w' => 800, 'h' => 230, 'dark' => true, 'alt' => 'Daily consumption against solar generation']
                        ) . '</div>'
                        . '<div class="ft"><span>Tariff SAR 0.32 per kWh above 6,000</span><span>1,680 kWh drawn from the grid</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'crypto-portfolio-dashboard',
                'name' => 'Crypto portfolio dashboard',
                'name_ar' => 'لوحة محفظة العملات الرقمية',
                'tagline' => 'Holdings, allocation and unrealised profit.',
                'tagline_ar' => 'الأصول والتوزيع والربح غير المحقق.',
                'summary' => 'A portfolio view that separates what it is worth from what it cost: unrealised profit is its own column, allocation is a donut, and the value chart covers a long enough window that a single day cannot flatter it.',
                'summary_ar' => 'عرض محفظة يفصل بين قيمتها الحالية وتكلفتها: الربح غير المحقق في عمود خاص، والتوزيع في مخطط دائري مجوّف، ومخطط القيمة يغطي نافذة زمنية طويلة بما يكفي كي لا يجمّلها يوم واحد.',
                'accent' => '#f59e0b',
                'tags' => ['crypto', 'portfolio', 'investing', 'dark'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Unrealised profit separated from current value', 'Allocation donut with values listed beside it', 'Long enough window to avoid flattering the day'],
                'features_ar' => ['الربح غير المحقق منفصل عن القيمة الحالية', 'مخطط دائري مجوّف للتوزيع مع القيم بجانبه', 'نافذة زمنية طويلة بما يكفي لتجنّب تجميل اليوم'],
                'height' => 760,
                'max' => 980,
                'theme' => 'dark',
                'css' => ".hold{display:grid;grid-template-columns:auto 1fr repeat(2,minmax(84px,auto));gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid var(--bd)}\n.hold:last-child{border-bottom:0}\n.up{color:var(--ok);font-weight:700}\n.down{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Portfolio</h1><p class="sub">5 assets · last updated 12 seconds ago</p></div>'
                    . self::range('Last 90 days') . '</div>',
                    self::grid(
                        220,
                        self::tile('Portfolio value', '$184,210', 'wallet', Kit::SERIES_DARK[0], [120, 128, 124, 138, 146, 142, 158, 164, 160, 172, 178, 184], '+18.4%', true, 'vs 90 days ago'),
                        self::tile('Unrealised profit', '+$42,840', 'trend-up', Kit::SERIES_DARK[2], null, '+30.3%', true, 'on cost'),
                        self::tile('24h change', '+$2,140', 'clock', Kit::SERIES_DARK[3], null, '+1.18%', true, 'today')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Portfolio value', 'Last 90 days, in thousands')
                            . '<div class="pad">' . self::line(
                                [['label' => 'Value', 'values' => [128, 134, 130, 142, 138, 148, 156, 152, 164, 172, 168, 184]]],
                                ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8', 'W9', 'W10', 'W11', 'W12'],
                                ['w' => 480, 'h' => 220, 'unit' => '$', 'area' => true, 'dark' => true, 'alt' => 'Portfolio value over 90 days']
                            ) . '</div>'
                        ),
                        self::card(
                            self::head('Allocation', 'By current value')
                            . '<div class="pad" style="display:flex;justify-content:center">'
                            . Kit::donut([[52, Kit::SERIES_DARK[0]], [24, Kit::SERIES_DARK[1]], [12, Kit::SERIES_DARK[2]], [8, Kit::SERIES_DARK[3]], [4, Kit::SERIES_DARK[4]]], 150, '$184k', 'total')
                            . '</div>'
                            . '<div class="pad" style="padding-top:0">' . self::legend([['BTC 52%', Kit::SERIES_DARK[0]], ['ETH 24%', Kit::SERIES_DARK[1]], ['SOL 12%', Kit::SERIES_DARK[2]], ['USDT 8%', Kit::SERIES_DARK[3]], ['ADA 4%', Kit::SERIES_DARK[4]]]) . '</div>'
                        )
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Holdings', 'Value and unrealised profit')
                        . '<div class="pad">'
                        . self::holdingRow('BTC', 'Bitcoin', '1.492 BTC', '$95,789', '+$28,410', true, Kit::SERIES_DARK[0])
                        . self::holdingRow('ETH', 'Ethereum', '12.96 ETH', '$44,211', '+$9,840', true, Kit::SERIES_DARK[1])
                        . self::holdingRow('SOL', 'Solana', '149.3 SOL', '$22,105', '+$6,120', true, Kit::SERIES_DARK[2])
                        . self::holdingRow('USDT', 'Tether', '14,738 USDT', '$14,738', '+$0', true, Kit::SERIES_DARK[3])
                        . self::holdingRow('ADA', 'Cardano', '17,620 ADA', '$7,367', '-$1,530', false, Kit::SERIES_DARK[4])
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'warehouse-throughput-dashboard',
                'name' => 'Warehouse throughput dashboard',
                'name_ar' => 'لوحة إنتاجية المستودع',
                'tagline' => 'Picks per hour, accuracy and dock activity.',
                'tagline_ar' => 'عمليات الالتقاط في الساعة والدقة ونشاط الأرصفة.',
                'summary' => 'Throughput measured where it is decided: picks per hour by shift, accuracy as a percentage of lines, and dock turnaround. The shift comparison is the chart, because that is the lever a warehouse manager has.',
                'summary_ar' => 'إنتاجية تُقاس حيث تُحسم: عمليات الالتقاط في الساعة لكل وردية، والدقة كنسبة من البنود، وزمن دوران الأرصفة. مقارنة الورديات هي المخطط الرئيسي، لأنها الأداة التي يملكها مدير المستودع.',
                'accent' => '#0f766e',
                'tags' => ['warehouse', 'throughput', 'operations', 'shifts'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Throughput compared across shifts', 'Pick accuracy as a share of lines', 'Dock turnaround with the worst bay flagged'],
                'features_ar' => ['مقارنة الإنتاجية بين الورديات', 'دقة الالتقاط كنسبة من البنود', 'زمن دوران الأرصفة مع إبراز أسوأ رصيف'],
                'height' => 720,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Warehouse A</h1><p class="sub">Today · 3 shifts · 28 pickers</p></div>'
                    . self::range('Today') . '</div>',
                    self::grid(
                        200,
                        self::tile('Picks per hour', '184', 'box', Kit::SERIES[0], [160, 168, 172, 176, 180, 178, 184, 184], '+8.2%', true, 'vs last week'),
                        self::tile('Pick accuracy', '99.4%', 'check', Kit::SERIES[2], null, '+0.2pt', true, 'vs last week'),
                        self::tile('Orders shipped', '1,482', 'truck', Kit::SERIES[1], null, '+124', true, 'vs yesterday'),
                        self::tile('Dock turnaround', '42 min', 'clock', Kit::SERIES[3], [56, 52, 50, 48, 46, 44, 43, 42], '-9 min', true, 'vs last week')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Throughput by shift', 'Picks per hour', self::legend([['Today', Kit::SERIES[0]], ['Last week', Kit::SERIES[1]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Today', 'values' => [198, 184, 142]],
                                    ['label' => 'Last week', 'values' => [182, 176, 138]],
                                ],
                                ['06:00 - 14:00', '14:00 - 22:00', '22:00 - 06:00'],
                                ['w' => 460, 'h' => 220, 'alt' => 'Picks per hour by shift']
                            ) . '</div>'
                            . '<div class="ft"><span>Night shift runs 28% below day</span><span>Staffed at 6 rather than 11</span></div>'
                        ),
                        self::card(
                            self::head('Dock activity', 'Turnaround per bay today')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Bay 1 · inbound', '18 vehicles', '34 min', 100, Kit::SERIES[2])
                            . self::rank('Bay 2 · inbound', '14 vehicles', '38 min', 88, Kit::SERIES[2])
                            . self::rank('Bay 3 · outbound', '22 vehicles', '41 min', 82, Kit::SERIES[2])
                            . self::rank('Bay 4 · outbound', '19 vehicles', '68 min', 50, Kit::SERIES[7])
                            . '</div>'
                            . '<div class="ft"><span>73 vehicles today</span><span class="bold" style="color:var(--bad)">Bay 4 is the bottleneck</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'social-engagement-heatmap',
                'name' => 'Posting time heatmap',
                'name_ar' => 'خريطة أوقات النشر',
                'tagline' => 'Engagement by weekday and hour on one hue.',
                'tagline_ar' => 'التفاعل حسب يوم الأسبوع والساعة بتدرّج لون واحد.',
                'summary' => 'When to post, answered by the data rather than by folklore: day down, hour across, engagement as one hue from light to dark. The best three windows are named underneath so the chart ends in a decision.',
                'summary_ar' => 'متى تنشر؟ تجيب البيانات لا الموروث الشائع: الأيام عموديًّا، والساعات أفقيًّا، والتفاعل بلون واحد من الفاتح إلى الداكن. وتُذكر أفضل ثلاث فترات في الأسفل لينتهي المخطط بقرار.',
                'accent' => '#2563eb',
                'tags' => ['social', 'heatmap', 'timing', 'engagement'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Single-hue sequential encoding with values printed', 'Best posting windows named in the footer', 'Native tooltips on every cell'],
                'features_ar' => ['ترميز متسلسل بلون واحد مع طباعة القيم', 'أفضل فترات النشر مذكورة في التذييل', 'تلميحات أصلية على كل خلية'],
                'height' => 560,
                'max' => 820,
                'css' => ".scale{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--mut)}\n.scale i{width:20px;height:10px;display:block}",
                'body' => self::wrap(
                    self::card(
                        self::head('Engagement by hour', 'Average engagements per post, last 90 days', '<span class="scale">Low'
                            . implode('', array_map(fn($step) => '<i style="background:' . $step . '"></i>', array_slice(Kit::RAMP, 0, 9)))
                            . 'High</span>')
                        . '<div class="pad">' . self::heat(
                            [
                                ['label' => 'Monday', 'values' => [12, 18, 34, 48, 62, 41, 28]],
                                ['label' => 'Tuesday', 'values' => [14, 22, 41, 58, 74, 52, 31]],
                                ['label' => 'Wednesday', 'values' => [16, 26, 44, 66, 82, 58, 34]],
                                ['label' => 'Thursday', 'values' => [18, 24, 38, 54, 71, 64, 42]],
                                ['label' => 'Friday', 'values' => [10, 14, 22, 31, 38, 46, 52]],
                                ['label' => 'Saturday', 'values' => [8, 11, 18, 24, 29, 38, 44]],
                                ['label' => 'Sunday', 'values' => [11, 16, 28, 42, 54, 48, 36]],
                            ],
                            ['06', '09', '12', '15', '18', '21', '00'],
                            ['cell' => 42, 'labelW' => 84, 'alt' => 'Engagement by weekday and hour']
                        ) . '</div>'
                        . '<div class="ft"><span>Darker is more engagement</span><span class="bold">Best windows: Wed 18:00, Tue 18:00, Thu 21:00</span></div>'
                    )
                ),
            ],
        ];
    }

    /* ---------------------------------------------------------------- */

    private static function channelRow(string $icon, string $colour, string $name, string $spend, string $roas, string $cpa, bool $healthy): string
    {
        return '<div class="chan">' . Kit::iconTile($icon, $colour, 36)
            . '<span><span class="bold" style="display:block">' . $name . '</span>'
            . '<span class="xs mut">' . ($healthy ? 'Profitable' : 'Below target return') . '</span></span>'
            . '<span><span class="k">Spend</span><b class="num">' . $spend . '</b></span>'
            . '<span><span class="k">Return</span><b class="num">' . $roas . '</b></span>'
            . '<span><span class="k">Cost per acquisition</span>'
            . ($healthy ? '<b class="num">' . $cpa . '</b>' : '<b class="num" style="color:var(--bad)">' . $cpa . '</b>')
            . '</span></div>';
    }

    private static function countryRow(string $colour, string $name, string $meta, string $value, float $share, string $growth, bool $up): string
    {
        return '<div class="ctry"><span class="flagish" style="background:' . $colour . '"></span>'
            . '<span style="min-width:0"><span class="bold" style="display:block">' . $name . '</span>'
            . '<span class="xs mut">' . $meta . '</span>'
            . '<span style="display:block;margin-top:7px;max-width:260px">' . Kit::meter($share, $colour, 6) . '</span></span>'
            . '<span class="num bold">' . $value . '</span>'
            . '<span>' . Kit::delta($growth, $up) . '</span></div>';
    }

    private static function forecastDay(string $day, string $icon, int $high, int $low, int $rain): string
    {
        $rainNote = $rain === 0 ? '' : '<span class="xs" style="color:#38bdf8">' . $rain . '%</span>';

        return '<div class="day"><span class="mut">' . $day . '</span>'
            . '<span style="color:' . ($icon === 'sun' ? '#fbbf24' : '#94a3b8') . '">' . Kit::icon($icon, 18) . '</span>'
            . '<span class="range"></span>'
            . '<span class="num"><span class="mut">' . $low . '°</span> <b>' . $high . '°</b> ' . $rainNote . '</span></div>';
    }

    private static function projectCard(string $name, string $area, int $percent, string $colour, string $risk, string $tone, string $milestone, array $team): string
    {
        return self::card(
            '<div class="pad"><div class="proj">'
            . '<div class="top"><span><span class="bold" style="display:block;font-size:14.5px">' . $name . '</span>'
            . '<span class="xs mut">' . $area . '</span></span>' . Kit::pill($risk, $tone, true) . '</div>'
            . '<div><div class="row" style="justify-content:space-between;margin-bottom:6px">'
            . '<span class="xs mut">Progress</span><span class="xs bold num">' . $percent . '%</span></div>'
            . Kit::meter($percent, $colour, 7) . '</div>'
            . '<div class="mile">' . Kit::icon('flag', 14) . $milestone
            . '<span style="margin-left:auto">' . Kit::avatarStack($team, 24) . '</span></div>'
            . '</div></div>'
        );
    }

    private static function workloadRow(string $name, string $meta, int $hours, int $capacity, string $colour): string
    {
        $percent = round($hours / $capacity * 100);
        $over = $percent > 100;

        return '<div class="per"><div class="top"><span class="row" style="gap:10px">' . Kit::avatar($name, 32)
            . '<span><span class="bold" style="display:block">' . $name . '</span><span class="xs mut">' . $meta . '</span></span></span>'
            . '<span class="num ' . ($over ? 'over' : 'bold') . '">' . $hours . 'h of ' . $capacity . 'h</span></div>'
            . Kit::meter(min(100, $percent), $over ? '#e11d48' : $colour, 7) . '</div>';
    }

    private static function wardRow(string $name, int $used, int $total, string $staffing, string $tone): string
    {
        $free = $total - $used;
        $percent = round($used / $total * 100);

        return '<div class="ward"><span><span class="bold" style="display:block">' . $name . '</span>'
            . '<span class="xs mut">' . $staffing . '</span></span>'
            . '<span class="beds">' . $used . ' / ' . $total . '</span>'
            . Kit::pill($free === 0 ? 'Full' : $free . ' free', $tone, true)
            . '<span class="meter">' . Kit::meter($percent, $percent >= 95 ? '#e11d48' : ($percent >= 85 ? '#d97706' : Kit::SERIES[2]), 6) . '</span></div>';
    }

    private static function holdingRow(string $symbol, string $name, string $amount, string $value, string $profit, bool $up, string $colour): string
    {
        return '<div class="hold">' . Kit::iconTile('circle', $colour, 36)
            . '<span><span class="bold" style="display:block">' . $symbol . '<span class="mut" style="font-weight:500"> · ' . $name . '</span></span>'
            . '<span class="xs mut num">' . $amount . '</span></span>'
            . '<span class="num bold" style="text-align:right">' . $value . '</span>'
            . '<span class="num ' . ($up ? 'up' : 'down') . '" style="text-align:right">' . $profit . '</span></div>';
    }

    /** @return array<int,array<string,mixed>> */
    private static function setThree(): array
    {
        return [
            [
                'slug' => 'budget-actual-dashboard',
                'name' => 'Budget against actual dashboard',
                'name_ar' => 'لوحة الميزانية مقابل الفعلي',
                'tagline' => 'Departmental spend with variance and burn rate.',
                'tagline_ar' => 'إنفاق الأقسام مع الفارق ومعدل الصرف.',
                'summary' => 'The monthly finance review on one screen: spend against budget per department, the variance in currency rather than percent, and a burn line that shows whether the year ends over or under.',
                'summary_ar' => 'المراجعة المالية الشهرية على شاشة واحدة: الإنفاق مقابل الميزانية لكل قسم، والفارق بالعملة لا بالنسبة، وخط صرف يُظهر إن كانت السنة ستنتهي بتجاوز أم بفائض.',
                'accent' => '#1d4ed8',
                'tags' => ['finance', 'budget', 'variance', 'burn'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Variance shown in currency, not only percent', 'Cumulative burn against the straight-line budget', 'Departments ranked by overspend'],
                'features_ar' => ['الفارق معروض بالعملة لا بالنسبة فقط', 'الصرف التراكمي مقابل الميزانية الخطية', 'الأقسام مرتّبة حسب التجاوز'],
                'height' => 740,
                'max' => 980,
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Budget</h1><p class="sub">FY2026 · 9 months elapsed</p></div>'
                    . self::range('Year to date') . '</div>',
                    self::grid(
                        200,
                        self::tile('Budget', '$1.66M', 'wallet', Kit::SERIES[0], null, 'Full year', true, ''),
                        self::tile('Spent', '$1.27M', 'card', Kit::SERIES[1], null, '76.4% used', true, 'with 75% elapsed'),
                        self::tile('Remaining', '$393k', 'box', Kit::SERIES[2], null, '3 months left', true, ''),
                        self::tile('Projected variance', '-$42k', 'alert', Kit::SERIES[7], null, 'Over budget', false, 'at this burn rate')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Cumulative burn', 'Against a straight-line budget', self::legend([['Actual', Kit::SERIES[0]], ['Budget line', Kit::SERIES[1]]]))
                            . '<div class="pad">' . self::line(
                                [
                                    ['label' => 'Actual', 'values' => [148, 302, 448, 604, 742, 898, 1042, 1164, 1267]],
                                    ['label' => 'Budget line', 'values' => [138, 277, 415, 553, 692, 830, 968, 1107, 1245]],
                                ],
                                ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                                ['w' => 480, 'h' => 220, 'unit' => '$', 'alt' => 'Cumulative spend against budget line']
                            ) . '</div>'
                            . '<div class="ft"><span>In thousands</span><span>Ahead of the line since March</span></div>'
                        ),
                        self::card(
                            self::head('By department', 'Spent of budget')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Engineering', '$722k of $980k', '74%', 74, Kit::SERIES[2])
                            . self::rank('Marketing', '$329k of $420k', '78%', 78, Kit::SERIES[2])
                            . self::rank('Operations', '$214k of $260k', '82%', 82, Kit::SERIES[3])
                            . self::rank('Support', '$118k of $128k', '92%', 92, Kit::SERIES[7])
                            . '</div>'
                            . '<div class="ft"><span>4 departments</span><span class="bold" style="color:var(--bad)">Support runs out in November</span></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'invoice-aging-panel',
                'name' => 'Invoice ageing panel',
                'name_ar' => 'لوحة أعمار الفواتير',
                'tagline' => 'Receivables bucketed by how overdue they are.',
                'tagline_ar' => 'المستحقات مقسّمة حسب مدة تأخرها.',
                'summary' => 'Receivables told as a story of time: current, thirty, sixty and ninety-plus days, with the oldest bucket in status red. The worst offenders are named underneath, because chasing starts with a name and a number.',
                'summary_ar' => 'المستحقات كقصة زمنية: جارية، و30 و60 و90 يومًا فأكثر، والفئة الأقدم بأحمر الحالة. وتُذكر أسوأ الحسابات بالاسم في الأسفل، لأن المتابعة تبدأ باسم ورقم.',
                'accent' => '#b45309',
                'tags' => ['finance', 'receivables', 'ageing', 'invoices'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Four ageing buckets with values and counts', 'Oldest bucket in reserved status colour', 'Worst accounts named for chasing'],
                'features_ar' => ['أربع فئات للأعمار مع القيم والأعداد', 'الفئة الأقدم بلون الحالة المحجوز', 'أسوأ الحسابات مذكورة بالاسم للمتابعة'],
                'height' => 680,
                'max' => 880,
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Outstanding', '$412,800', 'file', Kit::SERIES[0], null, '+$48k', false, 'vs last month'),
                        self::tile('Overdue', '$128,400', 'alert', Kit::SERIES[7], null, '31% of the book', false, ''),
                        self::tile('Average days to pay', '38 days', 'clock', Kit::SERIES[1], [44, 43, 41, 42, 40, 39, 38, 38], '-4 days', true, 'vs last quarter'),
                        self::tile('Collected this month', '$284,100', 'check', Kit::SERIES[2], null, '+12.4%', true, 'vs last month')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Ageing buckets', 'Value outstanding by age')
                            . '<div class="pad">' . self::columns(
                                [['label' => 'Outstanding', 'values' => [284.4, 68.2, 38.1, 22.1]]],
                                ['Current', '1-30 days', '31-60 days', '60+ days'],
                                ['w' => 460, 'h' => 220, 'unit' => '$', 'alt' => 'Receivables by age bucket']
                            ) . '</div>'
                            . '<div class="ft"><span>In thousands</span><span>69% is not yet due</span></div>'
                        ),
                        self::card(
                            self::head('Worst accounts', 'Oldest overdue balance first')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Copper &amp; Co', 'INV-2104 · 94 days overdue', '$22,100', 100, Kit::SERIES[7])
                            . self::rank('Redwood Group', 'INV-2188 · 61 days overdue', '$18,400', 83, Kit::SERIES[7])
                            . self::rank('Juno Labs', 'INV-2201 · 44 days overdue', '$14,200', 64, Kit::SERIES[3])
                            . self::rank('Harbor Studio', 'INV-2240 · 22 days overdue', '$9,800', 44, Kit::SERIES[3])
                            . '</div>'
                            . '<div class="ft"><span>4 of 28 overdue invoices</span><button class="btn tiny" type="button">Send reminders</button></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'customer-value-card',
                'name' => 'Customer lifetime value card',
                'name_ar' => 'بطاقة قيمة العميل',
                'tagline' => 'Lifetime value against acquisition cost, with payback.',
                'tagline_ar' => 'القيمة الدائمة للعميل مقابل تكلفة الاكتساب، مع فترة الاسترداد.',
                'summary' => 'Three numbers that only mean anything together: what a customer is worth, what one costs to acquire, and how long the payback takes. The ratio is the headline, and the payback period is what decides how fast you can grow.',
                'summary_ar' => 'ثلاثة أرقام لا معنى لها إلا مجتمعة: قيمة العميل، وتكلفة اكتسابه، والمدة اللازمة لاسترداد التكلفة. النسبة هي العنوان الرئيسي، وفترة الاسترداد هي ما يحدد سرعة نموّك.',
                'accent' => '#059669',
                'tags' => ['ltv', 'cac', 'unit-economics', 'saas'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['LTV to CAC ratio as the headline figure', 'Payback period stated in months', 'Value by acquisition channel ranked'],
                'features_ar' => ['نسبة LTV إلى CAC كرقم رئيسي', 'فترة الاسترداد مذكورة بالأشهر', 'القيمة حسب قناة الاكتساب مرتّبة'],
                'height' => 620,
                'max' => 620,
                'css' => ".ratio{display:flex;align-items:baseline;gap:10px;justify-content:center;padding:26px 20px 6px}\n.ratio .big{font-size:56px;font-weight:750;letter-spacing:-.035em;line-height:1}\n.pair{display:grid;grid-template-columns:1fr 1fr;gap:2px;padding:16px 0}\n.pair div{padding:14px 20px;text-align:center}\n.pair div:first-child{border-right:1px solid var(--bd)}\n.pair .k{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700}\n.pair .v{font-size:22px;font-weight:700;margin-top:5px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Unit economics', 'Rolling 12 months', self::range('Last 12 months'))
                        . '<div class="ratio"><span class="num big">4.4x</span><span class="mut">LTV to CAC</span></div>'
                        . '<p class="sm mut" style="text-align:center;padding:0 24px">Healthy is anything above 3x. Below that, growth costs more than it returns.</p>'
                        . '<div class="pair"><div><div class="k">Lifetime value</div><div class="v num">$1,284</div>'
                        . '<div class="xs mut" style="margin-top:4px">over 26 months</div></div>'
                        . '<div><div class="k">Acquisition cost</div><div class="v num">$292</div>'
                        . '<div class="xs mut" style="margin-top:4px">blended across channels</div></div></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<div class="row" style="justify-content:space-between;margin-bottom:9px"><h3>Payback period</h3><span class="bold num">6.1 months</span></div>'
                        . Kit::meter(51, Kit::SERIES[2], 9)
                        . '<p class="hint">Of the 12-month target. Anything under 12 months funds its own growth.</p></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:13px">Value by channel</h3><div style="display:grid;gap:15px">'
                        . self::rank('Referral', 'CAC $88 · LTV $1,640', '18.6x', 100, Kit::SERIES[2])
                        . self::rank('Organic search', 'CAC $142 · LTV $1,310', '9.2x', 49, Kit::SERIES[2])
                        . self::rank('Paid search', 'CAC $312 · LTV $1,204', '3.9x', 21, Kit::SERIES[2])
                        . self::rank('Paid social', 'CAC $488 · LTV $1,090', '2.2x', 12, Kit::SERIES[7])
                        . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'nps-score-dashboard',
                'name' => 'NPS dashboard',
                'name_ar' => 'لوحة مؤشر الترشيح',
                'tagline' => 'Score, band split and the trend over four quarters.',
                'tagline_ar' => 'النتيجة وتوزيع الفئات والاتجاه عبر أربعة أرباع.',
                'summary' => 'An NPS panel that shows its working: the score, the three bands it comes from as a single stacked bar, and the quarterly trend. Without the band split, a score of 32 could be a calm crowd or two furious camps.',
                'summary_ar' => 'لوحة NPS تُظهر طريقة حسابها: النتيجة، والفئات الثلاث التي تنتج عنها في شريط مكدّس واحد، والاتجاه الربعي. فبدون توزيع الفئات، قد تعني نتيجة 32 جمهورًا هادئًا أو معسكرين غاضبين.',
                'accent' => '#059669',
                'tags' => ['nps', 'survey', 'satisfaction', 'research'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Band split shown as one stacked bar', 'Quarterly trend beneath the headline', 'Response count and rate stated'],
                'features_ar' => ['توزيع الفئات في شريط مكدّس واحد', 'الاتجاه الربعي أسفل الرقم الرئيسي', 'عدد الردود ومعدلها مذكوران'],
                'height' => 640,
                'max' => 620,
                'css' => ".score{text-align:center;padding:24px 20px 10px}\n.score .big{font-size:62px;font-weight:750;letter-spacing:-.04em;line-height:1;color:var(--ok)}\n.stack{display:flex;gap:2px;height:36px;border-radius:9px;overflow:hidden;margin:0 20px}\n.stack span{display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700}\n.bands{display:flex;justify-content:space-between;padding:10px 20px 0;font-size:11.5px;color:var(--mut)}",
                'body' => self::wrap(
                    self::card(
                        self::head('Net promoter score', 'Q3 2026 · 1,482 responses', self::range('This quarter'))
                        . '<div class="score"><div class="num big">+42</div>'
                        . '<div class="sm mut" style="margin-top:8px">Up 8 points on last quarter</div></div>'
                        . '<div class="stack">'
                        . '<span style="flex:58;background:' . Kit::SERIES[2] . '">58% promoters</span>'
                        . '<span style="flex:26;background:' . Kit::SERIES[3] . '">26%</span>'
                        . '<span style="flex:16;background:' . Kit::SERIES[7] . '">16%</span>'
                        . '</div>'
                        . '<div class="bands"><span>Promoters (9-10)</span><span>Passives (7-8)</span><span>Detractors (0-6)</span></div>'
                        . '<div class="pad">' . self::columns(
                            [['label' => 'NPS', 'values' => [21, 28, 34, 42]]],
                            ['Q4 2025', 'Q1 2026', 'Q2 2026', 'Q3 2026'],
                            ['w' => 480, 'h' => 190, 'alt' => 'NPS by quarter']
                        ) . '</div>'
                        . '<div class="ft"><span>Response rate 18.4%</span><span>Score = promoters minus detractors</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'uptime-status-page',
                'name' => 'Service status page',
                'name_ar' => 'صفحة حالة الخدمات',
                'tagline' => 'Per-service uptime bars for the last 90 days.',
                'tagline_ar' => 'أشرطة وقت التشغيل لكل خدمة خلال آخر 90 يومًا.',
                'summary' => 'The public status page pattern: one bar per day per service, so an incident is a visible red tick in a wall of green rather than a percentage that rounds it away. Each service states its own 90-day figure.',
                'summary_ar' => 'نمط صفحة الحالة العامة: شريط لكل يوم لكل خدمة، فتظهر الحادثة علامة حمراء واضحة وسط جدار من الأخضر بدل نسبة مئوية تذيبها بالتقريب. وتذكر كل خدمة رقمها الخاص لـ 90 يومًا.',
                'accent' => '#16a34a',
                'tags' => ['status', 'uptime', 'incidents', 'public'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['90 daily bars per service, incidents visible individually', 'Overall banner driven by the worst service', 'Incident history with duration'],
                'features_ar' => ['90 شريطًا يوميًّا لكل خدمة، والحوادث ظاهرة فرديًّا', 'لافتة عامة تحددها أسوأ خدمة', 'سجل الحوادث مع مدتها'],
                'height' => 740,
                'max' => 820,
                'css' => ".svc{padding:16px 0;border-bottom:1px solid var(--bd)}\n.svc:last-child{border-bottom:0}\n.svc .top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px}\n.strip{display:flex;gap:2px;height:28px}\n.strip i{flex:1;border-radius:2px;background:var(--ok);min-width:2px}\n.strip i.w{background:var(--warn)}\n.strip i.b{background:var(--bad)}\n.legend{display:flex;justify-content:space-between;font-size:11px;color:var(--mut);margin-top:6px}\n.banner{display:flex;align-items:center;gap:11px;padding:16px 20px;background:var(--ok-bg);color:var(--ok);font-weight:650;border-radius:14px;margin-bottom:14px}\n.inc{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid var(--bd);font-size:13px}\n.inc:last-child{border-bottom:0}",
                'body' => self::wrap(
                    '<div class="banner">' . Kit::icon('check', 20, 2.4) . 'All systems operational</div>',
                    self::card(
                        self::head('Service status', 'Last 90 days', self::btn('Subscribe', 'bell'))
                        . '<div class="pad">'
                        . self::serviceRow('API', '99.98%', [])
                        . self::serviceRow('Component gallery', '100%', [])
                        . self::serviceRow('Drawing editor', '99.94%', [34, 61])
                        . self::serviceRow('File conversion', '99.81%', [12, 47, 48, 72])
                        . self::serviceRow('CDN', '100%', [])
                        . '</div>'
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Recent incidents', 'Last 90 days')
                        . '<div class="pad">'
                        . '<div class="inc">' . Kit::pill('Resolved', 'ok') . '<span><b style="display:block">Conversion queue backlog</b>'
                        . '<span class="xs mut">A stuck worker delayed image conversions. Queue drained after a restart.</span></span>'
                        . '<span class="xs mut num" style="white-space:nowrap">18 Aug · 42m</span></div>'
                        . '<div class="inc">' . Kit::pill('Resolved', 'ok') . '<span><b style="display:block">Editor autosave failures</b>'
                        . '<span class="xs mut">A bad deploy broke autosave for 6% of sessions. Rolled back.</span></span>'
                        . '<span class="xs mut num" style="white-space:nowrap">21 Jul · 1h 08m</span></div>'
                        . '<div class="inc">' . Kit::pill('Resolved', 'ok') . '<span><b style="display:block">Elevated API latency</b>'
                        . '<span class="xs mut">A slow query on the components index. Fixed with an added index.</span></span>'
                        . '<span class="xs mut num" style="white-space:nowrap">02 Jul · 26m</span></div>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'api-usage-dashboard',
                'name' => 'API usage dashboard',
                'name_ar' => 'لوحة استخدام الواجهة البرمجية',
                'tagline' => 'Calls, error rate and quota per key.',
                'tagline_ar' => 'الاستدعاءات ومعدل الأخطاء والحصة لكل مفتاح.',
                'summary' => 'A developer-facing usage panel: calls against quota with the reset time stated, top endpoints by volume, and the error rate separated into client and server errors - which are two completely different conversations.',
                'summary_ar' => 'لوحة استخدام موجّهة للمطورين: الاستدعاءات مقابل الحصة مع ذكر وقت إعادة التعيين، وأكثر نقاط النهاية استخدامًا، ومعدل الأخطاء مقسّمًا إلى أخطاء العميل وأخطاء الخادم، وهما حديثان مختلفان تمامًا.',
                'accent' => '#0f766e',
                'tags' => ['api', 'developer', 'quota', 'usage'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Quota usage with the reset time stated', 'Client and server errors told apart', 'Top endpoints ranked by call volume'],
                'features_ar' => ['استهلاك الحصة مع ذكر وقت إعادة التعيين', 'التمييز بين أخطاء العميل وأخطاء الخادم', 'أكثر نقاط النهاية مرتّبة حسب عدد الاستدعاءات'],
                'height' => 740,
                'max' => 980,
                'css' => ".mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>API usage</h1><p class="sub">Production key · sk_live_••2f81</p></div>'
                    . self::range('Last 30 days') . '</div>',
                    self::grid(
                        200,
                        self::tile('Calls', '4.28M', 'server', Kit::SERIES[0], [30, 34, 32, 38, 42, 40, 46, 48], '+18.2%', true),
                        self::tile('Error rate', '0.42%', 'alert', Kit::SERIES[3], [8, 7, 9, 6, 8, 5, 6, 4], '-0.18pt', true),
                        self::tile('p95 latency', '184 ms', 'clock', Kit::SERIES[1], [48, 46, 44, 45, 42, 41, 40, 38], '-22 ms', true),
                        self::tile('Quota used', '86%', 'pie', Kit::SERIES[2], null, 'Resets in 9 days', true, '')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Calls per day', 'Last two weeks')
                            . '<div class="pad">' . self::line(
                                [['label' => 'Calls', 'values' => [112, 128, 134, 121, 148, 92, 68, 142, 156, 161, 148, 172, 104, 78]]],
                                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'],
                                ['w' => 480, 'h' => 210, 'area' => true, 'alt' => 'API calls per day']
                            ) . '</div>'
                            . '<div class="ft"><span>In thousands</span><span>Weekends run 45% lower</span></div>'
                        ),
                        self::card(
                            self::head('Top endpoints', 'By call volume')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('<span class="mono">GET /components</span>', '1.84M calls · 42 ms', '43%', 100, Kit::SERIES[0])
                            . self::rank('<span class="mono">GET /components/{slug}</span>', '1.12M calls · 38 ms', '26%', 61, Kit::SERIES[0])
                            . self::rank('<span class="mono">GET /drawing-templates</span>', '684k calls · 61 ms', '16%', 37, Kit::SERIES[0])
                            . self::rank('<span class="mono">POST /drawings</span>', '412k calls · 214 ms', '10%', 22, Kit::SERIES[0])
                            . self::rank('<span class="mono">POST /components/{slug}/download</span>', '224k calls · 48 ms', '5%', 12, Kit::SERIES[0])
                            . '</div>'
                            . '<div class="ft"><span>4xx: 14,204 · 5xx: 3,812</span><a href="#">Error log</a></div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'error-tracking-dashboard',
                'name' => 'Error tracking dashboard',
                'name_ar' => 'لوحة تتبع الأخطاء',
                'tagline' => 'Exception volume, affected users and the top issues.',
                'tagline_ar' => 'حجم الاستثناءات والمستخدمون المتأثرون وأبرز المشكلات.',
                'summary' => 'Errors ranked by people affected rather than by count, because one loop firing ten thousand times matters less than a checkout crash hitting two hundred customers. New regressions are marked so they do not hide in the tail.',
                'summary_ar' => 'الأخطاء مرتّبة حسب عدد المتأثرين لا حسب التكرار، لأن حلقة تتكرر عشرة آلاف مرة أقل أهمية من تعطّل في الدفع يصيب مئتي عميل. وتُميَّز التراجعات الجديدة كي لا تختفي في الذيل.',
                'accent' => '#e11d48',
                'tags' => ['errors', 'monitoring', 'exceptions', 'devops'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Issues ranked by users affected, not raw count', 'New regressions flagged against release', 'Error volume trend with the deploy marked'],
                'features_ar' => ['المشكلات مرتّبة حسب المستخدمين المتأثرين لا العدد الخام', 'إبراز التراجعات الجديدة مقابل الإصدار', 'اتجاه حجم الأخطاء مع تعليم وقت النشر'],
                'height' => 740,
                'max' => 980,
                'theme' => 'dark',
                'css' => ".iss{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)}\n.iss:last-child{border-bottom:0}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}\n.iss .n{font-size:16px;font-weight:700;font-variant-numeric:tabular-nums;text-align:right}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Errors</h1><p class="sub">Production · last 24 hours</p></div>'
                    . '<div class="row" style="gap:8px">' . Kit::pill('2 new since v4.12.0', 'bad', true) . self::range('Last 24 hours') . '</div></div>',
                    self::grid(
                        200,
                        self::tile('Events', '18,420', 'alert', Kit::SERIES_DARK[7], [40, 38, 42, 46, 44, 62, 58, 54], '+34.2%', false),
                        self::tile('Users affected', '1,284', 'users', Kit::SERIES_DARK[1], null, '+412', false, 'vs yesterday'),
                        self::tile('Crash-free sessions', '98.6%', 'shield', Kit::SERIES_DARK[2], null, '-0.8pt', false, 'vs yesterday'),
                        self::tile('New issues', '2', 'zap', Kit::SERIES_DARK[3], null, 'Since v4.12.0', false, '')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Top issues', 'Ranked by users affected')
                        . '<div class="pad">'
                        . self::issueRow('TypeError', 'Cannot read properties of null (reading \'getBBox\')', 'drower/NodeOverlay.jsx:214', '684', true)
                        . self::issueRow('NetworkError', 'Failed to fetch /api/components', 'Apihooks/useComponents.js:42', '412', false)
                        . self::issueRow('RangeError', 'Maximum call stack size exceeded', 'utils/pathOps.js:188', '128', true)
                        . self::issueRow('TypeError', 'template.document is undefined', 'drower/DrawBoard.jsx:1272', '44', false)
                        . self::issueRow('QuotaExceededError', 'localStorage is full', 'hooks/useAutosave.js:66', '16', false)
                        . '</div>'
                        . '<div class="ft"><span>5 of 42 open issues</span><span>Two marked new appeared with v4.12.0</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'ci-pipeline-dashboard',
                'name' => 'CI pipeline dashboard',
                'name_ar' => 'لوحة خط التكامل المستمر',
                'tagline' => 'Build outcomes, duration and the current run.',
                'tagline_ar' => 'نتائج البناء ومدته والتشغيل الحالي.',
                'summary' => 'A build dashboard answering the two questions anyone opens one with: is the pipeline green, and why is it so slow. Stage durations are shown so the slow stage is named rather than guessed at.',
                'summary_ar' => 'لوحة بناء تجيب عن السؤالين اللذين يفتحها أي شخص من أجلهما: هل خط التكامل أخضر؟ ولماذا هو بطيء إلى هذا الحد؟ تُعرض مدد المراحل فتُسمّى المرحلة البطيئة بدل التخمين.',
                'accent' => '#7c3aed',
                'tags' => ['ci', 'builds', 'devops', 'pipeline'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Stage timings so the slow stage is identified', 'Pass rate over the last fifty runs', 'Current run with a live stage marker'],
                'features_ar' => ['توقيت المراحل لتحديد المرحلة البطيئة', 'نسبة النجاح في آخر خمسين تشغيلًا', 'التشغيل الحالي مع مؤشر مباشر للمرحلة'],
                'height' => 720,
                'max' => 880,
                'theme' => 'dark',
                'css' => ".stages{display:grid;gap:2px}\n.stg{display:grid;grid-template-columns:22px 1fr auto;gap:11px;align-items:center;padding:10px 0;font-size:13px}\n.dotok{color:var(--ok)}\n.dotrun{color:var(--acc)}\n.dotwait{color:var(--faint)}\n.runs{display:flex;gap:3px;height:30px;align-items:flex-end}\n.runs i{flex:1;border-radius:2px;background:var(--ok)}\n.runs i.b{background:var(--bad)}",
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Pass rate', '92%', 'check', Kit::SERIES_DARK[2], null, '+4pt', true, 'last 50 runs'),
                        self::tile('Median build', '6m 42s', 'clock', Kit::SERIES_DARK[0], [52, 50, 48, 46, 44, 42, 41, 40], '-1m 12s', true, 'vs last week'),
                        self::tile('Runs today', '38', 'refresh', Kit::SERIES_DARK[1], null, '3 failed', false, ''),
                        self::tile('Queue wait', '22s', 'list', Kit::SERIES_DARK[3], null, '-8s', true, 'vs last week')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Current run · #2941', 'main · commit 9f2ac41 · Omar Saleh', Kit::pill('Running', 'info', true))
                        . '<div class="pad"><div class="stages">'
                        . self::stageRow('Checkout', 'done', '8s')
                        . self::stageRow('Install dependencies', 'done', '54s')
                        . self::stageRow('Lint', 'done', '22s')
                        . self::stageRow('Unit tests', 'done', '1m 48s')
                        . self::stageRow('Build', 'running', '2m 12s so far')
                        . self::stageRow('End-to-end tests', 'waiting', 'about 3m')
                        . self::stageRow('Deploy', 'waiting', 'about 40s')
                        . '</div></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<div class="row" style="justify-content:space-between;margin-bottom:9px"><h3>Last 50 runs</h3><span class="xs mut">4 failures</span></div>'
                        . '<div class="runs">' . implode('', array_map(function ($i) {
                            $height = 40 + (($i * 37) % 60);
                            $failed = in_array($i, [7, 19, 33, 44], true);

                            return '<i class="' . ($failed ? 'b' : '') . '" style="height:' . $height . '%"></i>';
                        }, range(1, 50))) . '</div>'
                        . '<div class="xs mut" style="display:flex;justify-content:space-between;margin-top:6px"><span>Bar height is duration</span><span>Red is a failed run</span></div></div>'
                    )
                ),
            ],
            [
                'slug' => 'release-health-dashboard',
                'name' => 'Release health dashboard',
                'name_ar' => 'لوحة صحة الإصدار',
                'tagline' => 'Adoption, crash-free rate and rollout progress.',
                'tagline_ar' => 'نسبة الاعتماد ومعدل الخلو من الأعطال وتقدّم الطرح.',
                'summary' => 'Everything that decides whether a rollout continues: how many users are on the new build, whether it crashes more than the last one, and how far the staged rollout has gone - with the comparison against the previous release always in view.',
                'summary_ar' => 'كل ما يحدد استمرار الطرح: كم مستخدمًا على النسخة الجديدة، وهل تتعطل أكثر من سابقتها، وإلى أين وصل الطرح المرحلي، مع المقارنة بالإصدار السابق حاضرة دائمًا.',
                'accent' => '#2563eb',
                'tags' => ['release', 'rollout', 'mobile', 'quality'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['New release compared against the previous one', 'Rollout percentage with the next step stated', 'Adoption curve since release'],
                'features_ar' => ['مقارنة الإصدار الجديد بالسابق', 'نسبة الطرح مع ذكر الخطوة التالية', 'منحنى الاعتماد منذ الإصدار'],
                'height' => 720,
                'max' => 880,
                'css' => ".vs{display:grid;grid-template-columns:1fr 1fr;gap:2px}\n.vs div{padding:16px 20px}\n.vs div:first-child{border-right:1px solid var(--bd)}\n.vs .k{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700}\n.vs .v{font-size:24px;font-weight:700;margin-top:6px}",
                'body' => self::wrap(
                    self::card(
                        self::head('v4.12.0', 'Released 3 days ago · staged rollout', Kit::pill('Healthy', 'ok', true))
                        . '<div class="vs">'
                        . '<div><div class="k">Crash-free sessions</div><div class="v num" style="color:var(--ok)">99.82%</div>'
                        . '<div class="xs mut" style="margin-top:4px">v4.11.3 was 99.74%</div></div>'
                        . '<div><div class="k">Crash-free users</div><div class="v num" style="color:var(--ok)">99.41%</div>'
                        . '<div class="xs mut" style="margin-top:4px">v4.11.3 was 99.22%</div></div>'
                        . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<div class="row" style="justify-content:space-between;margin-bottom:9px"><h3>Rollout</h3><span class="bold num">50% of users</span></div>'
                        . Kit::meter(50, Kit::SERIES[0], 9)
                        . '<p class="hint">Next step to 100% in 18 hours, unless the crash rate rises above 0.3%.</p></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<h3 style="margin-bottom:10px">Adoption since release</h3>'
                        . self::line(
                            [['label' => 'On v4.12.0', 'values' => [2, 8, 14, 21, 28, 34, 41, 46, 50]]],
                            ['0h', '8h', '16h', '24h', '32h', '40h', '48h', '56h', '64h'],
                            ['w' => 780, 'h' => 190, 'unit' => '%', 'area' => true, 'alt' => 'Adoption since release']
                        ) . '</div>'
                        . '<div class="ft"><span>182k sessions on the new build</span><span>4 new issues, none blocking</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'content-performance-dashboard',
                'name' => 'Content performance dashboard',
                'name_ar' => 'لوحة أداء المحتوى',
                'tagline' => 'Article views, read time and conversion per post.',
                'tagline_ar' => 'مشاهدات المقالات ووقت القراءة والتحويل لكل منشور.',
                'summary' => 'Content judged past the pageview: read-through rate says whether anyone finished it, and conversions say whether it did any work. An article with high views and low read-through is a headline that outran its body.',
                'summary_ar' => 'محتوى يُقيَّم بما يتجاوز مشاهدة الصفحة: معدل إكمال القراءة يخبرك إن أنهاه أحد، والتحويلات تخبرك إن أدّى أي عمل. المقال كثير المشاهدات قليل الإكمال عنوانه أقوى من متنه.',
                'accent' => '#db2777',
                'tags' => ['content', 'blog', 'engagement', 'marketing'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Read-through rate alongside views', 'Conversions attributed per article', 'Publishing cadence against traffic'],
                'features_ar' => ['معدل إكمال القراءة بجانب المشاهدات', 'التحويلات منسوبة لكل مقال', 'وتيرة النشر مقابل الزيارات'],
                'height' => 740,
                'max' => 900,
                'css' => ".art{display:grid;grid-template-columns:1fr repeat(3,minmax(70px,auto));gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd);font-size:13px}\n.art:last-child{border-bottom:0}\n.art .k{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);font-weight:700;display:block}\n@media (max-width:620px){.art{grid-template-columns:1fr auto}}",
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Views', '284,100', 'eye', Kit::SERIES[0], [30, 34, 32, 38, 42, 44, 48, 52], '+22.4%', true),
                        self::tile('Read-through', '48.2%', 'file', Kit::SERIES[1], [42, 44, 43, 46, 45, 47, 48, 48], '+2.1pt', true),
                        self::tile('Average read time', '4m 12s', 'clock', Kit::SERIES[2], null, '+38s', true),
                        self::tile('Conversions', '1,284', 'check', Kit::SERIES[3], null, '+18.9%', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Top articles', 'Last 30 days', self::range())
                        . '<div class="pad">'
                        . '<div class="art"><span class="k">Article</span><span class="k">Views</span><span class="k">Read-through</span><span class="k">Signups</span></div>'
                        . self::articleRow('Two hundred single-file components, free to copy', 'Published 12 Sep · 6 min read', '84,210', 61, '412')
                        . self::articleRow('How the drawing editor keeps templates editable', 'Published 02 Sep · 9 min read', '42,180', 71, '188')
                        . self::articleRow('Why we stopped using an icon font', 'Published 24 Aug · 4 min read', '38,940', 54, '96')
                        . self::articleRow('A table component that survives a phone', 'Published 18 Aug · 7 min read', '28,410', 44, '142')
                        . self::articleRow('Converting images without uploading them', 'Published 04 Aug · 5 min read', '22,180', 38, '64')
                        . '</div>'
                        . '<div class="ft"><span>5 of 84 articles</span><span>They are 76% of blog traffic</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'video-analytics-dashboard',
                'name' => 'Video analytics dashboard',
                'name_ar' => 'لوحة تحليلات الفيديو',
                'tagline' => 'Views, watch time and an audience retention curve.',
                'tagline_ar' => 'المشاهدات ووقت المشاهدة ومنحنى احتفاظ الجمهور.',
                'summary' => 'The retention curve is the whole panel: where viewers drop off, marked with what happens at that moment in the video. Average view duration without the curve tells you there is a problem but never where it is.',
                'summary_ar' => 'منحنى الاحتفاظ هو اللوحة كلها: أين يغادر المشاهدون، مع توضيح ما يحدث في الفيديو عند تلك اللحظة. متوسط مدة المشاهدة بلا المنحنى يخبرك بوجود مشكلة لكنه لا يحدد موضعها أبدًا.',
                'accent' => '#e11d48',
                'tags' => ['video', 'retention', 'youtube', 'media'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Audience retention curve with drop-off annotated', 'Watch time alongside raw views', 'Traffic sources for the video'],
                'features_ar' => ['منحنى احتفاظ الجمهور مع التعليق على نقاط المغادرة', 'وقت المشاهدة بجانب المشاهدات الخام', 'مصادر الزيارات للفيديو'],
                'height' => 740,
                'max' => 880,
                'theme' => 'dark',
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Drawing editor walkthrough</h1><p class="sub">Published 12 September · 14:22 long</p></div>'
                    . self::range('Last 30 days') . '</div>',
                    self::grid(
                        200,
                        self::tile('Views', '184,210', 'play', Kit::SERIES_DARK[0], [20, 48, 62, 54, 44, 38, 34, 30], '+142%', true, 'vs previous video'),
                        self::tile('Watch time', '9,840 hours', 'clock', Kit::SERIES_DARK[1], null, '+128%', true, 'vs previous video'),
                        self::tile('Average view', '3m 12s', 'eye', Kit::SERIES_DARK[2], null, '22% of length', false, ''),
                        self::tile('Subscribers gained', '1,284', 'user', Kit::SERIES_DARK[3], null, '+0.7%', true, 'of viewers')
                    ),
                    '<div style="height:14px"></div>',
                    self::card(
                        self::head('Audience retention', 'Share still watching at each point')
                        . '<div class="pad">' . self::line(
                            [['label' => 'Watching', 'values' => [100, 82, 68, 61, 54, 48, 44, 38, 34, 31, 28, 24]]],
                            ['0:00', '1:20', '2:40', '4:00', '5:20', '6:40', '8:00', '9:20', '10:40', '12:00', '13:20', '14:22'],
                            ['w' => 800, 'h' => 220, 'unit' => '%', 'area' => true, 'dark' => true, 'alt' => 'Audience retention curve']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<h3 style="margin-bottom:11px">Where people leave</h3>'
                        . '<div style="display:grid;gap:11px;font-size:13px">'
                        . '<div class="row" style="gap:10px">' . Kit::pill('0:00 - 1:20', 'bad') . '<span>18% leave during the intro - it runs 40 seconds before anything is drawn</span></div>'
                        . '<div class="row" style="gap:10px">' . Kit::pill('4:00 - 5:20', 'warn') . '<span>A 90-second aside about file formats</span></div>'
                        . '<div class="row" style="gap:10px">' . Kit::pill('9:20', 'warn') . '<span>The advanced clipping section starts</span></div>'
                        . '</div></div>'
                        . '<div class="ft"><span>24% finish the video</span><span>Category average is 18%</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'podcast-stats-card',
                'name' => 'Podcast statistics card',
                'name_ar' => 'بطاقة إحصاءات البودكاست',
                'tagline' => 'Downloads per episode with platform split.',
                'tagline_ar' => 'التنزيلات لكل حلقة مع توزيعها حسب المنصة.',
                'summary' => 'Podcast reporting is downloads per episode over a fixed window, because a new episode always looks worse until it has had the same number of days. This card states the window rather than quietly comparing unequal things.',
                'summary_ar' => 'تُقاس تقارير البودكاست بالتنزيلات لكل حلقة خلال نافذة زمنية ثابتة، لأن الحلقة الجديدة تبدو دائمًا أسوأ حتى تمضي عليها الأيام نفسها. تذكر هذه البطاقة النافذة صراحةً بدل أن تقارن أمورًا غير متكافئة بصمت.',
                'accent' => '#7c3aed',
                'tags' => ['podcast', 'audio', 'downloads', 'media'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Downloads measured over an equal window per episode', 'Platform split with values listed', 'Completion rate per episode'],
                'features_ar' => ['التنزيلات مقاسة على نافذة متساوية لكل حلقة', 'التوزيع حسب المنصة مع إدراج القيم', 'معدل الإكمال لكل حلقة'],
                'height' => 700,
                'max' => 720,
                'css' => ".ep{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid var(--bd)}\n.ep:last-child{border-bottom:0}\n.epn{width:34px;height:34px;border-radius:10px;background:var(--acc-soft);color:var(--acc);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px}",
                'body' => self::wrap(
                    self::grid(
                        220,
                        self::tile('Downloads', '184,210', 'download', Kit::SERIES[0], [28, 32, 30, 36, 38, 42, 44, 48], '+18.2%', true),
                        self::tile('Average per episode', '12,280', 'play', Kit::SERIES[1], null, 'first 30 days', true, ''),
                        self::tile('Completion rate', '68.4%', 'check', Kit::SERIES[2], null, '+3.1pt', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        320,
                        self::card(
                            self::head('Recent episodes', 'Downloads in the first 30 days')
                            . '<div class="pad">'
                            . self::episodeRow(42, 'Designing for the ninety-ninth percentile', '14,820', '71%')
                            . self::episodeRow(41, 'What a component library owes you', '13,140', '68%')
                            . self::episodeRow(40, 'Vector editing in a browser', '12,480', '74%')
                            . self::episodeRow(39, 'Arabic typography on the web', '11,920', '66%')
                            . self::episodeRow(38, 'The cost of an icon font', '9,840', '61%')
                            . '</div>'
                        ),
                        self::card(
                            self::head('Listening platforms', 'Share of downloads')
                            . '<div class="pad" style="display:flex;justify-content:center">'
                            . Kit::donut([[44, Kit::SERIES[0]], [28, Kit::SERIES[1]], [18, Kit::SERIES[2]], [10, Kit::SERIES[3]]], 148, '184k', 'downloads')
                            . '</div>'
                            . '<div class="pad" style="padding-top:0">' . self::legend([['Apple Podcasts 44%', Kit::SERIES[0]], ['Spotify 28%', Kit::SERIES[1]], ['Web player 18%', Kit::SERIES[2]], ['Other apps 10%', Kit::SERIES[3]]]) . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'newsletter-metrics-dashboard',
                'name' => 'Newsletter metrics dashboard',
                'name_ar' => 'لوحة مؤشرات النشرة البريدية',
                'tagline' => 'List growth, open rate and the unsubscribe cost.',
                'tagline_ar' => 'نمو القائمة ومعدل الفتح وتكلفة إلغاء الاشتراك.',
                'summary' => 'Growth shown net rather than gross: subscribers gained against those lost, on one chart, so a list that looks like it is growing while churning through readers is visible for what it is.',
                'summary_ar' => 'النمو معروض صافيًا لا إجماليًّا: المشتركون المكتسبون مقابل المفقودين على مخطط واحد، فتنكشف حقيقة القائمة التي تبدو في نمو بينما تستنزف قرّاءها.',
                'accent' => '#db2777',
                'tags' => ['newsletter', 'email', 'growth', 'marketing'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Gained against lost on one shared scale', 'Open and click rates per issue', 'Net growth stated in the footer'],
                'features_ar' => ['المكتسبون مقابل المفقودين على مقياس مشترك واحد', 'معدلات الفتح والنقر لكل عدد', 'صافي النمو مذكور في التذييل'],
                'height' => 740,
                'max' => 900,
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Subscribers', '48,210', 'users', Kit::SERIES[0], [38, 40, 41, 43, 44, 45, 47, 48], '+4.2%', true),
                        self::tile('Open rate', '42.1%', 'mail', Kit::SERIES[1], [38, 39, 41, 40, 42, 41, 42, 42], '+1.4pt', true),
                        self::tile('Click rate', '8.4%', 'zap', Kit::SERIES[2], [7, 7.2, 7.8, 7.6, 8.1, 8, 8.2, 8.4], '+0.6pt', true),
                        self::tile('Unsubscribe rate', '0.28%', 'x', Kit::SERIES[3], null, '-0.04pt', true)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('List growth', 'Per issue', self::legend([['Gained', Kit::SERIES[2]], ['Lost', Kit::SERIES[7]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Gained', 'values' => [820, 940, 1120, 880, 1340, 1180], 'colour' => Kit::SERIES[2]],
                                    ['label' => 'Lost', 'values' => [140, 168, 122, 196, 134, 128], 'colour' => Kit::SERIES[7]],
                                ],
                                ['#38', '#39', '#40', '#41', '#42', '#43'],
                                ['w' => 480, 'h' => 220, 'alt' => 'Subscribers gained and lost per issue']
                            ) . '</div>'
                            . '<div class="ft"><span>6 issues</span><span class="bold" style="color:var(--ok)">Net +5,392</span></div>'
                        ),
                        self::card(
                            self::head('Where subscribers come from', 'Last 90 days')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Blog footer form', '4,820 signups', '42%', 100, Kit::SERIES[0])
                            . self::rank('Component gallery banner', '2,940 signups', '26%', 61, Kit::SERIES[0])
                            . self::rank('Editor onboarding', '1,880 signups', '16%', 39, Kit::SERIES[0])
                            . self::rank('Referral links', '1,140 signups', '10%', 24, Kit::SERIES[0])
                            . self::rank('Direct and other', '680 signups', '6%', 14, Kit::SERIES[0])
                            . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'referral-program-dashboard',
                'name' => 'Referral programme dashboard',
                'name_ar' => 'لوحة برنامج الإحالة',
                'tagline' => 'Invites, conversions and reward liability.',
                'tagline_ar' => 'الدعوات والتحويلات والتزامات المكافآت.',
                'summary' => 'A referral panel that tracks the money as well as the growth: rewards owed are a liability, and showing them beside the conversions keeps a programme from being celebrated and expensive at the same time.',
                'summary_ar' => 'لوحة إحالة تتتبع المال إلى جانب النمو: المكافآت المستحقة التزام مالي، وعرضها بجانب التحويلات يمنع أن يُحتفى ببرنامج وهو مكلف في الوقت نفسه.',
                'accent' => '#059669',
                'tags' => ['referral', 'growth', 'rewards', 'programme'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Reward liability tracked beside conversions', 'Funnel from invite sent to paid customer', 'Top referrers with their earnings'],
                'features_ar' => ['التزامات المكافآت متتبَّعة بجانب التحويلات', 'قمع من إرسال الدعوة حتى العميل المدفوع', 'أبرز المُحيلين مع أرباحهم'],
                'height' => 740,
                'max' => 900,
                'css' => ".funnel{display:grid;gap:10px}\n.fstage{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;font-size:13px}",
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Invites sent', '12,480', 'send', Kit::SERIES[0], [30, 34, 38, 36, 42, 46, 48, 52], '+18.4%', true),
                        self::tile('New customers', '842', 'user', Kit::SERIES[2], null, '6.7% of invites', true, ''),
                        self::tile('Rewards owed', '$16,840', 'wallet', Kit::SERIES[3], null, '$20 per referral', false, ''),
                        self::tile('Cost per acquisition', '$20.00', 'tag', Kit::SERIES[1], null, 'vs $28 blended', true, '')
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Referral funnel', 'Last 90 days')
                            . '<div class="pad"><div class="funnel">'
                            . '<div class="fstage"><span>Invites sent</span><b class="num">12,480</b></div>' . Kit::meter(100, Kit::SERIES[0])
                            . '<div class="fstage"><span>Invites opened</span><b class="num">7,410</b></div>' . Kit::meter(59, Kit::SERIES[0])
                            . '<div class="fstage"><span>Accounts created</span><b class="num">2,104</b></div>' . Kit::meter(17, Kit::SERIES[0])
                            . '<div class="fstage"><span>Became paying customers</span><b class="num">842</b></div>' . Kit::meter(7, Kit::SERIES[2])
                            . '</div></div>'
                            . '<div class="ft"><span>6.7% end-to-end</span><span>Best channel by a distance</span></div>'
                        ),
                        self::card(
                            self::head('Top referrers', 'By paying customers sent')
                            . '<div class="pad" style="display:grid;gap:15px">'
                            . self::rank('Maya Rahman', '42 customers · $840 earned', '42', 100, Kit::SERIES[2], Kit::avatar('Maya Rahman', 30))
                            . self::rank('Omar Saleh', '31 customers · $620 earned', '31', 74, Kit::SERIES[2], Kit::avatar('Omar Saleh', 30))
                            . self::rank('Harbor Studio', '24 customers · $480 earned', '24', 57, Kit::SERIES[2], Kit::avatar('Harbor Studio', 30))
                            . self::rank('Lina Haddad', '18 customers · $360 earned', '18', 43, Kit::SERIES[2], Kit::avatar('Lina Haddad', 30))
                            . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'loyalty-points-dashboard',
                'name' => 'Loyalty programme dashboard',
                'name_ar' => 'لوحة برنامج الولاء',
                'tagline' => 'Members by tier, points issued and redemption rate.',
                'tagline_ar' => 'الأعضاء حسب المستوى والنقاط الممنوحة ومعدل الاستبدال.',
                'summary' => 'Loyalty measured on redemption rather than enrolment: unredeemed points are a debt and an unused programme. Tier distribution shows whether anyone is actually climbing or everyone is stuck at bronze.',
                'summary_ar' => 'ولاء يُقاس بالاستبدال لا بالتسجيل: النقاط غير المستبدلة دَين وبرنامج غير مستخدم. ويُظهر توزيع المستويات إن كان أحد يترقى فعلًا أم أن الجميع عالقون في المستوى البرونزي.',
                'accent' => '#d97706',
                'tags' => ['loyalty', 'rewards', 'tiers', 'retail'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Points issued against points redeemed', 'Tier distribution with movement', 'Outstanding liability in currency'],
                'features_ar' => ['النقاط الممنوحة مقابل النقاط المستبدلة', 'توزيع المستويات مع حركتها', 'الالتزامات القائمة بالعملة'],
                'height' => 720,
                'max' => 900,
                'css' => ".tier{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)}\n.tier:last-child{border-bottom:0}\n.badge{width:38px;height:38px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px}",
                'body' => self::wrap(
                    self::grid(
                        200,
                        self::tile('Members', '84,210', 'users', Kit::SERIES[0], [60, 64, 66, 70, 74, 78, 81, 84], '+8.4%', true),
                        self::tile('Points issued', '12.4M', 'star', Kit::SERIES[3], null, 'this quarter', true, ''),
                        self::tile('Redemption rate', '38.2%', 'refresh', Kit::SERIES[2], [30, 31, 33, 34, 35, 36, 37, 38], '+4.1pt', true),
                        self::tile('Outstanding liability', '$184,200', 'wallet', Kit::SERIES[7], null, '+$12k', false)
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Members by tier', 'And who moved up this quarter')
                            . '<div class="pad">'
                            . self::tierRow('#b45309', 'Bronze', '0 - 999 points', '52,140', '62%')
                            . self::tierRow('#94a3b8', 'Silver', '1,000 - 4,999', '21,840', '26%')
                            . self::tierRow('#eab308', 'Gold', '5,000 - 19,999', '8,420', '10%')
                            . self::tierRow('#0f766e', 'Platinum', '20,000+', '1,810', '2%')
                            . '</div>'
                            . '<div class="ft"><span>2,410 members moved up a tier</span><span>184 moved down</span></div>'
                        ),
                        self::card(
                            self::head('Issued against redeemed', 'Points in millions', self::legend([['Issued', Kit::SERIES[3]], ['Redeemed', Kit::SERIES[2]]]))
                            . '<div class="pad">' . self::columns(
                                [
                                    ['label' => 'Issued', 'values' => [2.8, 3.1, 2.9, 3.6], 'colour' => Kit::SERIES[3]],
                                    ['label' => 'Redeemed', 'values' => [0.9, 1.1, 1.2, 1.5], 'colour' => Kit::SERIES[2]],
                                ],
                                ['Q4', 'Q1', 'Q2', 'Q3'],
                                ['w' => 460, 'h' => 210, 'alt' => 'Points issued against redeemed by quarter']
                            ) . '</div>'
                        )
                    )
                ),
            ],
            [
                'slug' => 'donation-campaign-dashboard',
                'name' => 'Donation campaign dashboard',
                'name_ar' => 'لوحة حملة التبرعات',
                'tagline' => 'Raised against target with donor mix and recent gifts.',
                'tagline_ar' => 'المبلغ المجموع مقابل الهدف مع مزيج المتبرعين وأحدث التبرعات.',
                'summary' => 'A campaign page built around one bar: raised against target, with days left beside it. Recurring gifts are separated from one-offs because they are what the next campaign starts from.',
                'summary_ar' => 'صفحة حملة مبنية حول شريط واحد: المجموع مقابل الهدف، والأيام المتبقية بجانبه. وتُفصل التبرعات المتكررة عن تبرعات المرة الواحدة، لأنها ما تنطلق منه الحملة التالية.',
                'accent' => '#be123c',
                'tags' => ['nonprofit', 'donations', 'campaign', 'fundraising'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Raised against target with days remaining', 'Recurring gifts separated from one-offs', 'Live donor feed with anonymity respected'],
                'features_ar' => ['المجموع مقابل الهدف مع الأيام المتبقية', 'التبرعات المتكررة منفصلة عن تبرعات المرة الواحدة', 'سجل مباشر للمتبرعين مع احترام إخفاء الهوية'],
                'height' => 740,
                'max' => 820,
                'css' => ".raise{padding:22px 20px}\n.raise .amt{font-size:42px;font-weight:750;letter-spacing:-.03em;line-height:1}\n.raise .of{font-size:13px;color:var(--mut);margin-top:6px}\n.feed{display:grid;gap:14px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Winter appeal', 'Closes 31 October · 41 days left', Kit::pill('Live', 'ok', true))
                        . '<div class="raise"><div class="num amt">$248,120</div>'
                        . '<div class="of">raised of a <b>$400,000</b> target · 62%</div>'
                        . '<div style="margin-top:14px">' . Kit::meter(62, Kit::SERIES[2], 12) . '</div></div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . self::grid(
                            160,
                            self::card('<div class="pad"><div class="xs mut" style="text-transform:uppercase;letter-spacing:.06em;font-weight:700">Donors</div><div class="num" style="font-size:24px;font-weight:700;margin-top:5px">4,182</div></div>'),
                            self::card('<div class="pad"><div class="xs mut" style="text-transform:uppercase;letter-spacing:.06em;font-weight:700">Average gift</div><div class="num" style="font-size:24px;font-weight:700;margin-top:5px">$59</div></div>'),
                            self::card('<div class="pad"><div class="xs mut" style="text-transform:uppercase;letter-spacing:.06em;font-weight:700">Recurring</div><div class="num" style="font-size:24px;font-weight:700;margin-top:5px">$2,840<span class="xs mut" style="font-weight:500">/mo</span></div></div>')
                        )
                        . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)">'
                        . '<h3 style="margin-bottom:10px">Raised per week</h3>'
                        . self::columns(
                            [['label' => 'Raised', 'values' => [18.2, 24.1, 31.4, 28.8, 42.1, 38.4, 34.2, 30.9], 'colour' => Kit::SERIES[7]]],
                            ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
                            ['w' => 740, 'h' => 190, 'unit' => '$', 'alt' => 'Amount raised per week']
                        ) . '</div>'
                        . '<div class="pad" style="border-top:1px solid var(--bd)"><h3 style="margin-bottom:13px">Recent gifts</h3><div class="feed">'
                        . self::event('heart', Kit::SERIES[7], '<b>Copper &amp; Co</b> gave <b>$12,000</b> to the winter appeal', '18 minutes ago')
                        . self::event('heart', Kit::SERIES[7], '<b>Anonymous</b> gave <b>$250</b>', '42 minutes ago')
                        . self::event('refresh', Kit::SERIES[2], '<b>Rana K.</b> started a <b>$45 monthly</b> gift', '2 hours ago')
                        . self::event('heart', Kit::SERIES[7], '<b>Bluebird Media</b> matched <b>$3,400</b> in staff giving', '5 hours ago')
                        . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'ticket-sales-dashboard',
                'name' => 'Event ticket sales dashboard',
                'name_ar' => 'لوحة مبيعات تذاكر الفعالية',
                'tagline' => 'Sales pace against capacity with ticket mix.',
                'tagline_ar' => 'وتيرة المبيعات مقابل السعة مع مزيج التذاكر.',
                'summary' => 'Event sales are a race against a date, so the pace line against the same point in the last event is the chart that matters. Capacity is stated as seats left rather than a percentage sold.',
                'summary_ar' => 'مبيعات الفعاليات سباق مع تاريخ، لذا فالمخطط المهم هو خط الوتيرة مقارنةً بالنقطة نفسها في الفعالية السابقة. وتُذكر السعة كمقاعد متبقية لا كنسبة مبيعة.',
                'accent' => '#7c3aed',
                'tags' => ['events', 'tickets', 'sales', 'pace'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Sales pace against the previous event', 'Seats remaining rather than percent sold', 'Ticket mix with revenue per type'],
                'features_ar' => ['وتيرة المبيعات مقابل الفعالية السابقة', 'المقاعد المتبقية بدل النسبة المبيعة', 'مزيج التذاكر مع الإيراد لكل نوع'],
                'height' => 740,
                'max' => 900,
                'css' => ".cap{display:flex;align-items:center;gap:16px;padding:18px 20px;flex-wrap:wrap}\n.cap .n{font-size:34px;font-weight:750;letter-spacing:-.03em;line-height:1}\n.tix{display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid var(--bd)}\n.tix:last-child{border-bottom:0}",
                'body' => self::wrap(
                    '<div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">'
                    . '<div><h1>Frugal Conf 2026</h1><p class="sub">14 November · Riyadh · 400 seats</p></div>'
                    . Kit::pill('On sale', 'ok', true) . '</div>',
                    self::card(
                        '<div class="cap"><span><span class="num n">284</span><div class="sm mut" style="margin-top:5px">tickets sold</div></span>'
                        . '<span style="flex:1;min-width:200px">' . Kit::meter(71, Kit::SERIES[0], 12)
                        . '<div class="xs mut" style="margin-top:7px">116 seats left · 55 days to go</div></span>'
                        . '<span style="text-align:right"><span class="num" style="font-size:22px;font-weight:700">$58,420</span>'
                        . '<div class="xs mut" style="margin-top:4px">revenue</div></span></div>'
                    ),
                    '<div style="height:14px"></div>',
                    self::grid(
                        340,
                        self::card(
                            self::head('Sales pace', 'Cumulative, against last year at the same point', self::legend([['This year', Kit::SERIES[0]], ['Last year', Kit::SERIES[1]]]))
                            . '<div class="pad">' . self::line(
                                [
                                    ['label' => 'This year', 'values' => [18, 48, 82, 124, 168, 204, 241, 284]],
                                    ['label' => 'Last year', 'values' => [12, 34, 61, 96, 132, 171, 208, 244]],
                                ],
                                ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
                                ['w' => 480, 'h' => 220, 'alt' => 'Cumulative ticket sales against last year']
                            ) . '</div>'
                            . '<div class="ft"><span>16% ahead of last year</span><span>Sold out last year on day 71</span></div>'
                        ),
                        self::card(
                            self::head('Ticket mix', 'Sold and revenue per type')
                            . '<div class="pad">'
                            . self::ticketRow('Standard', '$180', '184 sold', '$33,120', 100, Kit::SERIES[0])
                            . self::ticketRow('Front row', '$340', '62 sold', '$21,080', 34, Kit::SERIES[1])
                            . self::ticketRow('Student', '$45', '38 sold', '$1,710', 21, Kit::SERIES[2])
                            . self::ticketRow('Sponsor comp', '$0', '24 issued', '$0', 13, Kit::SERIES[3])
                            . '</div>'
                            . '<div class="ft"><span>Average $206 per paid ticket</span><span>Front row is 36% of revenue</span></div>'
                        )
                    )
                ),
            ],
        ];
    }

    /* ---------------------------------------------------------------- */

    private static function serviceRow(string $name, string $uptime, array $bad): string
    {
        $bars = '';
        foreach (range(1, 90) as $day) {
            $class = in_array($day, $bad, true) ? ' class="b"' : '';
            $bars .= '<i' . $class . '></i>';
        }

        return '<div class="svc"><div class="top"><b>' . $name . '</b>'
            . '<span class="num sm mut">' . $uptime . ' uptime</span></div>'
            . '<div class="strip">' . $bars . '</div>'
            . '<div class="legend"><span>90 days ago</span><span>today</span></div></div>';
    }

    private static function issueRow(string $type, string $message, string $where, string $users, bool $isNew): string
    {
        return '<div class="iss">' . Kit::iconTile('alert', $isNew ? Kit::SERIES_DARK[7] : Kit::SERIES_DARK[1], 36)
            . '<span style="min-width:0"><span class="bold" style="display:block">' . $type
            . ($isNew ? ' ' . Kit::pill('New', 'bad') : '') . '</span>'
            . '<span class="xs mut" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . $message . '</span>'
            . '<span class="mono mut">' . $where . '</span></span>'
            . '<span class="n">' . $users . '<span class="xs mut" style="display:block;font-weight:500">users</span></span>'
            . '<button class="btn tiny" type="button">Assign</button></div>';
    }

    private static function stageRow(string $name, string $state, string $time): string
    {
        $mark = match ($state) {
            'done' => '<span class="dotok">' . Kit::icon('check', 17, 2.4) . '</span>',
            'running' => '<span class="dotrun">' . Kit::icon('refresh', 17, 2.4) . '</span>',
            default => '<span class="dotwait">' . Kit::icon('circle', 17, 1.6) . '</span>',
        };

        return '<div class="stg">' . $mark . '<span class="' . ($state === 'waiting' ? 'mut' : 'bold') . '">' . $name . '</span>'
            . '<span class="xs mut num">' . $time . '</span></div>';
    }

    private static function articleRow(string $title, string $meta, string $views, int $read, string $signups): string
    {
        return '<div class="art"><span style="min-width:0"><span class="bold" style="display:block">' . $title . '</span>'
            . '<span class="xs mut">' . $meta . '</span></span>'
            . '<span class="num bold">' . $views . '</span>'
            . '<span><span class="num bold">' . $read . '%</span>'
            . '<span style="display:block;margin-top:5px;width:58px">' . Kit::meter($read, Kit::SERIES[1], 5) . '</span></span>'
            . '<span class="num bold">' . $signups . '</span></div>';
    }

    private static function episodeRow(int $number, string $title, string $downloads, string $completion): string
    {
        return '<div class="ep"><span class="epn">' . $number . '</span>'
            . '<span style="min-width:0"><span class="bold" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . $title . '</span>'
            . '<span class="xs mut">' . $completion . ' listened to the end</span></span>'
            . '<span class="num bold">' . $downloads . '</span>'
            . '<button class="btn tiny" type="button" aria-label="Play">' . Kit::icon('play', 14) . '</button></div>';
    }

    private static function tierRow(string $colour, string $name, string $range, string $count, string $share): string
    {
        return '<div class="tier"><span class="badge" style="background:' . $colour . '">' . mb_substr($name, 0, 1) . '</span>'
            . '<span><span class="bold" style="display:block">' . $name . '</span><span class="xs mut">' . $range . '</span></span>'
            . '<span class="num bold">' . $count . '</span>'
            . '<span class="num mut">' . $share . '</span></div>';
    }

    private static function ticketRow(string $type, string $price, string $sold, string $revenue, float $share, string $colour): string
    {
        return '<div class="tix">' . Kit::iconTile('tag', $colour, 34)
            . '<span><span class="bold" style="display:block">' . $type . ' <span class="mut" style="font-weight:500">· ' . $price . '</span></span>'
            . '<span class="xs mut">' . $sold . '</span>'
            . '<span style="display:block;margin-top:6px;max-width:170px">' . Kit::meter($share, $colour, 5) . '</span></span>'
            . '<span class="num bold">' . $revenue . '</span>'
            . '<button class="btn tiny" type="button">Edit</button></div>';
    }
}
