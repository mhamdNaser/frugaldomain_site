<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MetaobjectsRepositoryInterface;
use App\Modules\CMS\Requests\MetaobjectsIndexRequest;
use App\Modules\CMS\Requests\UpdateMetaobjectRequest;
use App\Modules\CMS\Resources\MetaobjectTableResource;

class MetaobjectController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MetaobjectsRepositoryInterface $repo,
    ) {}

    public function index(MetaobjectsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MetaobjectTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MetaobjectTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMetaobjectRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metaobject updated successfully',
            'data' => new MetaobjectTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMetaobjectRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metaobject created successfully',
            'data' => new MetaobjectTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $metaobject = $this->repo->findForFrontend($id);
        $storeId = (string) $metaobject->store_id;
        $entityId = (string) $metaobject->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Metaobject deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
