<?php

namespace App\Modules\Core\Models;

use App\Modules\User\Models\User;
use App\Modules\Core\Models\Scopes\ExcludeStaffScope;
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

    protected static function booted(): void
    {
        // The site's own staff are never counted (see ExcludeStaffScope). Sign-ins carry no browser id, so only the account is checked.
        static::addGlobalScope('excludeStaff', new ExcludeStaffScope(false));
    }

    protected $casts = [
        'successful' => 'boolean',
        'logged_in_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
