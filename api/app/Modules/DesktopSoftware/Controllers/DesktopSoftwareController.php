<?php

namespace App\Modules\DesktopSoftware\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DesktopSoftware\Models\DesktopSoftware;
use App\Modules\DesktopSoftware\Resources\DesktopSoftwareResource;
use Illuminate\Support\Facades\Storage;

class DesktopSoftwareController extends Controller
{
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
