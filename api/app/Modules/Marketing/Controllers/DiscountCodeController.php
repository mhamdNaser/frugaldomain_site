<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountCodesRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountCodesIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountCodeRequest;
use App\Modules\Marketing\Resources\DiscountCodeTableResource;

class DiscountCodeController extends Controller
{
    public function __construct(
        protected DiscountCodesRepositoryInterface $repo,
    ) {}

    public function index(DiscountCodesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
            $data['discount_id'] ?? null,
        );

        return response()->json([
            'data' => DiscountCodeTableResource::collection($result->items()),
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
            'data' => new DiscountCodeTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountCodeRequest $request, $id)
    {
        $validated = $request->validated();
        $current = $this->repo->find((int) $id);
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount code updated successfully',
            'data' => new DiscountCodeTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'discount_id' => ['nullable', 'integer'],
            'code' => ['required', 'string', 'max:255'],
        ]);

        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount code created successfully',
            'data' => new DiscountCodeTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $code = $this->repo->find((int) $id);
        $storeId = (string) $code->store_id;
        $entityId = (string) $code->id;
        $this->repo->delete((int) $id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount code deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
