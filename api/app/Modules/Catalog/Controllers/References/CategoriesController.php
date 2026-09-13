<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\CategoriesRepositoryInterface;
use App\Modules\Catalog\Requests\References\CategoriesIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateCategoryRequest;
use App\Modules\Catalog\Resources\References\CategoryTableResource;

class CategoriesController extends Controller
{
    public function __construct(
        protected CategoriesRepositoryInterface $repo,
    ) {}

    public function index(CategoriesIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => CategoryTableResource::collection($result->items()),
            'meta' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => new CategoryTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update((int) $id, $validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryTableResource($updated),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'shopify_category_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
        ]);

        $created = $this->repo->create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $category = $this->repo->find((int) $id);
        $storeId = (string) $category->store_id;
        $entityId = (string) $category->id;
        $this->repo->delete((int) $id);


        return response()->json([
            'message' => 'Category deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
