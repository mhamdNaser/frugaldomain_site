<?php

namespace App\Modules\Learning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Learning\Models\LearningCategory;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Models\LearningMedia;
use App\Modules\Learning\Models\LearningStage;
use App\Modules\Learning\Models\LearningUnit;
use App\Modules\Learning\Services\CourseTransfer;
use App\Modules\Learning\Services\LearningPresenter;
use App\Modules\Learning\Services\LessonBlocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The dashboard's course builder: categories, courses and the
 * stage → unit → lesson tree, plus media uploads and JSON import/export.
 */
class LearningAdminController extends Controller
{
    private const SLUG = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private LearningPresenter $presenter,
        private CourseTransfer $transfer
    ) {
    }

    /* ------------------------------------------------------------ categories */

    public function categories()
    {
        $categories = LearningCategory::query()
            ->withCount('courses')
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn ($c) => $this->presenter->category($c, true))->values(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $category = LearningCategory::create($this->categoryData($request));

        return response()->json(['data' => $this->presenter->category($category, true)], 201);
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = LearningCategory::findOrFail($id);
        $category->update($this->categoryData($request, $category->id));

        return response()->json(['data' => $this->presenter->category($category->loadCount('courses'), true)]);
    }

    public function destroyCategory(int $id)
    {
        $category = LearningCategory::withCount('courses')->findOrFail($id);

        if ($category->courses_count > 0) {
            throw ValidationException::withMessages([
                'category' => ['Move or delete the courses in this category before deleting it.'],
            ]);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }

    private function categoryData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:120', self::SLUG, Rule::unique('learning_categories', 'slug')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'ordering' => ['nullable', 'integer', 'min:0'],
        ]) + ['icon' => 'book', 'color' => '#0ea5e9'];
    }

    /* --------------------------------------------------------------- courses */

    public function courses(Request $request)
    {
        $courses = LearningCourse::query()
            ->with('category')
            ->withCount([
                'stages',
                'lessons',
                'lessons as published_lessons_count' => fn ($q) => $q->where('status', 'published'),
            ])
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('ordering')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => $courses->map(fn ($c) => $this->presenter->courseCard($c, true))->values(),
        ]);
    }

    public function course(int $id)
    {
        return response()->json([
            'data' => $this->presenter->courseDetail(LearningCourse::findOrFail($id), true),
        ]);
    }

    public function storeCourse(Request $request)
    {
        $course = LearningCourse::create($this->courseData($request));

        return response()->json(['data' => $this->presenter->courseDetail($course, true)], 201);
    }

    public function updateCourse(Request $request, int $id)
    {
        $course = LearningCourse::findOrFail($id);
        $course->update($this->courseData($request, $course->id));

        return response()->json(['data' => $this->presenter->courseDetail($course->fresh(), true)]);
    }

    public function destroyCourse(int $id)
    {
        LearningCourse::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function uploadCover(Request $request, int $id)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $course = LearningCourse::findOrFail($id);
        $course->update(['cover_path' => $request->file('image')->store('learning/covers', 'local')]);

        return response()->json(['data' => $this->presenter->courseDetail($course->fresh(), true)]);
    }

    public function deleteCover(int $id)
    {
        $course = LearningCourse::findOrFail($id);
        $course->update(['cover_path' => null]);

        return response()->json(['data' => $this->presenter->courseDetail($course->fresh(), true)]);
    }

    public function export(int $id)
    {
        $course = LearningCourse::findOrFail($id);

        return response()->json($this->transfer->export($course), 200, [
            'Content-Disposition' => 'attachment; filename="'.$course->slug.'.course.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function import(Request $request)
    {
        $request->validate([
            'mode' => ['required', Rule::in(CourseTransfer::MODES)],
            'file' => ['nullable', 'file', 'max:20480'],
            'document' => ['required_without:file', 'nullable'],
        ]);

        $document = $request->hasFile('file')
            ? json_decode((string) file_get_contents($request->file('file')->getRealPath()), true)
            : $request->input('document');

        if (is_string($document)) {
            $document = json_decode($document, true);
        }

        if (! is_array($document)) {
            throw ValidationException::withMessages(['document' => ['The file is not valid JSON.']]);
        }

        $course = $this->transfer->import($document, $request->input('mode'));

        return response()->json(['data' => $this->presenter->courseDetail($course, true)], 201);
    }

    private function courseData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:learning_categories,id'],
            'slug' => ['required', 'string', 'max:120', self::SLUG, Rule::unique('learning_courses', 'slug')->ignore($ignoreId)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:100000'],
            'level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
            'language' => ['required', Rule::in(['en', 'ar'])],
            'estimated_hours' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'outcomes' => ['nullable', 'array', 'max:40'],
            'outcomes.*' => ['string', 'max:500'],
            'prerequisites' => ['nullable', 'array', 'max:40'],
            'prerequisites.*' => ['string', 'max:500'],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:60'],
            'accent_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'ordering' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach (['outcomes', 'prerequisites', 'tags'] as $list) {
            $data[$list] = array_values(array_filter(array_map('trim', $data[$list] ?? []), 'strlen'));
        }
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['ordering'] = (int) ($data['ordering'] ?? 0);

        return $data;
    }

    /* ---------------------------------------------------------------- stages */

    public function storeStage(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:learning_courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ]);

        $stage = LearningStage::create($data + [
            'slug' => $this->uniqueSlug(LearningStage::class, $data['course_id'], $data['title']),
            'ordering' => (int) LearningStage::where('course_id', $data['course_id'])->max('ordering') + 1,
        ]);

        return $this->courseResponse($stage->course_id, 201);
    }

    public function updateStage(Request $request, int $id)
    {
        $stage = LearningStage::findOrFail($id);
        $stage->update($this->withoutEmptySlug($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'slug' => ['nullable', 'string', 'max:120', self::SLUG,
                Rule::unique('learning_stages', 'slug')->where('course_id', $stage->course_id)->ignore($stage->id)],
        ])));

        return $this->courseResponse($stage->course_id);
    }

    public function destroyStage(int $id)
    {
        $stage = LearningStage::findOrFail($id);
        $courseId = $stage->course_id;
        $stage->delete();

        return $this->courseResponse($courseId);
    }

    /* ----------------------------------------------------------------- units */

    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:learning_stages,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ]);
        $stage = LearningStage::findOrFail($data['stage_id']);

        LearningUnit::create($data + [
            'course_id' => $stage->course_id,
            'slug' => $this->uniqueSlug(LearningUnit::class, $stage->course_id, $data['title']),
            'ordering' => (int) LearningUnit::where('stage_id', $stage->id)->max('ordering') + 1,
        ]);

        return $this->courseResponse($stage->course_id, 201);
    }

    public function updateUnit(Request $request, int $id)
    {
        $unit = LearningUnit::findOrFail($id);
        $unit->update($this->withoutEmptySlug($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'slug' => ['nullable', 'string', 'max:120', self::SLUG,
                Rule::unique('learning_units', 'slug')->where('course_id', $unit->course_id)->ignore($unit->id)],
        ])));

        return $this->courseResponse($unit->course_id);
    }

    public function destroyUnit(int $id)
    {
        $unit = LearningUnit::findOrFail($id);
        $courseId = $unit->course_id;
        $unit->delete();

        return $this->courseResponse($courseId);
    }

    /* --------------------------------------------------------------- lessons */

    public function lesson(int $id)
    {
        $lesson = LearningLesson::findOrFail($id);

        return response()->json(['data' => $this->presenter->lesson($lesson, $lesson->course, true)]);
    }

    public function storeLesson(Request $request)
    {
        $request->validate([
            'unit_id' => ['required', 'integer', 'exists:learning_units,id'],
            'title' => ['required', 'string', 'max:255'],
        ]);
        $unit = LearningUnit::findOrFail($request->integer('unit_id'));
        $data = $this->lessonData($request, $unit->course_id);

        $lesson = LearningLesson::create($data + [
            'unit_id' => $unit->id,
            'course_id' => $unit->course_id,
            'slug' => $data['slug'] ?? $this->uniqueSlug(LearningLesson::class, $unit->course_id, $data['title']),
            'ordering' => (int) LearningLesson::where('unit_id', $unit->id)->max('ordering') + 1,
        ]);

        return response()->json(['data' => $this->presenter->lesson($lesson, $lesson->course, true)], 201);
    }

    public function updateLesson(Request $request, int $id)
    {
        $lesson = LearningLesson::findOrFail($id);
        $data = $this->lessonData($request, $lesson->course_id, $lesson->id);

        if (isset($data['unit_id'])) {
            $unit = LearningUnit::findOrFail($data['unit_id']);
            if ($unit->course_id !== $lesson->course_id) {
                throw ValidationException::withMessages(['unit_id' => ['A lesson can only move within its course.']]);
            }
        }

        $lesson->update($data);

        return response()->json(['data' => $this->presenter->lesson($lesson->fresh(), $lesson->course, true)]);
    }

    public function duplicateLesson(int $id)
    {
        $lesson = LearningLesson::findOrFail($id);
        $copy = $lesson->replicate();
        $copy->title = $lesson->title.' (copy)';
        $copy->slug = $this->uniqueSlug(LearningLesson::class, $lesson->course_id, $lesson->slug.'-copy');
        $copy->status = 'draft';
        $copy->ordering = $lesson->ordering + 1;

        DB::transaction(function () use ($lesson, $copy) {
            LearningLesson::where('unit_id', $lesson->unit_id)
                ->where('ordering', '>', $lesson->ordering)
                ->increment('ordering');
            $copy->save();
        });

        return response()->json(['data' => $this->presenter->lesson($copy, $copy->course, true)], 201);
    }

    public function destroyLesson(int $id)
    {
        $lesson = LearningLesson::findOrFail($id);
        $courseId = $lesson->course_id;
        $lesson->delete();

        return $this->courseResponse($courseId);
    }

    private function lessonData(Request $request, int $courseId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'unit_id' => ['sometimes', 'integer', 'exists:learning_units,id'],
            'slug' => ['nullable', 'string', 'max:120', self::SLUG,
                Rule::unique('learning_lessons', 'slug')->where('course_id', $courseId)->ignore($ignoreId)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'objectives' => ['nullable', 'array', 'max:30'],
            'objectives.*' => ['string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'blocks' => ['nullable', 'array', 'max:400'],
        ]);

        if ($request->has('blocks')) {
            $data['blocks'] = LessonBlocks::sanitize($request->input('blocks', []));
        }
        if (array_key_exists('objectives', $data)) {
            $data['objectives'] = array_values(array_filter(array_map('trim', $data['objectives'] ?? []), 'strlen'));
        }
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        return $data;
    }

    /* --------------------------------------------------------------- reorder */

    /**
     * Save a new order - and, optionally, a new parent - for a list of
     * siblings. Used by drag-and-drop and the up/down buttons alike.
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['stages', 'units', 'lessons'])],
            'parent_id' => ['required', 'integer'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        [$model, $parentKey, $parentModel] = match ($data['type']) {
            'stages' => [LearningStage::class, 'course_id', LearningCourse::class],
            'units' => [LearningUnit::class, 'stage_id', LearningStage::class],
            'lessons' => [LearningLesson::class, 'unit_id', LearningUnit::class],
        };

        $parent = $parentModel::findOrFail($data['parent_id']);
        $courseId = $parent instanceof LearningCourse ? $parent->id : $parent->course_id;

        $items = $model::query()->whereIn('id', $data['ids'])->get()->keyBy('id');
        if ($items->count() !== count(array_unique($data['ids']))
            || $items->contains(fn ($item) => $item->course_id !== $courseId)) {
            throw ValidationException::withMessages(['ids' => ['Items can only be ordered within their own course.']]);
        }

        DB::transaction(function () use ($data, $items, $parentKey, $parent) {
            foreach (array_values($data['ids']) as $index => $id) {
                $items[$id]->forceFill([$parentKey => $parent->id, 'ordering' => $index + 1])->save();
            }
        });

        return $this->courseResponse($courseId);
    }

    /* ----------------------------------------------------------------- media */

    public function media(Request $request)
    {
        $media = LearningMedia::query()
            ->when($request->query('course_id'), fn ($q, $id) => $q->where('course_id', $id))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json(['data' => $media->map->toPublicArray()->values()]);
    }

    public function uploadMedia(Request $request)
    {
        $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:learning_courses,id'],
            'alt' => ['nullable', 'string', 'max:500'],
            // SVG is left out on purpose: served from the API's origin, a
            // script inside one would run with that origin's privileges.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:102400'],
        ]);

        $file = $request->file('file');
        $mime = (string) $file->getMimeType();
        $kind = str_starts_with($mime, 'video/') ? 'video' : 'image';

        if ($kind === 'image' && $file->getSize() > 8 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => ['Images must be 8 MB or smaller.']]);
        }

        $media = LearningMedia::create([
            'course_id' => $request->input('course_id'),
            'kind' => $kind,
            'path' => $file->store('learning/media', 'local'),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'alt' => $request->input('alt'),
        ]);

        return response()->json(['data' => $media->toPublicArray()], 201);
    }

    public function destroyMedia(int $id)
    {
        LearningMedia::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /* --------------------------------------------------------------- helpers */

    private function courseResponse(int $courseId, int $status = 200)
    {
        return response()->json([
            'data' => $this->presenter->courseDetail(LearningCourse::findOrFail($courseId), true),
        ], $status);
    }

    private function withoutEmptySlug(array $data): array
    {
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        return $data;
    }

    private function uniqueSlug(string $model, int $courseId, string $value): string
    {
        $base = substr(Str::slug($value) ?: 'item', 0, 110);
        $slug = $base;
        $n = 2;
        while ($model::where('course_id', $courseId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
