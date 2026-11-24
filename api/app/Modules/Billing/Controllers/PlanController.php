<?php

namespace App\Modules\Billing\Controllers;

use App\Modules\Stores\Models\Store;
use App\Http\Controllers\Controller;
use App\Modules\Billing\Repositories\Interfaces\PlansRepositoryInterface;
use App\Modules\Billing\Requests\PlansIndexRequest;
use App\Modules\Billing\Requests\UpdatePlanRequest;
use App\Modules\Billing\Resources\PlanTableResource;

class PlanController extends Controller
{
    public function __construct(
        protected PlansRepositoryInterface $repo
    ) {}

    public function index(PlansIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 10,
            $data['page'] ?? 1,
        );

        return response()->json([
            'data' => PlanTableResource::collection($result->items()),
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
            'data' => new PlanTableResource($this->repo->findForFrontend((int) $id)),
        ]);
    }

    public function store(UpdatePlanRequest $request)
    {
        return response()->json([
            'message' => 'Plan created successfully',
            'data' => new PlanTableResource($this->repo->create($request->validated())),
        ], 201);
    }

    /** A plan stores are subscribed to cannot be deleted; deactivate it instead. */
    public function destroy($id)
    {
        $plan = $this->repo->find((int) $id);

        abort_if(
            $plan->subscriptions()->exists() || Store::query()->where('plan_id', $plan->id)->exists(),
            422,
            'Stores are using this plan. Deactivate it instead of deleting it.',
        );

        $this->repo->delete((int) $id);

        return response()->json(['message' => 'Plan deleted successfully']);
    }

    public function update(UpdatePlanRequest $request, $id)
    {
        return response()->json([
            'message' => 'Plan updated successfully',
            'data' => new PlanTableResource($this->repo->update((int) $id, $request->validated())),
        ]);
    }

    public function changeStatus($id)
    {
        $plan = $this->repo->toggleStatus((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new PlanTableResource($plan),
        ]);
    }
}
