<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\File;
use App\Modules\Shopify\AutoSync\Exceptions\ShopifyUserErrorsException;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Links Shopify files (media library) to products, variants and collections, and deletes them.
 */
class FileMediaSyncer
{
    public function attach(File $file, Model $owner, SyncContext $ctx): void
    {
        match (true) {
            $owner instanceof Collection => $this->attachToCollection($file, $owner, $ctx),
            $owner instanceof ProductVariant => $this->attachToVariant($file, $owner, $ctx),
            $owner instanceof Product => $this->attachToProduct($file, $owner, $ctx),
            default => null,
        };
    }

    /**
     * Deletes the file in Shopify, which also removes it from every product using it.
     * Returns the deleted Shopify id, or null when the file was never in Shopify.
     */
    public function delete(File $file, SyncContext $ctx): ?string
    {
        $gid = $this->existingGid($file, $ctx);

        if ($gid) {
            $this->deleteGid($gid, $ctx);
        }

        return $gid;
    }

    public function deleteGid(string $gid, SyncContext $ctx): void
    {
        try {
            $ctx->gw->mutate(<<<'GQL'
mutation FileDelete($fileIds: [ID!]!) {
  fileDelete(fileIds: $fileIds) {
    deletedFileIds
    userErrors { field message code }
  }
}
GQL, ['fileIds' => [$gid]], 'fileDelete');
        } catch (ShopifyUserErrorsException $e) {
            if (!$e->isNotFound()) {
                throw $e;
            }
        }
    }

    private function attachToProduct(File $file, Product $product, SyncContext $ctx): string
    {
        $ctx->ensureCreated($product, 'shopify_product_id');
        $productGid = Gid::make($product->shopify_product_id, 'Product');
        $fileGid = $this->fileGid($file, $ctx);

        $ctx->gw->mutate(<<<'GQL'
mutation FileLink($files: [FileUpdateInput!]!) {
  fileUpdate(files: $files) {
    files { id }
    userErrors { field message code }
  }
}
GQL, ['files' => [['id' => $fileGid, 'referencesToAdd' => [$productGid]]]], 'fileUpdate');

        $this->waitUntilOnProduct($productGid, $fileGid, $ctx);

        return $fileGid;
    }

    /**
     * Linking is processed asynchronously by Shopify. The image only counts as added once
     * Shopify reports it READY on the product; a failure or no answer in time is an error,
     * so the caller's transaction rolls the local link back.
     */
    private function waitUntilOnProduct(string $productGid, string $fileGid, SyncContext $ctx, int $timeoutSeconds = 30): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        do {
            $nodes = $ctx->gw->query(
                'query ProductMediaStatus($id: ID!) { product(id: $id) { media(first: 250) { nodes { id status mediaErrors { message } } } } }',
                ['id' => $productGid],
            )['product']['media']['nodes'] ?? [];

            foreach ($nodes as $node) {
                if (($node['id'] ?? null) !== $fileGid) {
                    continue;
                }

                if (($node['status'] ?? null) === 'READY') {
                    return;
                }

                if (($node['status'] ?? null) === 'FAILED') {
                    $reason = collect($node['mediaErrors'] ?? [])->pluck('message')->filter()->implode(' | ');
                    throw ValidationException::withMessages(['shopify' => 'Shopify could not add this image to the product' . ($reason ? ": {$reason}" : '.')]);
                }
            }

            usleep(1_000_000);
        } while (microtime(true) < $deadline);

