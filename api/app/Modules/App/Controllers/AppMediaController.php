<?php

namespace App\Modules\App\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\App\Repositories\Interfaces\AppMediaRepositoryInterface;
use App\Modules\App\Requests\UploadAppArchiveRequest;
use App\Modules\App\Requests\UploadAppImageRequest;
use App\Modules\App\Resources\AppResource;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AppMediaController extends Controller
{
    protected $repo;

    public function __construct(AppMediaRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /** POST /api/admin/apps/{id}/archive — the project's .zip build. */
    public function uploadArchive(UploadAppArchiveRequest $request, $id)
    {
        $app = $this->guard(fn() => $this->repo->storeArchive((int) $id, $request->file('archive')));

        return response()->json([
            'success' => true,
            'message' => 'Project files uploaded successfully',
            'data' => new AppResource($app),
        ], 201);
    }

    /** DELETE /api/admin/apps/{id}/archive */
    public function destroyArchive($id)
    {
        $app = $this->guard(fn() => $this->repo->deleteArchive((int) $id));

        return response()->json([
            'success' => true,
            'message' => 'Project files removed',
            'data' => new AppResource($app),
        ]);
    }

    /** POST /api/admin/apps/{id}/images — one main or one secondary shot. */
    public function uploadImage(UploadAppImageRequest $request, $id)
    {
        $data = $request->validated();

        $result = $this->guard(fn() => $this->repo->storeImage(
            (int) $id,
            $request->file('image'),
            $data['role'],
            array_key_exists('ordering', $data) ? $data['ordering'] : null,
            $data['alt'] ?? null
        ));

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => new AppResource($result['app']),
        ], 201);
    }

    /** DELETE /api/admin/apps/{id}/images/{imageId} */
    public function destroyImage($id, $imageId)
    {
        $app = $this->guard(fn() => $this->repo->deleteImage((int) $id, (int) $imageId));

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
            'data' => new AppResource($app),
        ]);
    }

    /**
     * Turns a rejected slug (the path-traversal guard) into a 422 rather than
     * letting an InvalidArgumentException surface as a 500.
     */
    protected function guard(callable $work)
    {
        try {
            return $work();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['slug' => $e->getMessage()]);
        }
    }
}
