<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Models\Scopes\ExcludeStaffScope;
use Illuminate\Database\Eloquent\Model;

/** One use of a tool (see AnalyticsRecorder::recordToolUse). */
class ToolUse extends Model
{
    protected $table = 'tool_uses';

    protected $fillable = [
        'tool',
        'action',
        'locale',
        'user_id',
        'session_id',
        'country',
        'device_type',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('excludeStaff', new ExcludeStaffScope());
    }
}
