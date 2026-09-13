<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\MenusRepositoryInterface;
use App\Modules\CMS\Requests\MenusIndexRequest;
use App\Modules\CMS\Requests\UpdateMenuRequest;
use App\Modules\CMS\Resources\MenuTableResource;

class MenuController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected MenusRepositoryInterface $repo,
    ) {}

    public function index(MenusIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            MenuTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new MenuTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateMenuRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Menu updated successfully',
            'data' => new MenuTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateMenuRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Menu created successfully',
            'data' => new MenuTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $menu = $this->repo->findForFrontend($id);
        $storeId = (string) $menu->store_id;
        $entityId = (string) $menu->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Menu deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
