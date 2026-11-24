<?php

namespace App\Modules\CMS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductMedia;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\File;
use App\Modules\CMS\Requests\AttachFileRequest;
use App\Modules\CMS\Repositories\Interfaces\FilesRepositoryInterface;
use App\Modules\CMS\Requests\FilesIndexRequest;
use App\Modules\CMS\Requests\UploadShopifyFileRequest;
use App\Modules\CMS\Resources\FileTableResource;
use App\Modules\CMS\Services\ShopifyFileUploadService;
use App\Modules\Shopify\AutoSync\ShopifyAutoSync;
use App\Modules\Shopify\AutoSync\Syncers\FileMediaSyncer;
use App\Modules\Shopify\Exceptions\ShopifySyncException;
use App\Modules\Shopify\Services\Sync\ImageProductSyncService;
use App\Modules\Shopify\Services\Sync\ImageVariantSyncService;
use App\Modules\Shopify\Support\ShopifyHelper;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    public function __construct(
        protected FilesRepositoryInterface $repo,
        protected ShopifyFileUploadService $shopifyFileUploadService,
        protected ShopifyAutoSync $shopifySync,
        protected FileMediaSyncer $fileMediaSyncer,
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

    public function uploadToShopify(UploadShopifyFileRequest $request)
    {
        // Waiting for Shopify to process the file can outlast the default 30s PHP limit.
        set_time_limit(150);
        $validated = $request->validated();
        $store = $this->resolveStore($validated['store_id'] ?? null);

        try {
            $shopifyFile = $this->shopifyFileUploadService->upload(
                store: $store,
                file: $request->file('file'),
                title: $validated['title'] ?? null,
            );
        } catch (\RuntimeException|ShopifySyncException $e) {
            report($e);

            throw ValidationException::withMessages([
                'file' => 'Shopify upload failed: ' . $e->getMessage(),
            ]);
        }

        $localFile = $this->upsertLocalFile(
            store: $store,
            shopifyFile: $shopifyFile,
            fallbackMimeType: $request->file('file')?->getMimeType(),
            role: $validated['role'] ?? 'global_file',
            size: $request->file('file')?->getSize(),
        );

        if (!empty($validated['owner_type']) && !empty($validated['owner_id'])) {
            $this->attachFileToOwner(
                $localFile,
                (string) $validated['owner_type'],
                (int) $validated['owner_id'],
                $validated['role'] ?? null,
            );
        }

        return response()->json([
            'message' => 'File uploaded to Shopify successfully.',
            'data' => new FileTableResource($localFile),
            'meta' => ['shopify_sync' => $this->shopifySync->report()],
        ], 201);
    }

    public function attach(AttachFileRequest $request)
    {
        set_time_limit(90); // waits for Shopify to finish adding the image to the product
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
            'meta' => ['shopify_sync' => $this->shopifySync->report()],
        ]);
    }

    private function upsertLocalFile(Store $store, array $shopifyFile, ?string $fallbackMimeType = null, ?string $role = null, ?int $size = null): File
    {
        $sourceUrl = $this->extractUrl($shopifyFile);
        $mimeType = $this->extractMimeType($shopifyFile) ?: $fallbackMimeType;
        $width = data_get($shopifyFile, 'image.width') ?? data_get($shopifyFile, 'preview.image.width');
        $height = data_get($shopifyFile, 'image.height') ?? data_get($shopifyFile, 'preview.image.height');
        $alt = data_get($shopifyFile, 'alt');
        $shopifyGid = (string) ($shopifyFile['id'] ?? '');
        $shopifyId = ShopifyHelper::extractId($shopifyGid);

        return File::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => $shopifyId ? (int) $shopifyId : null,
            ],
            [
                // Served from Shopify's CDN like every synced file; a local copy needed a
                // public storage link the host does not have, so the gallery showed a 404.
                'disk' => 'shopify',
                'path' => $sourceUrl,
                'url' => $sourceUrl,
                'mime_type' => $mimeType,
                'size' => $size,
                'type' => $this->typeFromMimeAndTypename($mimeType, $shopifyFile['__typename'] ?? null),
                'width' => $width,
                'height' => $height,
                'altText' => $alt,
                'role' => $role ?: 'global_file',
                'position' => 0,
                'fileable_type' => null,
                'fileable_id' => null,
                'meta' => array_merge($shopifyFile, [
                    'source_url' => $sourceUrl,
                ]),
            ],
        );
    }

    private function attachFileToOwner(File $file, string $ownerType, int $ownerId, ?string $role = null): File
    {
        $owner = $this->resolveOwner($ownerType, $ownerId);
        $ownerStoreId = (string) ($owner->store_id ?? '');

        abort_if($ownerStoreId === '', 422, 'Owner store is missing.');
        abort_if((string) $file->store_id !== $ownerStoreId, 422, 'File and owner must belong to the same store.');

        $this->authorizeStoreAccess($ownerStoreId);

        DB::transaction(function () use ($file, $owner, $ownerType, $role) {
            $this->linkFileLocally($file, $owner, $ownerType, $role);
            $this->shopifySync->run(
                (string) $owner->store_id,
                fn ($ctx) => $this->fileMediaSyncer->attach($file, $owner, $ctx),
            );
        });

        $localUrl = $file->url;
        $this->readBackImages($owner);
        $fresh = $file->fresh();

        // The cover was set from the local copy; follow the image to the URL Shopify gave it.
        $product = $owner instanceof ProductVariant ? $owner->product : $owner;
        if ($product instanceof Product && $fresh && $fresh->url !== $localUrl
            && ($product->fresh()->featured_image['url'] ?? null) === $localUrl) {
            $product->forceFill(['featured_image' => ['url' => $fresh->url, 'altText' => $fresh->altText ?: $product->title]])->save();
        }

        return $fresh ?? $file;
    }

    /**
     * Re-reads only the images Shopify now holds for the owner. A full product read-back
     * (variants, inventory, metafields...) made the request slow enough to be cut off
     * after Shopify had saved the image, so the dashboard never stored it.
     */
    private function readBackImages(Model $owner): void
    {
        $product = $owner instanceof ProductVariant ? $owner->product : $owner;

        if (!$product instanceof Product) {
            $this->shopifySync->refreshFromShopify($owner);
            return;
        }

        if (($this->shopifySync->report()['status'] ?? null) !== 'synced' || blank($product->shopify_product_id)) {
            return;
        }

        try {
            $store = Store::query()->findOrFail($product->store_id);
            app(ImageProductSyncService::class)->syncByProduct($store, $product, (string) $product->shopify_product_id);

            if ($owner instanceof ProductVariant && filled($owner->shopify_variant_id)) {
                app(ImageVariantSyncService::class)->syncByVariant($store, $owner, (string) $owner->shopify_variant_id);
            }
        } catch (\Throwable $e) {
            // The image is saved in Shopify and linked here; only the refresh failed.
            Log::warning('Image read-back failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
        }
    }

    private function linkFileLocally(File $file, Model $owner, string $ownerType, ?string $role): void
    {
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

        // Shopify sets featuredImage only after it processes the media, so the first image
        // is shown as the cover right away instead of waiting for the next sync.
        if ($owner instanceof Product && empty($owner->featured_image['url'] ?? null)) {
            $owner->featured_image = ['url' => $file->url, 'altText' => $file->altText ?: $owner->title];
            $owner->save();
        }
    }

    /**
     * Deletes the file in Shopify (it disappears from every product using it) and every
     * local copy of it. Nothing is deleted locally if Shopify could not be reached.
     */
    public function destroy(int $id)
    {
        $file = File::query()->findOrFail($id);
        $this->authorizeStoreAccess((string) $file->store_id);
        $meta = is_array($file->meta) ? $file->meta : [];

        $this->deleteEverywhere(
            storeId: (string) $file->store_id,
            product: $this->productOf($file->fileable),
            urls: [$file->url, $meta['source_url'] ?? null],
            deleteInShopify: function ($ctx) use ($file) {
                return $this->fileMediaSyncer->delete($file, $ctx);
            },
            deleteLocal: fn () => $file->delete(),
        );

        return response()->json([
            'message' => 'File deleted successfully.',
            'meta' => ['shopify_sync' => $this->shopifySync->report()],
        ]);
    }

    public function destroyProductMedia(int $id)
    {
        $media = ProductMedia::query()->findOrFail($id);
        $this->authorizeStoreAccess((string) $media->store_id);

        $this->deleteEverywhere(
            storeId: (string) $media->store_id,
            product: Product::query()->find($media->product_id),
            urls: [$media->url],
            deleteInShopify: function ($ctx) use ($media) {
                if (blank($media->shopify_media_id)) {
                    return null;
                }

                $this->fileMediaSyncer->deleteGid((string) $media->shopify_media_id, $ctx);

                return (string) $media->shopify_media_id;
            },
            deleteLocal: fn () => $media->delete(),
        );

        return response()->json([
            'message' => 'Media deleted successfully.',
            'meta' => ['shopify_sync' => $this->shopifySync->report()],
        ]);
    }

    /**
     * @param array<int, ?string> $urls
     */
    private function deleteEverywhere(string $storeId, ?Product $product, array $urls, \Closure $deleteInShopify, \Closure $deleteLocal): void
    {
        // The same image can exist as a library file, a product image file and a product
        // media row; match them by URL without the ?v= query string.
        $paths = array_values(array_filter(array_map(fn ($url) => strtok((string) $url, '?') ?: null, $urls)));

        DB::transaction(function () use ($storeId, $paths, $deleteInShopify, $deleteLocal) {
            $deletedGid = null;
            $this->shopifySync->run($storeId, function ($ctx) use ($deleteInShopify, &$deletedGid) {
                $deletedGid = $deleteInShopify($ctx);
            }); // aborts with 503 when Shopify cannot be reached, so nothing is deleted locally

            $deleteLocal();

            ProductMedia::query()
                ->where('store_id', $storeId)
                ->where(function ($query) use ($deletedGid, $paths) {
                    $query->when($deletedGid, fn ($q) => $q->orWhere('shopify_media_id', $deletedGid));
                    foreach ($paths as $path) {
                        $query->orWhere('url', 'like', $path . '%');
                    }
                })
                ->delete();

            if ($paths !== []) {
                File::query()
                    ->where('store_id', $storeId)
                    ->where(function ($query) use ($paths) {
                        foreach ($paths as $path) {
                            $query->orWhere('url', 'like', $path . '%')
                                ->orWhere('meta->source_url', 'like', $path . '%');
                        }
                    })
                    ->delete();
            }
        });

        if (!$product) {
            return;
        }

        if (in_array(strtok((string) ($product->featured_image['url'] ?? ''), '?'), $paths, true)) {
            $product->featured_image = null;
            $product->save();
        }

        $this->shopifySync->refreshFromShopify($product);
    }

    private function productOf(?Model $owner): ?Product
    {
        return $owner instanceof ProductVariant ? $owner->product : ($owner instanceof Product ? $owner : null);
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

    private function extractUrl(array $shopifyFile): ?string
    {
        return data_get($shopifyFile, 'image.url')
            ?? data_get($shopifyFile, 'url')
            ?? data_get($shopifyFile, 'sources.0.url')
            ?? data_get($shopifyFile, 'preview.image.url');
    }

    private function extractMimeType(array $shopifyFile): ?string
    {
        return data_get($shopifyFile, 'mimeType')
            ?? data_get($shopifyFile, 'sources.0.mimeType');
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
