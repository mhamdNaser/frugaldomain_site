<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningStage extends Model
{
    protected $table = 'learning_stages';

    protected $fillable = ['course_id', 'slug', 'title', 'code', 'summary', 'ordering'];

    protected $casts = ['ordering' => 'integer'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(LearningUnit::class, 'stage_id')->orderBy('ordering')->orderBy('id');
    }
}
