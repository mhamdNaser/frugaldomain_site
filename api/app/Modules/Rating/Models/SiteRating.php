<?php

namespace App\Modules\Rating\Models;

use Illuminate\Database\Eloquent\Model;

class SiteRating extends Model
{
    protected $fillable = ['path', 'stars', 'comment', 'locale', 'fingerprint'];

    protected $hidden = ['fingerprint'];

    protected $casts = ['stars' => 'integer'];

    /** The page's average (one decimal) and number of ratings. */
    public static function summary(string $path): array
    {
        $row = static::where('path', $path)->selectRaw('AVG(stars) as average, COUNT(*) as count')->first();
        $count = (int) ($row->count ?? 0);

        return [
            'path' => $path,
            'average' => $count ? round((float) $row->average, 1) : null,
            'count' => $count,
        ];
    }
}
