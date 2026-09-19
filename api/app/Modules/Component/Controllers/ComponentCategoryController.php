<?php

namespace App\Modules\Component\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Component\Repositories\Interfaces\ComponentCategoryRepositoryInterface;
use App\Modules\Component\Requests\StoreComponentCategoryRequest;
use App\Modules\Component\Resources\ComponentCategoryResource;
use Illuminate\Http\Request;

class ComponentCategoryController extends Controller
{
    public function __construct(protected ComponentCategoryRepositoryInterface $repo)
    {
    }

    /** GET /api/component-categories — public filter bar. */
    public function publicIndex()
    {
        return response()->json([
            'data' => ComponentCategoryResource::collection($this->repo->active())->resolve(),
        ]);
    }

    /** POST /api/admin/all-component-categories */
    public function index(Request $request)
    {
        $categories = $this->repo->all(
            $request->input('search'),
            (int) $request->input('rowsPerPage', 50),
            (int) $request->input('page', 1),
        );

        return response()->json([
            'data' => ComponentCategoryResource::collection($categories->items())->resolve(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /** POST /api/admin/component-categories */
    public function store(StoreComponentCategoryRequest $request)
    {
        $category = $this->repo->create($request->validated());

        return response()->json([
            'message' => 'Category created successfully',
            'data' => (new ComponentCategoryResource($category))->resolve(),
        ], 201);
    }

    /** PUT /api/admin/component-categories/{id} */
    public function update(StoreComponentCategoryRequest $request, int $id)
    {
        $category = $this->repo->update($id, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => (new ComponentCategoryResource($category))->resolve(),
        ]);
    }

    /** DELETE /api/admin/component-categories/{id} */
    public function destroy(int $id)
    {
        $this->repo->delete($id);

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
