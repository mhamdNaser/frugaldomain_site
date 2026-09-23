<?php

namespace App\Modules\Account\Models;

use App\Modules\Component\Models\Component;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class SavedComponent extends Model
{
    protected $table = 'saved_components';

    protected $fillable = [
        'user_id',
        'component_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }
}
