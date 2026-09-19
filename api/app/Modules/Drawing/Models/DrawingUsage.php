<?php

namespace App\Modules\Drawing\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class DrawingUsage extends Model
{
    protected $fillable = [
        'drawing_id',
        'user_id',
        'used_in_drawing_id',
        'points_awarded',
        'ip_address',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function drawing()
    {
        return $this->belongsTo(Drawing::class, 'drawing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
