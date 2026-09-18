<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Services\AnalyticsRecorder;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $table = 'page_visits';

    protected $fillable = [
        'user_id',
        'session_id',
        'path',
        'page_title',
        'referrer',
        'ip_address',
        'country',
        'device_type',
        'browser',
        'platform',
        'user_agent',
        'duration_seconds',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    /**
     * Applied to every reporting query so the admin dashboard never appears in
     * visitor analytics.
     *
     * New visits to those paths are already dropped before being written, but
     * rows recorded before that rule existed are still in the table, so the
     * reports have to filter as well.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('publicPaths', function (Builder $query) {
            foreach (AnalyticsRecorder::EXCLUDED_PATH_PREFIXES as $prefix) {
                $query->where(function (Builder $inner) use ($prefix) {
                    $inner->where('path', '!=', $prefix)
                        ->where('path', 'not like', $prefix . '/%');
                });
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
