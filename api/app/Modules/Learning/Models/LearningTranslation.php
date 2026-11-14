<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

/** One model's text in one extra language (see the create migration). */
class LearningTranslation extends Model
{
    protected $table = 'learning_translations';

    protected $fillable = ['model_type', 'model_id', 'locale', 'fields', 'blocks'];

    protected $casts = [
        'fields' => 'array',
        'blocks' => 'array',
    ];

    /** Model class => the short name stored in `model_type`. */
    public const TYPES = [
        LearningCategory::class => 'category',
        LearningCourse::class => 'course',
        LearningStage::class => 'stage',
        LearningUnit::class => 'unit',
        LearningLesson::class => 'lesson',
    ];

    /** Locales served from this table. Arabic has its own columns. */
    public const LOCALES = ['hi', 'zh', 'tr'];
}
