<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CMS\Controllers\Concerns\RespondsWithCmsPaginator;
use App\Modules\CMS\Repositories\Interfaces\ArticlesRepositoryInterface;
use App\Modules\CMS\Requests\ArticlesIndexRequest;
use App\Modules\CMS\Requests\ArticleShowRequest;
use App\Modules\CMS\Requests\UpdateArticleRequest;
use App\Modules\CMS\Resources\ArticleDetailResource;
use App\Modules\CMS\Resources\ArticleTableResource;

class ArticleController extends Controller
{
    use RespondsWithCmsPaginator;

    public function __construct(
        protected ArticlesRepositoryInterface $repo,
    ) {}

    public function index(ArticlesIndexRequest $request)
    {
        $data = $request->validated();

        return $this->paginatedResponse(
            $this->repo->all($data['search'] ?? null, $data['rowsPerPage'] ?? 10, $data['page'] ?? 1, $this->requestFilters($data)),
            ArticleTableResource::class,
        );
    }

    public function show(ArticleShowRequest $request, int $id)
    {
        $data = $request->validated();

        return response()->json([
            'data' => new ArticleDetailResource($this->repo->findForFrontend((int) ($data['id'] ?? $id))),
        ]);
    }

    public function update(UpdateArticleRequest $request, int $id)
    {
        $validated = $request->validated();
        $current = $this->repo->findForFrontend($id);
        $updated = $this->repo->update($id, $validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Article updated successfully',
            'data' => new ArticleDetailResource($updated),
            'meta' => [
                'outbound_sync_id' => $outboundSyncId,
            ],
        ]);
    }

    public function store(UpdateArticleRequest $request)
    {
        $validated = $request->validated();
        $created = $this->repo->create($validated);
        $outboundSyncId = null;

        return response()->json([
            'message' => 'Article created successfully',
            'data' => new ArticleDetailResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $article = $this->repo->findForFrontend($id);
        $storeId = (string) $article->store_id;
        $entityId = (string) $article->id;
        $this->repo->delete($id);

        $outboundSyncId = null;

        return response()->json([
            'message' => 'Article deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
