<?php

namespace App\Modules\Learning\Services;

use App\Modules\Learning\Models\LearningCategory;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningMedia;
use App\Modules\Learning\Models\LearningStage;
use App\Modules\Learning\Models\LearningUnit;

/**
 * The JSON shapes the API returns. Public and dashboard responses share them;
 * `$admin` adds the ids and drafts only the dashboard needs.
 */
class LearningPresenter
{
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
            'title' => $course->title,
            'subtitle' => $course->subtitle,
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
                'id', 'unit_id', 'course_id', 'slug', 'title', 'summary',
                'duration_minutes', 'status', 'ordering',
            ]);
        }]);

        $lessonsCount = 0;
        $publishedCount = 0;
        $minutes = 0;

        $stages = $course->stages->map(function (LearningStage $stage) use ($admin, &$lessonsCount, &$publishedCount, &$minutes) {
            return [
                'slug' => $stage->slug,
                'title' => $stage->title,
                'code' => $stage->code,
                'summary' => $stage->summary,
                'units' => $stage->units->map(function (LearningUnit $unit) use ($admin, &$lessonsCount, &$publishedCount, &$minutes) {
                    return [
                        'slug' => $unit->slug,
                        'title' => $unit->title,
                        'summary' => $unit->summary,
                        'lessons' => $unit->lessons->map(function (LearningLesson $lesson) use ($admin, &$lessonsCount, &$publishedCount, &$minutes) {
                            $lessonsCount++;
                            if ($lesson->isPublished()) {
                                $publishedCount++;
                                $minutes += (int) $lesson->duration_minutes;
                            }

                            $row = [
                                'slug' => $lesson->slug,
                                'title' => $lesson->title,
                                'summary' => $lesson->summary,
                                'duration_minutes' => $lesson->duration_minutes,
                                'available' => $lesson->isPublished(),
                            ];

                            return $admin
                                ? ['id' => $lesson->id, 'unit_id' => $lesson->unit_id, 'status' => $lesson->status, 'ordering' => $lesson->ordering] + $row
                                : $row;
                        })->values()->all(),
                    ] + ($admin ? ['id' => $unit->id, 'stage_id' => $unit->stage_id, 'ordering' => $unit->ordering] : []);
                })->values()->all(),
            ] + ($admin ? ['id' => $stage->id, 'ordering' => $stage->ordering] : []);
        })->values()->all();

        $card = $this->courseCard($course, $admin);
        $card['stages_count'] = count($stages);
        $card['lessons_count'] = $lessonsCount;
        $card['published_lessons_count'] = $publishedCount;

        return $card + [
            'description' => $course->description,
            'outcomes' => $course->outcomes ?? [],
            'prerequisites' => $course->prerequisites ?? [],
            'total_minutes' => $minutes,
            'stages' => $stages,
        ];
    }

    /** One lesson with its place in the course and its neighbours. */
    public function lesson(LearningLesson $lesson, LearningCourse $course, bool $admin = false): array
    {
        $course->loadMissing(['category', 'stages.units.lessons' => function ($query) {
            $query->select(['id', 'unit_id', 'course_id', 'slug', 'title', 'status', 'ordering']);
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
        $neighbour = function ($l) {
            return $l ? ['slug' => $l->slug, 'title' => $l->title] : null;
        };

        $data = [
            'slug' => $lesson->slug,
            'title' => $lesson->title,
            'summary' => $lesson->summary,
            'duration_minutes' => $lesson->duration_minutes,
            'objectives' => $lesson->objectives ?? [],
            'blocks' => $this->resolveMedia($lesson->blocks ?? []),
            'updated_at' => $lesson->updated_at?->toIso8601String(),
            'course' => [
                'slug' => $course->slug,
                'title' => $course->title,
                'language' => $course->language,
                'accent_color' => $course->accent_color ?: $course->category?->color,
                'category' => $course->category ? [
                    'slug' => $course->category->slug,
                    'name' => $course->category->name,
                    'name_ar' => $course->category->name_ar,
                ] : null,
            ],
            'stage' => $stage ? ['slug' => $stage->slug, 'title' => $stage->title, 'code' => $stage->code] : null,
            'unit' => $unit ? ['slug' => $unit->slug, 'title' => $unit->title] : null,
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
                'raw_blocks' => $lesson->blocks ?? [],
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
