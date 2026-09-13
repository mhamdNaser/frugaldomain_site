<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Repositories\Interfaces\DiscountsRepositoryInterface;
use App\Modules\Marketing\Requests\DiscountsIndexRequest;
use App\Modules\Marketing\Requests\UpdateDiscountRequest;
use App\Modules\Marketing\Resources\DiscountTableResource;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountsRepositoryInterface $repo,
    ) {}

    public function index(DiscountsIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => DiscountTableResource::collection($result->items()),
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
            'data' => new DiscountTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateDiscountRequest $request, $id)
    {
        $validated = $request->validated();
        $current = $this->repo->find((int) $id);
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount updated successfully',
            'data' => new DiscountTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]);

        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount created successfully',
            'data' => new DiscountTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $discount = $this->repo->find((int) $id);
        $storeId = (string) $discount->store_id;
        $entityId = (string) $discount->id;
        $this->repo->delete((int) $id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Discount deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
