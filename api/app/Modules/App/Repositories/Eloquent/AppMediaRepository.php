<?php

namespace App\Modules\App\Repositories\Eloquent;

use App\Modules\App\Models\App;
use App\Modules\App\Models\AppImage;
use App\Modules\App\Repositories\Interfaces\AppMediaRepositoryInterface;
use App\Modules\App\Support\AppStorage;
use App\Traits\ManageFiles;
use Illuminate\Http\UploadedFile;

class AppMediaRepository implements AppMediaRepositoryInterface
{
    use ManageFiles;
    use AppStorage;

    protected $model;

    public function __construct(App $app)
    {
        $this->model = $app;
    }

    public function storeArchive(int $appId, UploadedFile $archive)
    {
        $app = $this->model->findOrFail($appId);
        $directory = $this->appDirectory($app->slug);

        // One archive per app: drop the previous build before writing the new
        // one so the folder never accumulates stale zips.
        if ($app->archive_path) {
            $this->deleteFile($app->archive_path);
        }

        $path = $this->uploadImage($archive, $directory, $app->slug . '-build', 'zip');

        $app->forceFill([
            'archive_path' => $path,
            'archive_name' => $archive->getClientOriginalName(),
            'archive_size' => $this->sizeOf($path),
        ])->save();

        return $app->fresh(['features', 'images']);
    }

    public function deleteArchive(int $appId)
    {
        $app = $this->model->findOrFail($appId);

        if ($app->archive_path) {
            $this->deleteFile($app->archive_path);
        }

        $app->forceFill([
            'archive_path' => null,
            'archive_name' => null,
            'archive_size' => null,
        ])->save();

        return $app->fresh(['features', 'images']);
    }

    public function storeImage(int $appId, UploadedFile $image, string $role, ?int $ordering = null, ?string $alt = null)
    {
        $app = $this->model->findOrFail($appId);
        $directory = $this->appDirectory($app->slug);

        $role = $role === AppImage::ROLE_MAIN ? AppImage::ROLE_MAIN : AppImage::ROLE_SECONDARY;

        if ($role === AppImage::ROLE_MAIN) {
            // Exactly one main image: replace whatever was there.
            $this->purge(AppImage::where('app_id', $app->id)->where('role', AppImage::ROLE_MAIN)->get());
            $ordering = 0;
        } else {
            $ordering = $ordering === null
                ? (int) AppImage::where('app_id', $app->id)->where('role', AppImage::ROLE_SECONDARY)->max('ordering') + 1
                : max(0, $ordering);

            // A secondary slot is a slot: uploading into an occupied one
            // replaces its occupant rather than stacking a fourth shot.
            $this->purge(
                AppImage::where('app_id', $app->id)
                    ->where('role', AppImage::ROLE_SECONDARY)
                    ->where('ordering', $ordering)
                    ->get()
            );
        }

        $extension = strtolower($image->getClientOriginalExtension() ?: 'png');
        $path = $this->uploadImage($image, $directory, $app->slug . '-' . $role . '-' . $ordering, $extension);

        $record = AppImage::create([
            'app_id' => $app->id,
            'path' => $path,
            'role' => $role,
            'alt' => $alt,
            'ordering' => $ordering,
        ]);

        // Mirror the main shot onto the app row so listings need no join and
        // `cover` (which the gallery card reads) stays populated.
        if ($role === AppImage::ROLE_MAIN) {
            $app->forceFill(['main_image' => $path, 'cover' => $path])->save();
        }

        return [
            'app' => $app->fresh(['features', 'images']),
            'image' => $record,
        ];
    }

    public function deleteImage(int $appId, int $imageId)
    {
        $app = $this->model->findOrFail($appId);

        $image = AppImage::where('app_id', $app->id)->findOrFail($imageId);
        $wasMain = $image->role === AppImage::ROLE_MAIN;

        $this->deleteFile($image->path);
        $image->delete();

        if ($wasMain) {
            $app->forceFill(['main_image' => null, 'cover' => null])->save();
        }

        return $app->fresh(['features', 'images']);
    }

    /** Deletes rows and their files together. */
    protected function purge($images): void
    {
        foreach ($images as $image) {
            $this->deleteFile($image->path);
            $image->delete();
        }
    }

    protected function sizeOf(string $path): ?int
    {
        $full = public_path($path);

        return file_exists($full) ? filesize($full) : null;
    }
}
