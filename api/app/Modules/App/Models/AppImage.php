<?php

namespace App\Modules\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppImage extends Model
{
    use HasFactory;

    protected $table = 'app_images';

    /** The only two roles an image row may carry. */
    public const ROLE_MAIN = 'main';
    public const ROLE_SECONDARY = 'secondary';

    /** How many secondary screenshots a project is expected to ship. */
    public const SECONDARY_COUNT = 3;

    protected $fillable = [
        'app_id',
        'path',
        'role',
        'alt',
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
