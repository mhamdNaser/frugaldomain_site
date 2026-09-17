<?php

namespace App\Modules\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppFeature extends Model
{
    use HasFactory;

    protected $table = 'app_features';

    protected $fillable = [
        'app_id',
        'label',
        'ordering',
    ];

    protected $casts = [
        'ordering' => 'integer',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
