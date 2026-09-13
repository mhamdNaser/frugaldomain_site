<?php

namespace App\Modules\Shipping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Repositories\Interfaces\ShippingZonesRepositoryInterface;
use App\Modules\Shipping\Requests\ShippingZonesIndexRequest;
use App\Modules\Shipping\Requests\UpdateShippingZoneRequest;
use App\Modules\Shipping\Resources\ShippingZoneDetailsResource;
use App\Modules\Shipping\Resources\ShippingZoneTableResource;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    public function __construct(
        protected ShippingZonesRepositoryInterface $repo,
    ) {}

    public function index(ShippingZonesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => ShippingZoneTableResource::collection($result->items()),
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
            'data' => new ShippingZoneDetailsResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateShippingZoneRequest $request, $id)
    {
        $validated = $request->validated();
        $current = $this->repo->find((int) $id);
        $updated = $this->repo->update((int) $id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Shipping zone updated successfully',
            'data' => new ShippingZoneDetailsResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'shopify_zone_id' => ['nullable', 'string', 'max:255'],
            'shopify_profile_id' => ['nullable', 'string', 'max:255'],
            'countries' => ['nullable'],
        ]);

        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Shipping zone created successfully',
            'data' => new ShippingZoneDetailsResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $zone = $this->repo->find((int) $id);
        $storeId = (string) $zone->store_id;
        $entityId = (string) $zone->id;
        $this->repo->delete((int) $id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Shipping zone deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
