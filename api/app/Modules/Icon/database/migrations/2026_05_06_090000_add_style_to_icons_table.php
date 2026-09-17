<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Icons are either line art or filled art. */
    private const STYLE_OUTLINE = 'outline';
    private const STYLE_SOLID = 'solid';

    public function up(): void
    {
        Schema::table('icons', function (Blueprint $table) {
            // Indexed because the public gallery filters on it on every page
            // request, alongside category_id and is_active.
            $table->string('style', 32)
                ->default(self::STYLE_OUTLINE)
                ->index()
                ->after('description');
        });

        // Composite index matching the gallery's hot query: the paginated
        // public endpoint always filters active icons and orders by id desc,
        // usually narrowed by style and/or category.
        Schema::table('icons', function (Blueprint $table) {
            $table->index(['is_active', 'style', 'category_id'], 'icons_active_style_category_index');
        });

        $this->backfillStyles();
    }

    public function down(): void
    {
        Schema::table('icons', function (Blueprint $table) {
            $table->dropIndex('icons_active_style_category_index');
            $table->dropIndex(['style']);
            $table->dropColumn('style');
        });
    }

    /**
     * Derive each existing icon's style from its own artwork.
     *
     * The rule reads the root <svg> element only: artwork that declares
     * fill="none" together with a stroke is line art (the Heroicons-style
     * outline set), everything else paints with fills and is solid (the
     * flag set). Inner elements are deliberately ignored — several flags
     * carry a stroked detail path inside otherwise filled artwork and would
     * be misread as outlines if nested nodes counted.
     *
     * Icons whose SVG is missing on disk keep the column default.
     */
    private function backfillStyles(): void
    {
        DB::table('icons')
            ->select('id', 'file_svg')
            ->orderBy('id')
            ->chunkById(200, function ($icons): void {
                $solidIds = [];
                $outlineIds = [];

                foreach ($icons as $icon) {
                    $style = $this->styleForSvg($icon->file_svg);

                    if ($style === null) {
                        continue;
                    }

                    if ($style === self::STYLE_SOLID) {
                        $solidIds[] = $icon->id;
                    } else {
                        $outlineIds[] = $icon->id;
                    }
                }

                if ($solidIds !== []) {
                    DB::table('icons')->whereIn('id', $solidIds)->update(['style' => self::STYLE_SOLID]);
                }

                if ($outlineIds !== []) {
                    DB::table('icons')->whereIn('id', $outlineIds)->update(['style' => self::STYLE_OUTLINE]);
                }
            });
    }

    /**
     * @return string|null  null when the artwork cannot be read.
     */
    private function styleForSvg(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $absolute = public_path(ltrim($relativePath, '/'));

        if (!File::exists($absolute) || !File::isFile($absolute)) {
            return null;
        }

        // The root element is always within the first bytes of the document;
        // reading the whole file for 500+ icons is needless IO.
        $head = (string) file_get_contents($absolute, false, null, 0, 2048);

        if ($head === '' || !preg_match('/<svg\b[^>]*>/i', $head, $match)) {
            return null;
        }

        $root = $match[0];

        $hasNoFill = (bool) preg_match('/\bfill\s*=\s*(["\'])\s*none\s*\1/i', $root);
        $hasStroke = (bool) preg_match('/\bstroke\s*=\s*["\']/i', $root);

        return ($hasNoFill && $hasStroke) ? self::STYLE_OUTLINE : self::STYLE_SOLID;
    }
};
