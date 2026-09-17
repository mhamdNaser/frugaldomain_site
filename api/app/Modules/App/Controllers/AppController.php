<?php

namespace App\Modules\App\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\App\Repositories\Interfaces\AppRepositoryInterface;
use App\Modules\App\Requests\StoreAppRequest;
use App\Modules\App\Resources\AppResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AppController extends Controller
{
    protected $repo;

    public function __construct(AppRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    // ---------------------------------------------------------------- public

    /**
     * GET /api/apps — the public catalogue.
     *
     * Replaces the static public/json/apps.json. The `hosting` block is kept
     * in the envelope so the front-end hook can hand the same shape to the
     * gallery and detail pages it always has.
     */
    public function publicIndex()
    {
        $apps = $this->repo->published();

        return response()->json([
            'hosting' => $this->hosting(),
            'apps' => AppResource::collection($apps)->resolve(),
        ]);
    }

    /** GET /api/apps/{slug} — one published app. */
    public function publicShow(string $slug)
    {
        $app = $this->repo->findBySlug($slug, true);

        return response()->json([
            'hosting' => $this->hosting(),
            'app' => (new AppResource($app))->resolve(),
        ]);
    }

    // ----------------------------------------------------------------- admin

    /** POST /api/admin/apps/all — paginated admin listing (drafts included). */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rowsPerPage', 10);
        $page = $request->input('page', 1);

        $result = $this->repo->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => AppResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    /** GET /api/admin/apps/{id} */
    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => new AppResource($this->repo->find((int) $id)),
        ]);
    }

    public function store(StoreAppRequest $request)
    {
        $app = $this->repo->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'App created successfully',
            'data' => new AppResource($app),
        ], 201);
    }

    public function update(StoreAppRequest $request, $id)
    {
        try {
            $app = $this->repo->update((int) $id, $request->validated());
        } catch (InvalidArgumentException $e) {
            // Thrown by the storage guard when a slug would escape public/apps.
            throw ValidationException::withMessages(['slug' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'App updated successfully',
            'data' => new AppResource($app),
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'App deleted successfully',
        ]);
    }

    public function changeStatus($id)
    {
        $app = $this->repo->toggleStatus((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Status changed successfully',
            'data' => new AppResource($app),
        ]);
    }

    /**
     * Hosting defaults, previously the `hosting` object of apps.json. Derived
     * from config so a deploy does not need a code change.
     */
    protected function hosting(): array
    {
        return [
            // APPS_BASE_URL lets the public site live on a different host from
            // the API; it falls back to APP_URL when unset.
            'baseUrl' => rtrim((string) env('APPS_BASE_URL', config('app.url')), '/'),
            'appsPath' => '/apps',
            'serverRoot' => 'public_html/apps',
        ];
    }
}
