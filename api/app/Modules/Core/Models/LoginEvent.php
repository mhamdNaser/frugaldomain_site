<?php

namespace App\Modules\Core\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class LoginEvent extends Model
{
    protected $table = 'login_events';

    protected $fillable = [
        'user_id',
        'email',
        'successful',
        'failure_reason',
        'provider',
        'ip_address',
        'country',
        'device_type',
        'browser',
        'platform',
        'user_agent',
        'logged_in_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'logged_in_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
