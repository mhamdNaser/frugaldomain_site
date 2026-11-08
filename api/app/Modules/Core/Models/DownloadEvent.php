<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Models\Scopes\ExcludeStaffScope;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One download of a program (see AnalyticsRecorder::recordDownload). */
class DownloadEvent extends Model
{
    protected $table = 'download_events';

    protected $fillable = [
        'item_type',
        'item_id',
        'item_slug',
        'item_name',
        'user_id',
        'session_id',
        'ip_address',
        'country',
        'device_type',
        'platform',
        'referrer',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('excludeStaff', new ExcludeStaffScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
