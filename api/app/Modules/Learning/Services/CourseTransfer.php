<?php

namespace App\Modules\Learning\Services;

use App\Modules\Learning\Models\LearningCategory;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningStage;
use App\Modules\Learning\Models\LearningUnit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Moves a whole course in and out of the database as one JSON document
 * (format "frugal-course@1"). The dashboard's import/export buttons and the
 * curriculum seeder both go through here, so a course written by hand, one
 * exported from another install and the shipped curriculum are the same file.
 *
 *   {
 *     "format": "frugal-course@1",
 *     "category": { "slug", "name", "name_ar", "description", "icon", "color" },
 *     "course":   { "slug", "title", "subtitle", "description", "level", ... },
 *     "stages": [ { "slug", "title", "code", "summary",
 *       "units": [ { "slug", "title", "summary",
 *         "lessons": [ { "slug", "title", "summary", "duration_minutes",
 *                        "objectives": [], "status", "blocks": [] } ] } ] } ]
 *   }
 *
 * Modes:
 *   replace - the course's stages, units and lessons are rebuilt from the file.
 *   merge   - everything in the file is created or updated by slug; items that
 *             exist only in the database are left alone.
 *   fill    - only what is missing is created, and a lesson's content is
 *             written only while that lesson is still empty. Safe to run over
 *             a course that has been edited in the dashboard.
 */
class CourseTransfer
{
    public const FORMAT = 'frugal-course@1';

    public const MODES = ['replace', 'merge', 'fill'];