        throw ValidationException::withMessages(['shopify' => 'Shopify has not confirmed the image yet, so it was not added. Please try again in a moment.']);
    }

    private function attachToVariant(File $file, ProductVariant $variant, SyncContext $ctx): void
    {
        $product = $variant->product;
        $fileGid = $this->attachToProduct($file, $product, $ctx);
        $ctx->ensureCreated($variant, 'shopify_variant_id');

        $ctx->gw->mutate(<<<'GQL'
mutation VariantMedia($productId: ID!, $variantMedia: [ProductVariantAppendMediaInput!]!) {
  productVariantAppendMedia(productId: $productId, variantMedia: $variantMedia) {
    product { id }
    userErrors { field message code }
  }
}
GQL, [
            'productId' => Gid::make($product->fresh()->shopify_product_id, 'Product'),
            'variantMedia' => [[
                'variantId' => Gid::make($variant->shopify_variant_id, 'ProductVariant'),
                'mediaIds' => [$fileGid],
            ]],
        ], 'productVariantAppendMedia');
    }

    private function attachToCollection(File $file, Collection $collection, SyncContext $ctx): void
    {
        $ctx->ensureCreated($collection, 'shopify_collection_id');
        $source = $this->shopifyUrl($file);

        $ctx->gw->mutate(<<<'GQL'
mutation CollectionImage($input: CollectionInput!) {
  collectionUpdate(input: $input) {
    collection { id }
    userErrors { field message }
  }
}
GQL, ['input' => [
            'id' => Gid::make($collection->shopify_collection_id, 'Collection'),
            'image' => ['src' => $source, 'altText' => $file->altText ?: $collection->title],
        ]], 'collectionUpdate');
    }

    /**
     * Files uploaded through "upload-to-shopify" already have a Shopify id; others are
     * created in the Shopify media library from their public URL first.
     */
    private function fileGid(File $file, SyncContext $ctx): string
    {
        $meta = is_array($file->meta) ? $file->meta : [];

        if ($gid = $this->existingGid($file, $ctx)) {
            return $gid;
        }

        $source = $meta['source_url'] ?? $file->url;

        if (!is_string($source) || !preg_match('#^https?://#', $source) || preg_match('#//(localhost|127\.0\.0\.1)#', $source)) {
            throw ValidationException::withMessages(['shopify' => 'This file is not in Shopify. Upload it with "upload to Shopify" first.']);
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation FileCreate($files: [FileCreateInput!]!) {
  fileCreate(files: $files) {
    files { id __typename }
    userErrors { field message code }
  }
}
GQL, ['files' => [[
            'originalSource' => $source,
            'contentType' => str_starts_with((string) $file->mime_type, 'video/') ? 'VIDEO' : 'IMAGE',
            'alt' => $file->altText,
        ]]], 'fileCreate');

        $created = $result['files'][0] ?? [];
        $file->forceFill([
            'shopify_id' => Gid::numeric($created['id'] ?? null),
            'meta' => array_merge($meta, ['__typename' => $created['__typename'] ?? 'MediaImage', 'source_url' => $source]),
        ])->saveQuietly();

        return (string) $created['id'];
    }

    /**
     * The media id of a file already in Shopify. Library files know their type; images
     * imported from product.images only have a ProductImage id, so they are matched by
     * URL among the product's media.
     */
    private function existingGid(File $file, SyncContext $ctx): ?string
    {
        $meta = is_array($file->meta) ? $file->meta : [];

        if (filled($file->shopify_id) && filled($meta['__typename'] ?? null)) {
            return Gid::make((string) $file->shopify_id, (string) $meta['__typename']);
        }

        $owner = $file->fileable;
        $product = $owner instanceof ProductVariant ? $owner->product : $owner;
        $productGid = $product instanceof Product ? Gid::make($product->shopify_product_id, 'Product') : null;

        if (!$productGid) {
            return null;
        }

        $media = $ctx->gw->query(
            'query ProductMedia($id: ID!) { product(id: $id) { media(first: 250) { nodes { id ... on MediaImage { image { url } } } } } }',
            ['id' => $productGid],
        )['product']['media']['nodes'] ?? [];

        $paths = array_filter([strtok((string) $file->url, '?'), strtok((string) ($meta['source_url'] ?? ''), '?')]);

        foreach ($media as $node) {
            if (in_array(strtok((string) ($node['image']['url'] ?? ''), '?'), $paths, true)) {
                return (string) $node['id'];
            }
        }

        return null;
    }

    private function shopifyUrl(File $file): string
    {
        $meta = is_array($file->meta) ? $file->meta : [];
        $url = data_get($meta, 'source_url') ?? data_get($meta, 'image.url') ?? $file->url;

        if (!is_string($url) || preg_match('#//(localhost|127\.0\.0\.1)#', $url)) {
            throw ValidationException::withMessages(['shopify' => 'This file has no public URL Shopify can download.']);
        }

        return $url;
    }
}
