<?php

namespace App\Modules\Component\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'components';

    protected $fillable = [
        'slug',
        'name',
        'name_ar',
        'tagline',
        'tagline_ar',
        'summary',
        'summary_ar',
        'file_type',
        'status',
        'version',
        'accent',
        'tags',
        'stack',
        'file_path',
        'file_name',
        'file_size',
        'source',
        'preview_theme',
        'preview_height',
        'user_id',
        'ordering',
        'downloads',
    ];

    protected $casts = [
        'tags' => 'array',
        'stack' => 'array',
        'file_size' => 'integer',
        'preview_height' => 'integer',
        'ordering' => 'integer',
        'downloads' => 'integer',
    ];

    /** Only an html component can actually run in the preview frame. */
    public function isRenderable(): bool
    {
        return $this->file_type === 'html';
    }

    public function features()
    {
        return $this->hasMany(ComponentFeature::class, 'component_id')->orderBy('ordering');
    }

    public function categories()
    {
        return $this->belongsToMany(
            ComponentCategory::class,
            'component_category',
            'component_id',
            'component_category_id',
        )->orderBy('ordering');
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