    public function import(array $document, string $mode = 'merge'): LearningCourse
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException("Unknown import mode [{$mode}].");
        }

        $this->assertShape($document);

        return DB::transaction(function () use ($document, $mode) {
            $category = $this->importCategory($document['category'], $mode);
            $course = $this->importCourse($category, $document['course'], $mode);

            if ($mode === 'replace') {
                $course->stages()->delete();
            }

            $usedStageSlugs = [];
            $usedUnitSlugs = [];
            $usedLessonSlugs = [];

            foreach (array_values($document['stages'] ?? []) as $stageIndex => $stageData) {
                $stageSlug = $this->uniqueSlug($stageData['slug'] ?? $stageData['title'], $usedStageSlugs);
                $stage = $this->upsert(
                    LearningStage::query()->where('course_id', $course->id)->where('slug', $stageSlug)->first(),
                    new LearningStage(['course_id' => $course->id, 'slug' => $stageSlug]),
                    [
                        'title' => $stageData['title'],
                        'code' => $stageData['code'] ?? null,
                        'summary' => $stageData['summary'] ?? null,
                        'ordering' => $stageIndex + 1,
                    ],
                    $mode
                );

                foreach (array_values($stageData['units'] ?? []) as $unitIndex => $unitData) {
                    $unitSlug = $this->uniqueSlug($unitData['slug'] ?? $unitData['title'], $usedUnitSlugs);
                    $unit = $this->upsert(
                        LearningUnit::query()->where('course_id', $course->id)->where('slug', $unitSlug)->first(),
                        new LearningUnit(['course_id' => $course->id, 'slug' => $unitSlug]),
                        [
                            'stage_id' => $stage->id,
                            'title' => $unitData['title'],
                            'summary' => $unitData['summary'] ?? null,
                            'ordering' => $unitIndex + 1,
                        ],
                        $mode
                    );

                    foreach (array_values($unitData['lessons'] ?? []) as $lessonIndex => $lessonData) {
                        $lessonSlug = $this->uniqueSlug($lessonData['slug'] ?? $lessonData['title'], $usedLessonSlugs);
                        $this->importLesson($course, $unit, $lessonSlug, $lessonData, $lessonIndex + 1, $mode);
                    }
                }
            }

            return $course->fresh();
        });
    }

    public function export(LearningCourse $course): array
    {
        $course->load(['category', 'stages.units.lessons']);

        return [
            'format' => self::FORMAT,
            'exported_at' => now()->toIso8601String(),
            'category' => Arr::only($course->category->toArray(), [
                'slug', 'name', 'name_ar', 'description', 'description_ar', 'icon', 'color', 'ordering',
            ]),
            'course' => Arr::only($course->toArray(), [
                'slug', 'title', 'subtitle', 'description', 'level', 'language', 'estimated_hours',
                'outcomes', 'prerequisites', 'tags', 'accent_color', 'is_featured', 'status', 'ordering',
            ]),
            'stages' => $course->stages->map(fn (LearningStage $stage) => [
                'slug' => $stage->slug,
                'title' => $stage->title,
                'code' => $stage->code,
                'summary' => $stage->summary,
                'units' => $stage->units->map(fn (LearningUnit $unit) => [
                    'slug' => $unit->slug,
                    'title' => $unit->title,
                    'summary' => $unit->summary,
                    'lessons' => $unit->lessons->map(fn (LearningLesson $lesson) => [
                        'slug' => $lesson->slug,
                        'title' => $lesson->title,
                        'summary' => $lesson->summary,
                        'duration_minutes' => $lesson->duration_minutes,
                        'objectives' => $lesson->objectives ?? [],
                        'status' => $lesson->status,
                        'blocks' => $lesson->blocks ?? [],
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function importCategory(array $data, string $mode): LearningCategory
    {
        $slug = Str::slug($data['slug'] ?? $data['name']);

        return $this->upsert(
            LearningCategory::query()->where('slug', $slug)->first(),
            new LearningCategory(['slug' => $slug]),
            Arr::only(array_merge(['icon' => 'book', 'color' => '#0ea5e9', 'status' => 'active'], $data), [
                'name', 'name_ar', 'description', 'description_ar', 'icon', 'color', 'status', 'ordering',
            ]),
            $mode
        );
    }

    private function importCourse(LearningCategory $category, array $data, string $mode): LearningCourse
    {
        $slug = Str::slug($data['slug'] ?? $data['title']);
        $fields = Arr::only($data, [
            'title', 'subtitle', 'description', 'level', 'language', 'estimated_hours',
            'outcomes', 'prerequisites', 'tags', 'accent_color', 'is_featured', 'status', 'ordering',
        ]);
        $fields['category_id'] = $category->id;

        return $this->upsert(
            LearningCourse::query()->where('slug', $slug)->first(),
            new LearningCourse(['slug' => $slug, 'status' => 'draft']),
            $fields,
            $mode
        );
    }

    private function importLesson(
        LearningCourse $course,
        LearningUnit $unit,
        string $slug,
        array $data,
        int $ordering,
        string $mode
    ): void {
        $blocks = LessonBlocks::sanitize($data['blocks'] ?? [], "lesson[{$slug}].blocks");
        $content = [
            'summary' => $data['summary'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'objectives' => array_values($data['objectives'] ?? []),
            'blocks' => $blocks,
            'status' => in_array($data['status'] ?? null, ['draft', 'published'], true)
                ? $data['status']
                : ($blocks ? 'published' : 'draft'),
        ];
        $placement = ['unit_id' => $unit->id, 'title' => $data['title'], 'ordering' => $ordering];

        $existing = LearningLesson::query()->where('course_id', $course->id)->where('slug', $slug)->first();

        if (! $existing) {
            LearningLesson::create(array_merge(
                ['course_id' => $course->id, 'slug' => $slug],
                $placement,
                $content
            ));

            return;
        }

        if ($mode === 'fill') {
            // Never overwrite work done in the dashboard: an existing lesson
            // only receives content while it has none of its own.
            if (empty($existing->blocks) && $blocks) {
                $existing->fill($content)->save();
            }

            return;
        }

        $existing->fill(array_merge($placement, $content))->save();
    }

    /**
     * Create `$fresh` with `$fields`, or update `$existing` with them - except
     * in fill mode, where an existing row is returned untouched.
     */
    private function upsert($existing, $fresh, array $fields, string $mode)
    {
        if ($existing) {
            if ($mode !== 'fill') {
                $existing->fill($fields)->save();
            }

            return $existing;
        }

        $fresh->fill($fields)->save();

        return $fresh;
    }

    private function uniqueSlug(string $value, array &$used): string
    {
        $base = Str::slug($value) ?: 'item';
        $base = substr($base, 0, 110);
        $slug = $base;
        $n = 2;
        while (isset($used[$slug])) {
            $slug = $base.'-'.$n++;
        }
        $used[$slug] = true;

        return $slug;
    }

    private function assertShape(array $document): void
    {
        $errors = [];
        if (($document['format'] ?? self::FORMAT) !== self::FORMAT) {
            $errors['format'] = ['Unsupported course format. Expected "'.self::FORMAT.'".'];
        }
        if (empty($document['category']['name']) && empty($document['category']['slug'])) {
            $errors['category'] = ['The file must name a category (category.name).'];
        }
        if (empty($document['course']['title'])) {
            $errors['course.title'] = ['The file must include course.title.'];
        }
        foreach (array_values($document['stages'] ?? []) as $s => $stage) {
            if (empty($stage['title'])) {
                $errors["stages.{$s}.title"] = ['Every stage needs a title.'];
            }
            foreach (array_values($stage['units'] ?? []) as $u => $unit) {
                if (empty($unit['title'])) {
                    $errors["stages.{$s}.units.{$u}.title"] = ['Every unit needs a title.'];
                }
                foreach (array_values($unit['lessons'] ?? []) as $l => $lesson) {
                    if (empty($lesson['title'])) {
                        $errors["stages.{$s}.units.{$u}.lessons.{$l}.title"] = ['Every lesson needs a title.'];
                    }
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
