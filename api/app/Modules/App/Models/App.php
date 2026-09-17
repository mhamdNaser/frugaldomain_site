<?php

namespace App\Modules\App\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class App extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'apps';

    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'summary',
        'type',
        'status',
        'version',
        'updated_on',
        'accent',
        'tags',
        'stack',
        'docs',
        'repository',
        'preview_mode',
        'preview_url',
        'subdomain',
        'preview_embed',
        'preview_open_in_new_tab',
        'archive_path',
        'archive_name',
        'archive_size',
        'main_image',
        'cover',
        'user_id',
        'ordering',
    ];

    protected $casts = [
        'tags' => 'array',
        'stack' => 'array',
        'docs' => 'array',
        'preview_embed' => 'boolean',
        'preview_open_in_new_tab' => 'boolean',
        'updated_on' => 'date',
        'archive_size' => 'integer',
        'ordering' => 'integer',
    ];

    public function features()
    {
        return $this->hasMany(AppFeature::class, 'app_id')->orderBy('ordering');
    }

    public function images()
    {
        return $this->hasMany(AppImage::class, 'app_id')->orderBy('ordering');
    }

    /** The single cover screenshot, if one has been uploaded. */
    public function mainImage()
    {
        return $this->hasOne(AppImage::class, 'app_id')->where('role', 'main');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
