<?php

namespace App\Modules\Catalog\Controllers\References;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\Interfaces\References\OptionsRepositoryInterface;
use App\Modules\Catalog\Requests\References\OptionsIndexRequest;
use App\Modules\Catalog\Requests\References\UpdateOptionRequest;
use App\Modules\Catalog\Resources\References\OptionTableResource;

class OptionsController extends Controller
{
    public function __construct(
        protected OptionsRepositoryInterface $repo,
    ) {}

    public function index(OptionsIndexRequest $request)
    {
        $data = $request->validated();
        $search = $data['search'] ?? null;
        $rowsPerPage = $data['rowsPerPage'] ?? 10;
        $page = $data['page'] ?? 1;

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => OptionTableResource::collection($result->items()),
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
            'data' => new OptionTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function update(UpdateOptionRequest $request, $id)
    {
        $validated = $request->validated();
        $updated = $this->repo->update((int) $id, $validated);

        return response()->json([
            'message' => 'Option updated successfully',
            'data' => new OptionTableResource($updated),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'store_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'values' => ['nullable', 'array'],
            'values.*.label' => ['required_with:values', 'string', 'max:255'],
            'values.*.value' => ['required_with:values', 'string', 'max:255'],
        ]);

        $created = $this->repo->create($validated);

        return response()->json([
            'message' => 'Option created successfully',
            'data' => new OptionTableResource($created),
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ], 201);
    }

    public function destroy(int $id)
    {
        $validated = request()->validate([]);

        $option = $this->repo->find((int) $id);
        $storeId = (string) $option->store_id;
        $entityId = (string) $option->id;
        $this->repo->delete((int) $id);


        return response()->json([
            'message' => 'Option deleted successfully',
            'meta' => ['outbound_sync_id' => $outboundSyncId],
        ]);
    }
}
