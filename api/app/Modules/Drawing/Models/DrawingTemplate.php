<?php

namespace App\Modules\Drawing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A starter template offered inside the editor.
 *
 * The document holds real vector elements, not a picture of them, so anything
 * dropped on the canvas from here behaves exactly like something the user
 * drew: every tool, effect, clip and boolean op applies to it.
 */
class DrawingTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'category',
        'document',
        'thumbnail_path',
        'width',
        'height',
        'sort_order',
        'is_active',
        'tags',
    ];

    protected $casts = [
        'document' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    /** Listing a category must not drag every document along with it. */
    protected $hidden = ['document'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
