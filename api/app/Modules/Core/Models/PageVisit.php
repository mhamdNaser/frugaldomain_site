<?php

namespace App\Modules\Core\Models;

use App\Modules\User\Models\User;
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
