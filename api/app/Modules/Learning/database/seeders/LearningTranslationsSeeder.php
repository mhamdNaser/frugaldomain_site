<?php

namespace App\Modules\Learning\database\seeders;

use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningTranslation;
use App\Modules\Learning\Services\LessonBlocks;
use Illuminate\Database\Seeder;

/**
 * Loads the languages beyond English and Arabic into learning_translations.
 *
 *   translations/<locale>/curriculum/<course>.json
 *       { "category": {name, description},
 *         "course":   {title, subtitle, description, outcomes, prerequisites},
 *         "stages":   { "<stage slug>": {title, summary} },
 *         "units":    { "<unit slug>":  {title, summary} },
 *         "lessons":  { "<lesson slug>": "title" } }
 *   translations/<locale>/lessons/<course>/<lesson>.json
 *       { title, summary, objectives, blocks }   (blocks mirror the English ones)
 *
 * Run it after LearningCurriculumSeeder, which creates the rows it points at.
 * The files are the only source of these languages (the dashboard edits English
 * and Arabic), so every run simply writes them over what is stored; re-running
 * is safe and is how a corrected translation ships.
 *
 *   php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningTranslationsSeeder" --force
 */
class LearningTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(__DIR__.'/translations/*', GLOB_ONLYDIR) as $dir) {
            $locale = basename($dir);
            if (! in_array($locale, LearningTranslation::LOCALES, true)) {
                $this->command?->warn("Learning translations: skipped {$locale} (not in LearningTranslation::LOCALES)");

                continue;
            }

            $syllabi = 0;
            foreach (glob("{$dir}/curriculum/*.json") as $file) {
                $syllabi += $this->syllabus($locale, basename($file, '.json'), $this->read($file));
            }

            $lessons = 0;
            foreach (glob("{$dir}/lessons/*", GLOB_ONLYDIR) as $courseDir) {
                $ids = LearningLesson::query()
                    ->join('learning_courses', 'learning_courses.id', '=', 'learning_lessons.course_id')
                    ->where('learning_courses.slug', basename($courseDir))
                    ->pluck('learning_lessons.id', 'learning_lessons.slug');

                foreach (glob("{$courseDir}/*.json") as $file) {
                    $slug = basename($file, '.json');
                    if (! isset($ids[$slug])) {
                        $this->command?->warn("Learning translations [{$locale}]: no lesson ".basename($courseDir)."/{$slug}");

                        continue;
                    }
                    $data = $this->read($file);
                    $blocks = LessonBlocks::sanitize($data['blocks'] ?? [], "{$locale}/{$slug}.blocks");
                    $this->put('lesson', $ids[$slug], $locale, [
                        'title' => $data['title'] ?? null,
                        'summary' => $data['summary'] ?? null,
                        'objectives' => array_values($data['objectives'] ?? []),
                    ], $blocks);
                    $lessons++;
                }
            }

            $this->command?->info("Learning translations [{$locale}]: {$syllabi} syllabi, {$lessons} lessons");
        }
    }

    private function syllabus(string $locale, string $courseSlug, array $doc): int
    {
        $course = LearningCourse::query()->with('category', 'stages.units.lessons')->where('slug', $courseSlug)->first();
        if (! $course) {
            $this->command?->warn("Learning translations [{$locale}]: no course {$courseSlug}");

            return 0;
        }

        $this->put('course', $course->id, $locale, $doc['course'] ?? []);
        if ($course->category && ! empty($doc['category'])) {
            $this->put('category', $course->category->id, $locale, $doc['category']);
        }
        foreach ($course->stages as $stage) {
            $this->put('stage', $stage->id, $locale, $doc['stages'][$stage->slug] ?? []);
            foreach ($stage->units as $unit) {
                $this->put('unit', $unit->id, $locale, $doc['units'][$unit->slug] ?? []);
                foreach ($unit->lessons as $lesson) {
                    $this->put('lesson', $lesson->id, $locale, ['title' => $doc['lessons'][$lesson->slug] ?? null]);
                }
            }
        }

        return 1;
    }

    /** Merge non-empty fields into the stored row; a body replaces the stored body. */
    private function put(string $type, int $id, string $locale, array $fields, ?array $blocks = null): void
    {
        $fields = array_filter($fields, fn ($v) => $v !== null && $v !== '' && $v !== []);
        if (! $fields && $blocks === null) {
            return;
        }

        $row = LearningTranslation::firstOrNew(['model_type' => $type, 'model_id' => $id, 'locale' => $locale]);
        $row->fields = array_merge($row->fields ?? [], $fields);
        if ($blocks !== null) {
            $row->blocks = $blocks;
        }
        $row->save();
    }

    private function read(string $file): array
    {
        return json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }
}
