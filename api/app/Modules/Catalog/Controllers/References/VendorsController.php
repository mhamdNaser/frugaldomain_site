<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\VendorsRepositoryInterface;
use App\Modules\Catalog\Requests\References\UpdateVendorRequest;
use App\Modules\Catalog\Requests\References\VendorsIndexRequest;
use App\Modules\Catalog\Resources\References\VendorTableResource;

class VendorsController extends Controller
{
    public function __construct(
        protected VendorsRepositoryInterface $repo,
    ) {}

    public function index(VendorsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => VendorTableResource::collection($result->items()),
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
            'data' => new VendorTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateVendorRequest $request, $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update((int) $id, $validated);

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => new VendorTableResource($updated),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'shopify_vendor_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        $created = $this->repo->create($validated);

        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => new VendorTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $vendor = $this->repo->find((int) $id);
        $storeId = (string) $vendor->store_id;
        $entityId = (string) $vendor->id;
        $this->repo->delete((int) $id);


        return response()->json([
            'message' => 'Vendor deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function changeStatus($id)
    {
        $vendor = $this->repo->toggleStatus((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new VendorTableResource($vendor),
        ]);
    }
}
