<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\PagesRepositoryInterface;
use App\Modules\CMS\Requests\PagesIndexRequest;
use App\Modules\CMS\Requests\UpdatePageRequest;
use App\Modules\CMS\Resources\PageTableResource;

class PageController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected PagesRepositoryInterface $repo,
    ) {}

    public function index(PagesIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            PageTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new PageTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdatePageRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Page updated successfully',
            'data' => new PageTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdatePageRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Page created successfully',
            'data' => new PageTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $page = $this->repo->findForFrontend($id);
        $storeId = (string) $page->store_id;
        $entityId = (string) $page->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Page deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
