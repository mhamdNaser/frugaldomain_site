<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Resources\DrawingResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The public gallery.
 *
 * Open to anyone, and scoped to published drawings only - the scope is
 * applied here in one place rather than left to each caller to remember.
 */
class DrawingGalleryController extends Controller
{
    private const MAX_PER_PAGE = 60;

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 24), self::MAX_PER_PAGE);

        $query = Drawing::published()->with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Whitelisted, because this value reaches the query builder.
        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'popular' => $query->orderByDesc('use_count')->orderByDesc('published_at'),
            'viewed' => $query->orderByDesc('view_count'),
            default => $query->orderByDesc('published_at'),
        };

        return DrawingResource::collection($query->paginate($perPage));
    }

    /**
     * One published drawing, including the document so it can be placed on a
     * canvas. Reusable drawings are the point of the gallery; a drawing whose
     * author opted out of reuse is still viewable but ships no document.
     */
    public function show(Request $request, string $slug)
    {
        $drawing = Drawing::published()->with('user')->where('slug', $slug)->firstOrFail();

        // A counter bump must not turn a cached GET into a write the caller
        // waits on, so it goes straight to the query builder and skips the
        // model's timestamps.
        Drawing::whereKey($drawing->id)->update(['view_count' => DB::raw('view_count + 1')]);

        return (new DrawingResource($drawing))->withDocument($drawing->isReusable());
    }
}
