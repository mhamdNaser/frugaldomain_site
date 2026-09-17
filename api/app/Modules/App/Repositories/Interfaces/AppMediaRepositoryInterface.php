<?php

namespace App\Modules\App\Repositories\Interfaces;

use Illuminate\Http\UploadedFile;

interface AppMediaRepositoryInterface
{
    /** Stores the build archive for an app and returns the refreshed model. */
    public function storeArchive(int $appId, UploadedFile $archive);

    /** Removes the stored archive, both row and file. */
    public function deleteArchive(int $appId);

    /**
     * Stores one screenshot.
     *
     * @param string $role AppImage::ROLE_MAIN or AppImage::ROLE_SECONDARY
     */
    public function storeImage(int $appId, UploadedFile $image, string $role, ?int $ordering = null, ?string $alt = null);

    /** Deletes a single screenshot belonging to the given app. */
    public function deleteImage(int $appId, int $imageId);
}
