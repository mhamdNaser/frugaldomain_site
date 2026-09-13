<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetafieldsRepositoryInterface;
use App\Modules\CMS\Requests\MetafieldsIndexRequest;
use App\Modules\CMS\Requests\UpdateMetafieldRequest;
use App\Modules\CMS\Resources\MetafieldTableResource;

class MetafieldController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MetafieldsRepositoryInterface $repo,
    ) {}

    public function index(MetafieldsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetafieldTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MetafieldTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMetafieldRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metafield updated successfully',
            'data' => new MetafieldTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMetafieldRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metafield created successfully',
            'data' => new MetafieldTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $metafield = $this->repo->findForFrontend($id);
        $storeId = (string) $metafield->store_id;
        $entityId = (string) $metafield->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metafield deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
