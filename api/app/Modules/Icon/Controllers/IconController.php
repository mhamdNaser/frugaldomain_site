<?php

namespace App\Modules\Icon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Icon\Repositories\Eloquent\IconRepository;
use App\Modules\Icon\Requests\StoreIconRequest;
use App\Modules\Icon\Resources\IconResource;
use App\Modules\Icon\Repositories\Interfaces\IconRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IconController extends Controller
{
    protected $repo;

    public function __construct(IconRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => IconResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    /** Hard ceiling on `per_page` so a caller cannot ask for the whole table. */
    private const PUBLIC_MAX_PER_PAGE = 60;

    /** Default page size for the public gallery. */
    private const PUBLIC_DEFAULT_PER_PAGE = 60;

    /** Styles the public endpoints accept as a filter. */
    private const STYLES = ['outline', 'solid'];

    public function allWithoutPagination(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $style = $this->normalizeStyle($request->input('style'));

        // Cache the transformed payload rather than the hydrated models: the
        // resolved array is a few dozen KB, while a serialised collection of
        // every icon (plus relations) exceeds MySQL's max_allowed_packet.
        $cacheKey = sprintf(
            'icons:v%d:list:%s:%s:%s',
            IconRepository::cacheVersion(),
            md5((string) $category),
            md5((string) $search),
            md5((string) $style)
        );

        $payload = Cache::remember($cacheKey, 60, function () use ($search, $category, $style) {
            return IconResource::collection(
                $this->repo->allWithoutPagination($search, $category, $style)
            )->resolve();
        });

        return response()->json($payload);
    }

    /**
     * Public, paginated icon listing — GET /api/icons
     *
     * Query params: page, per_page (capped at 60), search, category, style.
     * Returns { data: IconResource[], meta: { current_page, last_page,
     * total, per_page, from, to } }.
     */
    public function publicIndex(Request $request)
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $style = $this->normalizeStyle($request->query('style'));

        $perPage = (int) $request->query('per_page', self::PUBLIC_DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, self::PUBLIC_MAX_PER_PAGE));

        $page = max(1, (int) $request->query('page', 1));

        // Same caching contract as allWithoutPagination: store the RESOLVED
        // resource arrays (never hydrated models), keyed by every filter plus
        // the page number, and versioned so any icon write busts the lot.
        $cacheKey = sprintf(
            'icons:v%d:page:%s:%s:%s:%d:%d',
            IconRepository::cacheVersion(),
            md5((string) $category),
            md5((string) $search),
            md5((string) $style),
            $perPage,
            $page
        );

        $payload = Cache::remember($cacheKey, 60, function () use ($search, $category, $style, $perPage, $page) {
            $result = $this->repo->paginatePublic($search, $category, $style, $perPage, $page);

            return [
                'data' => IconResource::collection($result['data'])->resolve(),
                'meta' => $result['meta'],
            ];
        });

        return response()->json($payload);
    }

    /**
     * Accept only known styles; anything else means "no style filter" so a
     * stray query string cannot silently return an empty gallery.
     */
    private function normalizeStyle($style): ?string
    {
        $style = is_string($style) ? strtolower(trim($style)) : '';

        return in_array($style, self::STYLES, true) ? $style : null;
    }

    public function store(StoreIconRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $icon = $this->repo->create($data);

        return response()->json(['message' => 'Icon created successfully'], 201);
    }

    public function update(StoreIconRequest $request, $id)
    {
        $data = $request->validated();
        $icon = $this->repo->update($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Icon updated successfully',
            'data' => $icon
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Icon deleted successfully'
        ]);
    }

    public function destroyArray(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        $ids = array_values(array_filter($ids, fn($v) => $v !== null && $v !== ''));
        $deletedCount = $this->repo->deleteArray($ids);

        return response()->json([
            'success' => true,
            'message' => "$deletedCount icons deleted successfully"
        ]);
    }

    public function changeStatus($id)
    {
        $icon = $this->repo->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => $icon
        ]);
    }
}
