<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningLesson extends Model
{
    protected $table = 'learning_lessons';

    protected $fillable = [
        'unit_id', 'course_id', 'slug', 'title', 'summary', 'duration_minutes',
        'objectives', 'blocks', 'status', 'ordering',
    ];

    protected $casts = [
        'objectives' => 'array',
        'blocks' => 'array',
        'duration_minutes' => 'integer',
        'ordering' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(LearningUnit::class, 'unit_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
