<?php

namespace App\Modules\DesktopSoftware\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DesktopSoftware\Models\DesktopSoftware;
use App\Modules\DesktopSoftware\Requests\StoreDesktopSoftwareRequest;
use App\Modules\DesktopSoftware\Requests\UploadDesktopSoftwareFileRequest;
use App\Modules\DesktopSoftware\Resources\DesktopSoftwareResource;
use Illuminate\Support\Facades\Storage;

class DesktopSoftwareController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'data' => DesktopSoftwareResource::collection(
                DesktopSoftware::query()->orderBy('ordering')->orderBy('name')->get()
            )->resolve(),
        ]);
    }

    public function store(StoreDesktopSoftwareRequest $request)
    {
        $software = DesktopSoftware::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new DesktopSoftwareResource($software))->resolve(),
        ], 201);
    }

    public function update(StoreDesktopSoftwareRequest $request, int $id)
    {
        $software = DesktopSoftware::findOrFail($id);
        $software->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new DesktopSoftwareResource($software->fresh()))->resolve(),
        ]);
    }

    public function destroy(int $id)
    {
        DesktopSoftware::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function uploadFile(UploadDesktopSoftwareFileRequest $request, int $id)
    {
        $software = DesktopSoftware::findOrFail($id);
        $software->file_path = $request->file('file')->store('desktop-software', 'local');
        $software->save();

        return response()->json([
            'success' => true,
            'data' => (new DesktopSoftwareResource($software->fresh()))->resolve(),
        ]);
    }

    public function deleteFile(int $id)
    {
        $software = DesktopSoftware::findOrFail($id);

        if ($software->file_path) {
            Storage::disk('local')->delete($software->file_path);
            $software->file_path = null;
            $software->save();
        }

        return response()->json([
            'success' => true,
            'data' => (new DesktopSoftwareResource($software->fresh()))->resolve(),
        ]);
    }

    public function index()
    {
        $software = DesktopSoftware::query()
            ->where('status', 'published')
            ->whereNotNull('file_path')
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => DesktopSoftwareResource::collection($software)->resolve(),
        ]);
    }

    public function show(string $slug)
    {
        $software = DesktopSoftware::query()
            ->where('status', 'published')
            ->whereNotNull('file_path')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => (new DesktopSoftwareResource($software))->resolve(),
        ]);
    }

    public function download(string $slug)
    {
        $software = DesktopSoftware::query()
            ->where('status', 'published')
            ->whereNotNull('file_path')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless(
            $software->file_path && Storage::disk('local')->exists($software->file_path),
            404
        );

        return response()->download(
            Storage::disk('local')->path($software->file_path),
            $software->downloadFilename()
        );
    }
}
