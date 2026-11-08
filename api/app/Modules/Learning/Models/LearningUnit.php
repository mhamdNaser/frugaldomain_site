<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningUnit extends Model
{
    protected $table = 'learning_units';

    protected $fillable = ['stage_id', 'course_id', 'slug', 'title', 'summary', 'ordering'];

    protected $casts = ['ordering' => 'integer'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(LearningStage::class, 'stage_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearningLesson::class, 'unit_id')->orderBy('ordering')->orderBy('id');
    }
}
