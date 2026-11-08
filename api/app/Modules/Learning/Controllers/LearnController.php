<?php

namespace App\Modules\Learning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Learning\Models\LearningCategory;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningMedia;
use App\Modules\Learning\Services\LearningPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The public side of the learning platform. Only active categories and
 * published courses are visible; a draft lesson appears in a syllabus as a
 * locked title ("coming soon") but its content is never served.
 */
class LearnController extends Controller
{
    public function __construct(private LearningPresenter $presenter)
    {
    }

    public function categories()
    {
        $categories = LearningCategory::query()
            ->where('status', 'active')
            ->withCount(['courses' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn ($category) => $this->presenter->category($category))->values(),
        ]);
    }

    public function courses(Request $request)
    {
        $courses = $this->publishedCourses()
            ->with('category')
            ->withCount([
                'stages',
                'lessons',
                'lessons as published_lessons_count' => fn ($q) => $q->where('status', 'published'),
            ])
            ->when($request->query('category'), function ($query, $slug) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
            })
            ->orderByDesc('is_featured')
            ->orderBy('ordering')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => $courses->map(fn ($course) => $this->presenter->courseCard($course))->values(),
        ]);
    }

    public function course(string $slug)
    {
        $course = $this->publishedCourses()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->presenter->courseDetail($course)]);
    }

    public function lesson(string $slug, string $lessonSlug)
    {
        $course = $this->publishedCourses()->where('slug', $slug)->firstOrFail();
        $lesson = LearningLesson::query()
            ->where('course_id', $course->id)
            ->where('slug', $lessonSlug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json(['data' => $this->presenter->lesson($lesson, $course)]);
    }

    public function cover(string $slug)
    {
        $course = $this->publishedCourses()->where('slug', $slug)->firstOrFail();

        abort_unless($course->cover_path && Storage::disk('local')->exists($course->cover_path), 404);

        return response()->file(Storage::disk('local')->path($course->cover_path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * An uploaded image or video. Served by id; the extension in the url is
     * cosmetic. Range requests are honoured so long videos can be seeked.
     */
    public function media(string $file)
    {
        $id = (int) pathinfo($file, PATHINFO_FILENAME);
        $media = LearningMedia::findOrFail($id);

        abort_unless(Storage::disk('local')->exists($media->path), 404);

        return response()->file(Storage::disk('local')->path($media->path), [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * A sitemap of every public course and written lesson, in both languages,
     * with the hreflang pairs. It is built from the database, so a new batch
     * of lessons is listed as soon as the seeder runs, with no frontend
     * build. robots.txt on the main site points crawlers here; a sitemap on
     * another host is valid when robots.txt submits it.
     *
     * Review topics (a single pointer to the lesson that teaches them) are
     * left out: they are thin by design and the page itself is noindex.
     */
    public function sitemap()
    {
        $xml = cache()->remember('learn:sitemap', now()->addHour(), function () {
            // The public site, as in the frontend's SITE_ORIGIN. Not taken from
            // FRONTEND_URL, which is a localhost address in development.
            $origin = 'https://frugaldomain.site';

            $courses = $this->publishedCourses()->get(['id', 'slug', 'updated_at']);
            $lessons = LearningLesson::query()
                ->whereIn('course_id', $courses->pluck('id'))
                ->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('summary')->orWhere('summary', 'not like', 'A review topic:%'))
                ->get(['course_id', 'slug', 'summary_ar', 'updated_at']);
            $slugOf = $courses->pluck('slug', 'id');

            $entries = [];
            foreach ($courses as $course) {
                $entries[] = ["/learn/{$course->slug}", $course->updated_at, true, '0.8'];
            }
            foreach ($lessons as $lesson) {
                $entries[] = [
                    "/learn/{$slugOf[$lesson->course_id]}/{$lesson->slug}",
                    $lesson->updated_at,
                    filled($lesson->summary_ar),
                    '0.7',
                ];
            }

            $e = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
            foreach ($entries as [$path, $updated, $bilingual, $priority]) {
                $en = $origin.$path;
                $ar = $origin.'/ar'.$path;
                $links = $bilingual
                    ? '<xhtml:link rel="alternate" hreflang="en" href="'.$e($en).'"/>'
                        .'<xhtml:link rel="alternate" hreflang="ar" href="'.$e($ar).'"/>'
                        .'<xhtml:link rel="alternate" hreflang="x-default" href="'.$e($en).'"/>'
                    : '';
                $lastmod = $updated ? '<lastmod>'.$updated->toDateString().'</lastmod>' : '';
                foreach ($bilingual ? [$en, $ar] : [$en] as $loc) {
                    $out .= '<url><loc>'.$e($loc).'</loc>'.$lastmod.'<priority>'.$priority.'</priority>'.$links.'</url>'."\n";
                }
            }

            return $out.'</urlset>'."\n";
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function publishedCourses()
    {
        return LearningCourse::query()
            ->where('status', 'published')
            ->whereHas('category', fn ($q) => $q->where('status', 'active'));
    }
}
