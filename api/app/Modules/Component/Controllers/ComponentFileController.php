<?php

namespace App\Modules\Component\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Component\Repositories\Interfaces\ComponentRepositoryInterface;
use App\Modules\Component\Requests\UploadComponentFileRequest;
use App\Modules\Component\Resources\ComponentResource;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ComponentFileController extends Controller
{
    public function __construct(protected ComponentRepositoryInterface $repo)
    {
    }

    /** POST /api/admin/components/{id}/file — the single template file. */
    public function store(UploadComponentFileRequest $request, int $id)
    {
        $component = $this->guard(
            fn() => $this->repo->storeFile($id, $request->file('file')),
        );

        return response()->json([
            'message' => 'File uploaded successfully',
            'data' => (new ComponentResource($component))->resolve(),
        ]);
    }

    /** DELETE /api/admin/components/{id}/file */
    public function destroy(int $id)
    {
        $component = $this->guard(fn() => $this->repo->removeFile($id));

        return response()->json([
            'message' => 'File removed successfully',
            'data' => (new ComponentResource($component))->resolve(),
        ]);
    }

    /** A rejected upload is the caller's mistake, so answer 422 not 500. */
    protected function guard(callable $work)
    {
        try {
            return $work();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }
    }
}
