<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCategory extends Model
{
    protected $table = 'learning_categories';

    protected $fillable = [
        'slug', 'name', 'name_ar', 'description', 'description_ar',
        'icon', 'color', 'status', 'ordering',
    ];

    protected $casts = [
        'ordering' => 'integer',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(LearningCourse::class, 'category_id')->orderBy('ordering')->orderBy('id');
    }
}
