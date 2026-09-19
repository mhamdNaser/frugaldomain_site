<?php

namespace App\Modules\Component\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponentCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'component_categories';

    protected $fillable = [
        'slug',
        'name',
        'name_ar',
        'description',
        'accent',
        'icon',
        'is_active',
        'ordering',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordering' => 'integer',
    ];

    public function components()
    {
        return $this->belongsToMany(
            Component::class,
            'component_category',
            'component_category_id',
            'component_id',
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
