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

    private function publishedCourses()
    {
        return LearningCourse::query()
            ->where('status', 'published')
            ->whereHas('category', fn ($q) => $q->where('status', 'active'));
    }
}
