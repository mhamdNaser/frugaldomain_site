<?php

namespace App\Modules\Core\Models;

use App\Modules\Icon\Models\Icon;
use App\Modules\User\Models\User;
use App\Modules\Core\Models\Scopes\ExcludeStaffScope;
use Illuminate\Database\Eloquent\Model;

class IconEvent extends Model
{
    protected $table = 'icon_events';

    protected $fillable = [
        'icon_id',
        'user_id',
        'session_id',
        'event_type',
        'ip_address',
        'country',
        'device_type',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        // The site's own staff are never counted (see ExcludeStaffScope).
        static::addGlobalScope('excludeStaff', new ExcludeStaffScope());
    }

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function icon()
    {
        return $this->belongsTo(Icon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
