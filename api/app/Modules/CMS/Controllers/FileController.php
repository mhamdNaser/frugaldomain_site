<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\File;
use App\Modules\CMS\Requests\AttachFileRequest;
use App\Modules\CMS\Repositories\Interfaces\FilesRepositoryInterface;
use App\Modules\CMS\Requests\FilesIndexRequest;
use App\Modules\CMS\Resources\FileTableResource;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function __construct(
        protected FilesRepositoryInterface $repo,
    ) {}

    public function index(FilesIndexRequest $request)
    {
        $data = $request->validated();
        $result = $this->repo->all(
            $data['search'] ?? null,
            $data['rowsPerPage'] ?? 15,
            $data['page'] ?? 1,
            [
                'parent_field' => $data['parent_field'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'file_type' => $data['file_type'] ?? null,
                'role' => $data['role'] ?? null,
                'owner_type' => $data['owner_type'] ?? null,
                'sort_by' => $data['sort_by'] ?? null,
                'sort_direction' => $data['sort_direction'] ?? 'desc',
            ],
        );

        return response()->json([
            'data' => FileTableResource::collection($result->items()),
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $result->currentPage(),
                'from' => $result->firstItem(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'to' => $result->lastItem(),
                'total' => $result->total(),
            ],
            'facets' => $this->repo->facets(),
        ]);
    }

    public function attach(AttachFileRequest $request)
    {
        $validated = $request->validated();

        $file = File::query()->findOrFail((int) $validated['file_id']);
        $this->authorizeStoreAccess((string) $file->store_id);

        $updated = $this->attachFileToOwner(
            file: $file,
            ownerType: (string) $validated['owner_type'],
            ownerId: (int) $validated['owner_id'],
            role: $validated['role'] ?? null,
        );

        return response()->json([
            'message' => 'File linked successfully.',
            'data' => new FileTableResource($updated),
        ]);
    }

    private function detectExtension(?string $sourceUrl, ?string $mimeType): string
    {
        $extFromUrl = strtolower((string) pathinfo((string) parse_url((string) $sourceUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($extFromUrl !== '') {
            return $extFromUrl;
        }

        $mime = strtolower((string) $mimeType);

        return match (true) {
            str_contains($mime, 'jpeg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'svg') => 'svg',
            str_contains($mime, 'mp4') => 'mp4',
            default => 'bin',
        };
    }

    private function attachFileToOwner(File $file, string $ownerType, int $ownerId, ?string $role = null): File
    {
        $owner = $this->resolveOwner($ownerType, $ownerId);
        $ownerStoreId = (string) ($owner->store_id ?? '');

        abort_if($ownerStoreId === '', 422, 'Owner store is missing.');
        abort_if((string) $file->store_id !== $ownerStoreId, 422, 'File and owner must belong to the same store.');

        $this->authorizeStoreAccess($ownerStoreId);

        $file->fill([
            'fileable_type' => $owner::class,
            'fileable_id' => $owner->getKey(),
            'role' => $role ?: $this->defaultRoleForOwner($ownerType),
        ]);
        $file->save();

        if ($owner instanceof Collection) {
            $owner->image_url = $file->url;
            $owner->image_alt = $file->altText ?: $owner->title;
            $owner->save();
        }

        return $file->fresh();
    }

    private function resolveOwner(string $ownerType, int $ownerId): Model
    {
        return match ($ownerType) {
            'product' => Product::query()->findOrFail($ownerId),
            'variant' => ProductVariant::query()->findOrFail($ownerId),
            'collection' => Collection::query()->findOrFail($ownerId),
            default => abort(422, 'Unsupported owner type.'),
        };
    }

    private function defaultRoleForOwner(string $ownerType): string
    {
        return match ($ownerType) {
            'product' => 'product_image',
            'variant' => 'variant_image',
            'collection' => 'collection_image',
            default => 'global_file',
        };
    }

    private function resolveStore(?string $requestedStoreId): Store
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $storeId = (string) ($user->store?->id ?? '');
            abort_if($storeId === '', 404, 'No store is linked to the authenticated user.');

            return Store::query()->findOrFail($storeId);
        }

        $storeId = $requestedStoreId ?: (string) ($user?->store?->id ?? '');
        abort_if($storeId === '', 422, 'store_id is required.');

        return Store::query()->findOrFail($storeId);
    }

    private function authorizeStoreAccess(string $storeId): void
    {
        $user = auth()->user();

        if (
            $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('partner')
            && !$user->hasRole('admin')
        ) {
            $linkedStoreId = (string) ($user->store?->id ?? '');
            abort_if($linkedStoreId === '', 404, 'No store is linked to the authenticated user.');
            abort_if($linkedStoreId !== $storeId, 403, 'You are not allowed to access this store.');
        }
    }

    private function typeFromMimeAndTypename(?string $mimeType, ?string $typename): string
    {
        $mime = strtolower((string) $mimeType);

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return match ($typename) {
            'MediaImage' => 'image',
            'Video' => 'video',
            default => 'document',
        };
    }
}
