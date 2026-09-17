<?php

namespace App\Modules\Core\Models;

use App\Modules\Icon\Models\Icon;
use App\Modules\User\Models\User;
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
