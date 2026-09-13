<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\ProductTypesRepositoryInterface;
use App\Modules\Catalog\Requests\References\ProductTypesIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateProductTypeRequest;
use App\Modules\Catalog\Resources\References\ProductTypeTableResource;

class ProductTypesController extends Controller
{
    public function __construct(
        protected ProductTypesRepositoryInterface $repo,
    ) {}

    public function index(ProductTypesIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => ProductTypeTableResource::collection($result->items()),
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
            'data' => new ProductTypeTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateProductTypeRequest $request, $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update((int) $id, $validated);

        return response()->json([
            'message' => 'Product type updated successfully',
            'data' => new ProductTypeTableResource($updated),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'shopify_product_type_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $created = $this->repo->create($validated);

        return response()->json([
            'message' => 'Product type created successfully',
            'data' => new ProductTypeTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $productType = $this->repo->find((int) $id);
        $storeId = (string) $productType->store_id;
        $entityId = (string) $productType->id;
        $this->repo->delete((int) $id);


        return response()->json([
            'message' => 'Product type deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
