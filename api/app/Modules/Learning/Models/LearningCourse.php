<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class LearningCourse extends Model
{
    protected $table = 'learning_courses';

    protected $fillable = [
        'category_id', 'slug', 'title', 'subtitle', 'description', 'level', 'language',
        'estimated_hours', 'outcomes', 'prerequisites', 'tags', 'cover_path',
        'accent_color', 'is_featured', 'status', 'ordering',
    ];

    protected $casts = [
        'outcomes' => 'array',
        'prerequisites' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'estimated_hours' => 'integer',
        'ordering' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $course): void {
            $previous = $course->getOriginal('cover_path');
            if ($course->wasChanged('cover_path') && $previous) {
                Storage::disk('local')->delete($previous);
            }
        });

        static::deleting(function (self $course): void {
            if ($course->cover_path) {
                Storage::disk('local')->delete($course->cover_path);
            }
            // The media rows are only nulled by the foreign key, so their
            // files are removed here rather than left orphaned on disk.
            LearningMedia::where('course_id', $course->id)->get()->each->delete();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'category_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(LearningStage::class, 'course_id')->orderBy('ordering')->orderBy('id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(LearningUnit::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearningLesson::class, 'course_id');
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        return url('/api/learn/courses/'.$this->slug.'/cover').'?v='.substr(md5($this->cover_path), 0, 8);
    }
}
