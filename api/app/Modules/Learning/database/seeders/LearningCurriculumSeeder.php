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
 */
class LearningCurriculumSeeder extends Seeder
{
    public function run(CourseTransfer $transfer): void
    {
        $base = __DIR__;

        foreach (glob($base.'/curriculum/*.json') as $file) {
            $document = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $courseSlug = $document['course']['slug'];

            foreach ($document['stages'] as &$stage) {
                foreach ($stage['units'] as &$unit) {
                    foreach ($unit['lessons'] as &$lesson) {
                        $lesson = $this->withContent($base, $courseSlug, $lesson);
                    }
                }
            }
            unset($stage, $unit, $lesson);

            $course = $transfer->import($document, 'fill');
            $this->command?->info("Learning: {$course->slug}");
        }
    }

    private function withContent(string $base, string $courseSlug, array $lesson): array
    {
        $contentFile = "{$base}/lessons/{$courseSlug}/{$lesson['slug']}.json";

        if (is_file($contentFile)) {
            $content = json_decode((string) file_get_contents($contentFile), true, 512, JSON_THROW_ON_ERROR);

            return array_merge($lesson, [
                'summary' => $content['summary'] ?? null,
                'duration_minutes' => $content['duration_minutes'] ?? null,
                'objectives' => $content['objectives'] ?? [],
                'blocks' => $content['blocks'] ?? [],
                'status' => $content['status'] ?? 'published',
            ]);
        }

        if (! empty($lesson['review_of'])) {
            $ref = $lesson['review_of'];
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
            ]);
        }

        return $lesson;
    }
}
