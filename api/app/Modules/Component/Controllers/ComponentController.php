<?php

namespace App\Modules\Component\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Component\Repositories\Interfaces\ComponentCategoryRepositoryInterface;
use App\Modules\Component\Repositories\Interfaces\ComponentRepositoryInterface;
use App\Modules\Component\Requests\StoreComponentRequest;
use App\Modules\Component\Resources\ComponentCategoryResource;
use App\Modules\Component\Resources\ComponentResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ComponentController extends Controller
{
    public function __construct(
        protected ComponentRepositoryInterface $repo,
        protected ComponentCategoryRepositoryInterface $categories,
    ) {
    }

    /* ---------------------------------------------------------------- */
    /* Public                                                            */
    /* ---------------------------------------------------------------- */

    /**
     * GET /api/components — the published gallery.
     *
     * The category list travels with the payload so the filter bar can render
     * in the same paint as the cards, instead of popping in afterwards.
     */
    public function publicIndex(Request $request)
    {
        $components = $this->repo->published($this->filters($request));

        return response()->json([
            'categories' => ComponentCategoryResource::collection($this->categories->active())->resolve(),
            'components' => ComponentResource::collection($components)->resolve(),
        ]);
    }

    /** GET /api/components/{slug} — one published component, with its source. */
    public function publicShow(string $slug)
    {
        $component = $this->repo->findBySlug($slug, true);

        return response()->json([
            'component' => ComponentResource::withSource($component)->resolve(),
        ]);
    }

    /** POST /api/components/{slug}/download — counts a download. */
    public function registerDownload(string $slug)
    {
        $component = $this->repo->findBySlug($slug, true);
        $component = $this->repo->registerDownload($component->id);

        return response()->json([
            'downloads' => (int) $component->downloads,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* Admin                                                             */
    /* ---------------------------------------------------------------- */

    /** POST /api/admin/all-components — paginated list for the dashboard. */
    public function index(Request $request)
    {
        $components = $this->repo->all(
            $request->input('search'),
            (int) $request->input('rowsPerPage', 10),
            (int) $request->input('page', 1),
            $this->filters($request),
        );

        return response()->json([
            'data' => ComponentResource::collection($components->items())->resolve(),
            'meta' => [
                'current_page' => $components->currentPage(),
                'last_page' => $components->lastPage(),
                'per_page' => $components->perPage(),
                'total' => $components->total(),
            ],
        ]);
    }

    /** GET /api/admin/components/{id} */
    public function show(int $id)
    {
        return response()->json([
            'data' => ComponentResource::withSource($this->repo->find($id))->resolve(),
        ]);
    }

    /** POST /api/admin/components */
    public function store(StoreComponentRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;

        $component = $this->guard(fn() => $this->repo->create($data));

        return response()->json([
            'message' => 'Component created successfully',
            'data' => (new ComponentResource($component))->resolve(),
        ], 201);
    }

    /** PUT /api/admin/components/{id} */
    public function update(StoreComponentRequest $request, int $id)
    {
        $component = $this->guard(fn() => $this->repo->update($id, $request->validated()));

        return response()->json([
            'message' => 'Component updated successfully',
            'data' => (new ComponentResource($component))->resolve(),
        ]);
    }

    /** DELETE /api/admin/components/{id} */
    public function destroy(int $id)
    {
        $this->repo->delete($id);

        return response()->json(['message' => 'Component deleted successfully']);
    }

    /** PATCH /api/admin/components/{id}/status */
    public function toggleStatus(int $id)
    {
        $component = $this->repo->toggleStatus($id);

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => (new ComponentResource($component))->resolve(),
        ]);
    }

    /* ---------------------------------------------------------------- */

    /** Filters shared by the public gallery and the admin table. */
    private function filters(Request $request): array
    {
        $categories = $request->input('categories', []);

        if (is_string($categories)) {
            $categories = array_filter(explode(',', $categories));
        }

        return [
            'categories' => (array) $categories,
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'tag' => $request->input('tag'),
        ];
    }

    /** Turns a storage guard rejection into a 422 instead of a 500. */
    protected function guard(callable $work)
    {
        try {
            return $work();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['slug' => $e->getMessage()]);
        }
    }
}
