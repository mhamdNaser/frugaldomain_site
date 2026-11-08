<?php

namespace App\Modules\Learning\Services;

use App\Modules\Learning\Models\LearningCategory;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningMedia;
use App\Modules\Learning\Models\LearningStage;
use App\Modules\Learning\Models\LearningUnit;
use Illuminate\Support\Facades\DB;

/**
 * The JSON shapes the API returns.
 *
 * Public responses are in one language: the one the page asks for with
 * `X-Language` (or `?lang=`). Each Arabic field falls back to its English
 * twin when it is empty, so a half-translated course still reads in full.
 * Dashboard responses (`$admin`) carry both languages side by side instead.
 */
class LearningPresenter
{
    /**
     * Read from the current request every time: the router keeps controller
     * instances (and so this presenter) alive across requests.
     */
    public function isArabic(): bool
    {
        $request = request();
        $lang = $request?->query('lang') ?: $request?->header('X-Language');

        return strtolower((string) $lang) === 'ar';
    }

    /** `$model->field` in the requested language, falling back to English. */
    private function tr($model, string $field)
    {
        if ($this->isArabic()) {
            $value = $model->{$field.'_ar'} ?? null;
            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return $model->{$field};
    }

    private function hasArabicContent(LearningLesson $lesson): bool
    {
        return ! empty($lesson->blocks_ar);
    }

    public function category(LearningCategory $category, bool $admin = false): array
    {
        $data = [
            'slug' => $category->slug,
            'name' => $category->name,
            'name_ar' => $category->name_ar,
            'description' => $category->description,
            'description_ar' => $category->description_ar,
            'icon' => $category->icon,
            'color' => $category->color,
            'courses_count' => (int) ($category->courses_count ?? 0),
        ];

        if ($admin) {
            $data = ['id' => $category->id] + $data + [
                'status' => $category->status,
                'ordering' => $category->ordering,
            ];
        }

        return $data;
    }

    /** A course as a catalogue card. Expects the `withCount` aliases below. */
    public function courseCard(LearningCourse $course, bool $admin = false): array
    {
        $data = [
            'slug' => $course->slug,
            'title' => $admin ? $course->title : $this->tr($course, 'title'),
            'subtitle' => $admin ? $course->subtitle : $this->tr($course, 'subtitle'),
            'level' => $course->level,
            'language' => $course->language,
            'estimated_hours' => $course->estimated_hours,
            'tags' => $course->tags ?? [],
            'accent_color' => $course->accent_color ?: $course->category?->color,
            'is_featured' => (bool) $course->is_featured,
            'cover_url' => $course->coverUrl(),
            'category' => $course->category ? [
                'slug' => $course->category->slug,
                'name' => $course->category->name,
                'name_ar' => $course->category->name_ar,
                'color' => $course->category->color,
                'icon' => $course->category->icon,
            ] : null,
            'stages_count' => (int) ($course->stages_count ?? 0),
            'lessons_count' => (int) ($course->lessons_count ?? 0),
            'published_lessons_count' => (int) ($course->published_lessons_count ?? 0),
        ];

        if ($admin) {
            $data = ['id' => $course->id, 'category_id' => $course->category_id] + $data + [
                'title_ar' => $course->title_ar,
                'subtitle_ar' => $course->subtitle_ar,
                'status' => $course->status,
                'ordering' => $course->ordering,
                'updated_at' => $course->updated_at?->toIso8601String(),
            ];
        }

        return $data;
    }

    /** The course page: details plus the full syllabus tree. */
    public function courseDetail(LearningCourse $course, bool $admin = false): array
    {
        $course->loadMissing(['category', 'stages.units.lessons' => function ($query) {
            $query->select([
                'id', 'unit_id', 'course_id', 'slug', 'title', 'title_ar', 'summary', 'summary_ar',
                'duration_minutes', 'status', 'ordering',
                // Only whether Arabic content exists is needed here, not the content.
                DB::raw("CASE WHEN blocks_ar IS NULL OR JSON_LENGTH(blocks_ar) = 0 THEN 0 ELSE 1 END as has_ar"),
            ]);
        }]);

        $lessonsCount = 0;
        $publishedCount = 0;
        $minutes = 0;

        $node = function ($model, array $extra) use ($admin) {
            $row = [
                'slug' => $model->slug,
                'title' => $admin ? $model->title : $this->tr($model, 'title'),
                'summary' => $admin ? $model->summary : $this->tr($model, 'summary'),
            ];
            if ($admin) {
                $row += ['title_ar' => $model->title_ar, 'summary_ar' => $model->summary_ar];
            }

            return $row + $extra;
        };

        $stages = $course->stages->map(function (LearningStage $stage) use ($admin, $node, &$lessonsCount, &$publishedCount, &$minutes) {
            $units = $stage->units->map(function (LearningUnit $unit) use ($admin, $node, &$lessonsCount, &$publishedCount, &$minutes) {
                $lessons = $unit->lessons->map(function (LearningLesson $lesson) use ($admin, $node, &$lessonsCount, &$publishedCount, &$minutes) {
                    $lessonsCount++;
                    if ($lesson->isPublished()) {
                        $publishedCount++;
                        $minutes += (int) $lesson->duration_minutes;
                    }

                    return $node($lesson, [
                        'duration_minutes' => $lesson->duration_minutes,
                        'available' => $lesson->isPublished(),
                        'has_ar' => (bool) ($lesson->has_ar ?? false),
                    ] + ($admin ? ['id' => $lesson->id, 'unit_id' => $lesson->unit_id, 'status' => $lesson->status, 'ordering' => $lesson->ordering] : []));
                })->values()->all();

                return $node($unit, ['lessons' => $lessons]
                    + ($admin ? ['id' => $unit->id, 'stage_id' => $unit->stage_id, 'ordering' => $unit->ordering] : []));
            })->values()->all();

            return $node($stage, ['code' => $stage->code, 'units' => $units]
                + ($admin ? ['id' => $stage->id, 'ordering' => $stage->ordering] : []));
        })->values()->all();

        $card = $this->courseCard($course, $admin);
        $card['stages_count'] = count($stages);
        $card['lessons_count'] = $lessonsCount;
        $card['published_lessons_count'] = $publishedCount;

        $details = $admin
            ? [
                'description' => $course->description,
                'description_ar' => $course->description_ar,
                'outcomes' => $course->outcomes ?? [],
                'outcomes_ar' => $course->outcomes_ar ?? [],
                'prerequisites' => $course->prerequisites ?? [],
                'prerequisites_ar' => $course->prerequisites_ar ?? [],
            ]
            : [
                'description' => $this->tr($course, 'description'),
                'outcomes' => $this->tr($course, 'outcomes') ?? [],
                'prerequisites' => $this->tr($course, 'prerequisites') ?? [],
            ];

        return $card + $details + [
            'total_minutes' => $minutes,
            'stages' => $stages,
        ];
    }

    /** One lesson with its place in the course and its neighbours. */
    public function lesson(LearningLesson $lesson, LearningCourse $course, bool $admin = false): array
    {
        $course->loadMissing(['category', 'stages.units.lessons' => function ($query) {
            $query->select(['id', 'unit_id', 'course_id', 'slug', 'title', 'title_ar', 'status', 'ordering']);
        }]);

        // The course order, flattened: stage → unit → lesson.
        $sequence = [];
        $stage = null;
        $unit = null;
        foreach ($course->stages as $s) {
            foreach ($s->units as $u) {
                foreach ($u->lessons as $l) {
                    if (! $admin && ! $l->isPublished() && $l->id !== $lesson->id) {
                        continue;
                    }
                    $sequence[] = $l;
                    if ($l->id === $lesson->id) {
                        $stage = $s;
                        $unit = $u;
                    }
                }
            }
        }

        $position = collect($sequence)->search(fn ($l) => $l->id === $lesson->id);
        $neighbour = fn ($l) => $l ? ['slug' => $l->slug, 'title' => $this->tr($l, 'title')] : null;

        // Arabic content is served whole or not at all: mixing languages
        // block by block would read worse than either.
        $arabicContent = $this->isArabic() && $this->hasArabicContent($lesson);

        $data = [
            'slug' => $lesson->slug,
            'title' => $this->tr($lesson, 'title'),
            'summary' => $arabicContent ? ($lesson->summary_ar ?: $lesson->summary) : $lesson->summary,
            'duration_minutes' => $lesson->duration_minutes,
            'objectives' => $arabicContent ? ($lesson->objectives_ar ?: $lesson->objectives ?? []) : ($lesson->objectives ?? []),
            'blocks' => $this->resolveMedia(($arabicContent ? $lesson->blocks_ar : $lesson->blocks) ?? []),
            'content_language' => $arabicContent ? 'ar' : 'en',
            'has_ar' => $this->hasArabicContent($lesson),
            'updated_at' => $lesson->updated_at?->toIso8601String(),
            'course' => [
                'slug' => $course->slug,
                'title' => $this->tr($course, 'title'),
                'language' => $course->language,
                'accent_color' => $course->accent_color ?: $course->category?->color,
                'category' => $course->category ? [
                    'slug' => $course->category->slug,
                    'name' => $course->category->name,
                    'name_ar' => $course->category->name_ar,
                ] : null,
            ],
            'stage' => $stage ? ['slug' => $stage->slug, 'title' => $this->tr($stage, 'title'), 'code' => $stage->code] : null,
            'unit' => $unit ? ['slug' => $unit->slug, 'title' => $this->tr($unit, 'title')] : null,
            'position' => $position === false ? null : $position + 1,
            'total' => count($sequence),
            'previous' => $position ? $neighbour($sequence[$position - 1] ?? null) : null,
            'next' => $position === false ? null : $neighbour($sequence[$position + 1] ?? null),
        ];

        if ($admin) {
            $data = [
                'id' => $lesson->id,
                'unit_id' => $lesson->unit_id,
                'course_id' => $lesson->course_id,
                'status' => $lesson->status,
                'ordering' => $lesson->ordering,
                'en' => [
                    'title' => $lesson->title,
                    'summary' => $lesson->summary,
                    'objectives' => $lesson->objectives ?? [],
                    'blocks' => $lesson->blocks ?? [],
                ],
                'ar' => [
                    'title' => $lesson->title_ar,
                    'summary' => $lesson->summary_ar,
                    'objectives' => $lesson->objectives_ar ?? [],
                    'blocks' => $lesson->blocks_ar ?? [],
                ],
            ] + $data;
        }

        return $data;
    }

    /** Fill in the url of image/video blocks that point at an uploaded file. */
    public function resolveMedia(array $blocks): array
    {
        $ids = collect($blocks)
            ->filter(fn ($block) => ! empty($block['media_id']))
            ->pluck('media_id')
            ->unique()
            ->all();

        if (! $ids) {
            return $blocks;
        }

        $media = LearningMedia::query()->whereIn('id', $ids)->get()->keyBy('id');

        return array_map(function (array $block) use ($media) {
            if (! empty($block['media_id']) && $media->has($block['media_id'])) {
                $block['url'] = $media[$block['media_id']]->url();
            }

            return $block;
        }, $blocks);
    }
}
