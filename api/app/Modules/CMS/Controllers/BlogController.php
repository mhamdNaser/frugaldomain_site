<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\BlogsRepositoryInterface;
use App\Modules\CMS\Requests\BlogsIndexRequest;
use App\Modules\CMS\Requests\UpdateBlogRequest;
use App\Modules\CMS\Resources\BlogTableResource;

class BlogController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected BlogsRepositoryInterface $repo,
    ) {}

    public function index(BlogsIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            BlogTableResource::class,
        );
    }

    public function show(int $id)
    {
        return response()->json([
            'data' => new BlogTableResource($this->repo->findForFrontend($id)),
        ]);
    }

    public function update(UpdateBlogRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Blog updated successfully',
            'data' => new BlogTableResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateBlogRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Blog created successfully',
            'data' => new BlogTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $blog = $this->repo->findForFrontend($id);
        $storeId = (string) $blog->store_id;
        $entityId = (string) $blog->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Blog deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
