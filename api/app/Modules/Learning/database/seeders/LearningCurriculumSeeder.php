<?php

namespace App\Modules\Learning\database\seeders;

use App\Modules\Learning\Services\CourseTransfer;
use Illuminate\Database\Seeder;

/**
 * Loads the shipped curriculum.
 *
 *   curriculum/<course>.json        the syllabus: category, course, stages,
 *                                   units and lesson titles (frugal-course@1)
 *   lessons/<course>/<lesson>.json  the content of one lesson: summary,
 *                                   duration, objectives and blocks
 *
 * A lesson with a content file is published; one without is listed in the
 * syllabus as "coming soon". A syllabus entry with `review_of` is a topic the
 * roadmap teaches elsewhere; it becomes a short pointer to that lesson.
 *
 * Runs in "fill" mode, so it is safe to re-run after the dashboard has been
 * used: it adds what is missing and fills lessons that are still empty, and
 * never overwrites a lesson that already has content.
 *
 *   php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningCurriculumSeeder"
 *   LEARNING_SEED_MODE=merge php artisan db:seed --class=...   (re-apply shipped lessons)
 */
class LearningCurriculumSeeder extends Seeder
{
    private array $arabicTitles = [];

    public function run(CourseTransfer $transfer): void
    {
        // LEARNING_SEED_MODE=merge re-applies the shipped files over lessons
        // that already have content (for corrections to shipped lessons).
        // It overwrites dashboard edits to those lessons, so fill is the default.
        $mode = getenv('LEARNING_SEED_MODE') === 'merge' ? 'merge' : 'fill';
        $this->command?->info("Learning curriculum: {$mode} mode");

        $base = __DIR__;

        $documents = [];
        foreach (glob($base.'/curriculum/*.json') as $file) {
            $documents[] = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }

        // Arabic lesson titles across all courses, so a review lesson can
        // name the lesson it points to in Arabic.
        $this->arabicTitles = [];
        foreach ($documents as $doc) {
            foreach ($doc['stages'] as $stage) {
                foreach ($stage['units'] as $unit) {
                    foreach ($unit['lessons'] as $lesson) {
                        if (! empty($lesson['title_ar'])) {
                            $this->arabicTitles[$doc['course']['slug'].'/'.$lesson['slug']] = $lesson['title_ar'];
                        }
                    }
                }
            }
        }

        foreach ($documents as $document) {
            $courseSlug = $document['course']['slug'];

            foreach ($document['stages'] as &$stage) {
                foreach ($stage['units'] as &$unit) {
                    foreach ($unit['lessons'] as &$lesson) {
                        $lesson = $this->withContent($base, $courseSlug, $lesson);
                    }
                }
            }
            unset($stage, $unit, $lesson);

            $course = $transfer->import($document, $mode);
            $this->command?->info("Learning: {$course->slug}");
        }
    }

    private function withContent(string $base, string $courseSlug, array $lesson): array
    {
        $contentFile = "{$base}/lessons/{$courseSlug}/{$lesson['slug']}.json";

        if (is_file($contentFile)) {
            $content = json_decode((string) file_get_contents($contentFile), true, 512, JSON_THROW_ON_ERROR);

            $ar = $content['ar'] ?? [];

            return array_merge($lesson, [
                'summary' => $content['summary'] ?? null,
                'duration_minutes' => $content['duration_minutes'] ?? null,
                'objectives' => $content['objectives'] ?? [],
                'blocks' => $content['blocks'] ?? [],
                'status' => $content['status'] ?? 'published',
                'title_ar' => $ar['title'] ?? ($lesson['title_ar'] ?? null),
                'summary_ar' => $ar['summary'] ?? null,
                'objectives_ar' => $ar['objectives'] ?? [],
                'blocks_ar' => $ar['blocks'] ?? [],
            ]);
        }

        if (! empty($lesson['review_of'])) {
            $ref = $lesson['review_of'];
            $ref['title_ar'] = $this->arabicTitles[$ref['course'].'/'.$ref['lesson']] ?? null;
            $refAvailable = is_file("{$base}/lessons/{$ref['course']}/{$ref['lesson']}.json");

            return array_merge($lesson, [
                'summary' => "A review topic: taught in depth in \"{$ref['title']}\".",
                'duration_minutes' => 5,
                'status' => $refAvailable ? 'published' : 'draft',
                'blocks' => [[
                    'id' => 'review',
                    'type' => 'callout',
                    'variant' => 'info',
                    'title' => 'Review topic',
                    'markdown' => "This topic is taught in depth in **[{$ref['title']}](/learn/{$ref['course']}/{$ref['lesson']})**. "
                        .'Revisit that lesson, make sure you can answer its *Check your understanding* questions, '
                        .'then continue here - the lessons that follow in this stage build on it.',
                ]],
                'summary_ar' => 'موضوع للمراجعة: يُشرح بالتفصيل في درس «'.($ref['title_ar'] ?? $ref['title']).'».',
                'blocks_ar' => [[
                    'id' => 'review',
                    'type' => 'callout',
                    'variant' => 'info',
                    'title' => 'موضوع للمراجعة',
                    'markdown' => 'يُشرح هذا الموضوع بالتفصيل في درس **['.($ref['title_ar'] ?? $ref['title'])."](/learn/{$ref['course']}/{$ref['lesson']})**. "
                        .'راجع ذلك الدرس وتأكّد من قدرتك على الإجابة عن أسئلة «تحقّق من فهمك» فيه، '
                        .'ثم تابع هنا؛ فالدروس التالية في هذه المرحلة تُبنى عليه.',
                ]],
            ]);
        }

        return $lesson;
    }
}
