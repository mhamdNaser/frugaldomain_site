<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductMedia;
use App\Modules\CMS\Models\File;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('Locale', '0001_01_01_000003_create_countries_table'),
        $m('Locale', '0001_01_01_000004_create_states_table'),
        $m('Locale', '0001_01_01_000005_create_cities_table'),
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('User', '2026_09_29_120000_allow_self_registration_on_users_table'),
        $m('User', '2025_10_04_075133_create_personal_access_tokens_table'),
        $m('User', '2025_10_11_123012_create_permission_tables'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
        $m('Catalog', '2026_02_26_091538_create_product_variants_table'),
        $m('Catalog', '2026_04_20_130400_create_advanced_commerce_catalog_tables'),
    ]]);
    Role::findOrCreate('partner', 'web');
    Storage::fake('public');

    $this->owner = User::create([
        'first_name' => 'Store', 'last_name' => 'Owner', 'name' => 'owner',
        'email' => 'owner@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $this->owner->assignRole('partner');

    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $this->owner->id,
        'shopify_domain' => 'demo.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_test'),
        'name' => 'Demo',
    ]);
});

/** A real 1x1 PNG, so the upload passes the mimes rule without the GD extension. */
function fakePng(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
    ));
}

/**
 * @param array<string, mixed> $node the file as Shopify returns it once processed
 */
function fakeShopifyFileUpload(array $node, string $mediaStatus = 'READY'): void
{
    Http::fake([
        'staged.example.com/*' => Http::response('', 201),
        'cdn.shopify.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        '*/admin/api/*/graphql.json' => function (Request $request) use ($node, $mediaStatus) {
            $query = (string) $request['query'];

            return Http::response(match (true) {
                str_contains($query, 'stagedUploadsCreate') => ['data' => ['stagedUploadsCreate' => [
                    'stagedTargets' => [[
                        'url' => 'https://staged.example.com/upload',
                        'resourceUrl' => 'https://staged.example.com/upload/abc',
                        'parameters' => [['name' => 'key', 'value' => 'abc']],
                    ]],
                    'userErrors' => [],
                ]]],
                // Shopify answers before the image is processed: no URL yet.
                str_contains($query, 'fileCreate') => ['data' => ['fileCreate' => [
                    'files' => [['__typename' => 'MediaImage', 'id' => $node['id'], 'alt' => 'shirt', 'image' => null]],
                    'userErrors' => [],
                ]]],
                str_contains($query, 'FileStatus') => ['data' => ['node' => $node]],
                str_contains($query, 'fileUpdate') => ['data' => ['fileUpdate' => ['files' => [['id' => $node['id']]], 'userErrors' => []]]],
                // The product's images as Shopify lists them once the media is READY.
                str_contains($query, 'GetProductImages') => ['data' => ['product' => ['images' => [
                    'edges' => [['cursor' => 'c1', 'node' => ['id' => 'gid://shopify/ProductImage/900', 'url' => ($node['image']['url'] ?? 'https://cdn.shopify.com/x.png') . '?v=1']]],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'c1'],
                ]]]],
                str_contains($query, 'ProductMediaStatus') => ['data' => ['product' => ['media' => ['nodes' => [['id' => $node['id'], 'status' => $mediaStatus, 'mediaErrors' => $mediaStatus === 'FAILED' ? [['message' => 'Image is too large']] : []]]]]]],
                default => ['data' => []],
            });
        },
    ]);
}

it('uploads an image to Shopify and waits until it has a URL', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/555',
        'alt' => 'shirt',
        'fileStatus' => 'READY',
        'mimeType' => 'image/png',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/shirt.png', 'width' => 800, 'height' => 600],
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('shirt.png'),
            'title' => 'shirt',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.width', 800);

    $file = File::query()->sole();
    expect((string) $file->shopify_id)->toBe('555')
        ->and($file->type)->toBe('image')
        ->and($file->size)->toBeGreaterThan(0)
        ->and($file->meta['source_url'])->toBe('https://cdn.shopify.com/s/files/shirt.png')
        // Shown straight from Shopify's CDN; a local copy 404'd on the host.
        ->and($file->url)->toBe('https://cdn.shopify.com/s/files/shirt.png')
        ->and($file->disk)->toBe('shopify');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('links an uploaded image to the product in Shopify', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/556',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/hat.png', 'width' => 10, 'height' => 10],
    ]);

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Hat', 'slug' => 'hat', 'handle' => 'hat',
        'shopify_product_id' => 'gid://shopify/Product/77',
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('hat.png'),
            'owner_type' => 'product',
            'owner_id' => $product->id,
            'role' => 'product_image',
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $file = File::query()->sole();
    expect($file->fileable_id)->toBe($product->id)
        ->and($file->role)->toBe('product_image');

    Http::assertSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'fileUpdate')
        && $request['variables']['files'][0] === [
            'id' => 'gid://shopify/MediaImage/556',
            'referencesToAdd' => ['gid://shopify/Product/77'],
        ]);
});

it('does not link the image or report success when Shopify fails to add it to the product', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/557',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/cap.png', 'width' => 10, 'height' => 10],
    ], mediaStatus: 'FAILED');

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Cap', 'slug' => 'cap', 'handle' => 'cap',
        'shopify_product_id' => 'gid://shopify/Product/78',
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('cap.png'),
            'owner_type' => 'product',
            'owner_id' => $product->id,
            'role' => 'product_image',
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.shopify.0', 'Shopify could not add this image to the product: Image is too large');

    expect(File::query()->sole()->fileable_id)->toBeNull()
        ->and($product->fresh()->featured_image['url'] ?? null)->toBeNull();
});

it('returns a readable error when Shopify cannot process the file', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/557',
        'fileStatus' => 'FAILED',
        'fileErrors' => [['code' => 'UNSUPPORTED_IMAGE_FILE_TYPE', 'message' => 'Image format is not supported']],
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('broken.png'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonPath('errors.file.0', 'Shopify upload failed: Image format is not supported');

    expect(File::query()->count())->toBe(0);
    // Not added here, so not left behind in Shopify either.
    Http::assertSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'fileDelete')
        && $request['variables']['fileIds'] === ['gid://shopify/MediaImage/557']);
});

it('rejects file types Shopify does not accept', function () {
    Http::fake();

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');

    Http::assertNothingSent();
});

it('shows the linked image as the product cover right away', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/600',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/cap.png', 'width' => 10, 'height' => 10],
    ]);
    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Cap', 'slug' => 'cap', 'handle' => 'cap',
        'shopify_product_id' => 'gid://shopify/Product/80',
    ]);

    $this->actingAs($this->owner)->post('/api/admin/files/upload-to-shopify', [
        'file' => fakePng('cap.png'), 'owner_type' => 'product', 'owner_id' => $product->id,
    ], ['Accept' => 'application/json'])->assertCreated();

    expect($product->fresh()->featured_image['url'])->toBe(File::query()->sole()->url);
});

/** A product with one image stored three ways, as the different Shopify syncs leave it. */
function productWithImage($test): array
{
    $product = Product::query()->create([
        'store_id' => $test->store->id, 'title' => 'Bag', 'slug' => 'bag', 'handle' => 'bag',
        'shopify_product_id' => 'gid://shopify/Product/90',
        'featured_image' => ['url' => 'https://cdn.shopify.com/s/files/bag.png?v=1'],
    ]);
    $uploaded = File::query()->create([
        'store_id' => $test->store->id, 'disk' => 'public', 'path' => 'x/bag.png', 'url' => 'https://app.test/storage/x/bag.png',
        'type' => 'image', 'role' => 'product_image', 'fileable_type' => Product::class, 'fileable_id' => $product->id,
        'shopify_id' => 700, 'meta' => ['__typename' => 'MediaImage', 'source_url' => 'https://cdn.shopify.com/s/files/bag.png?v=1'],
    ]);
    $imported = File::query()->create([
        'store_id' => $test->store->id, 'disk' => 'public', 'path' => 'bag', 'url' => 'https://cdn.shopify.com/s/files/bag.png?v=2',
        'type' => 'image', 'role' => 'product_image', 'fileable_type' => Product::class, 'fileable_id' => $product->id,
        'shopify_id' => 12345, 'meta' => [],
    ]);
    $media = ProductMedia::query()->create([
        'store_id' => $test->store->id, 'product_id' => $product->id, 'shopify_media_id' => 'gid://shopify/MediaImage/700',
        'url' => 'https://cdn.shopify.com/s/files/bag.png?v=3',
    ]);

    return [$product, $uploaded, $imported, $media];
}

function fakeShopifyDelete(array $errors = []): void
{
    Http::fake(['*/admin/api/*/graphql.json' => fn (Request $request) => Http::response(match (true) {
        str_contains((string) $request['query'], 'fileDelete') => ['data' => ['fileDelete' => [
            'deletedFileIds' => $errors ? [] : ['gid://shopify/MediaImage/700'], 'userErrors' => $errors,
        ]]],
        str_contains((string) $request['query'], 'ProductMedia(') => ['data' => ['product' => ['media' => ['nodes' => [
            ['id' => 'gid://shopify/MediaImage/700', 'image' => ['url' => 'https://cdn.shopify.com/s/files/bag.png?v=9']],
        ]]]]],
        default => ['data' => []],
    })]);
}

it('deletes an image in Shopify and every local copy of it', function () {
    fakeShopifyDelete();
    [$product, $uploaded, $imported, $media] = productWithImage($this);

    $this->actingAs($this->owner)->deleteJson("/api/admin/files/{$uploaded->id}")->assertOk();

    Http::assertSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'fileDelete')
        && $request['variables'] === ['fileIds' => ['gid://shopify/MediaImage/700']]);
    expect(File::query()->count())->toBe(0)
        ->and(ProductMedia::query()->count())->toBe(0)
        ->and($product->fresh()->featured_image)->toBeNull();
});

it('finds the Shopify media of an image imported from the product by its URL', function () {
    fakeShopifyDelete();
    [, , $imported] = productWithImage($this);

    $this->actingAs($this->owner)->deleteJson("/api/admin/files/{$imported->id}")->assertOk();

    Http::assertSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'fileDelete')
        && $request['variables'] === ['fileIds' => ['gid://shopify/MediaImage/700']]);
    expect(File::query()->count())->toBe(0);
});

it('deletes product media rows through their own endpoint', function () {
    fakeShopifyDelete();
    [, , , $media] = productWithImage($this);

    $this->actingAs($this->owner)->deleteJson("/api/admin/product-media/{$media->id}")->assertOk();

    expect(ProductMedia::query()->count())->toBe(0)
        ->and(File::query()->count())->toBe(0);
});

it('keeps the image when Shopify refuses to delete it', function () {
    fakeShopifyDelete([['field' => ['fileIds'], 'message' => 'File is in use by a theme', 'code' => 'FILE_LOCKED']]);
    [, $uploaded] = productWithImage($this);

    $this->actingAs($this->owner)->deleteJson("/api/admin/files/{$uploaded->id}")
        ->assertStatus(422)->assertJsonValidationErrors('shopify');

    expect(File::query()->count())->toBe(2)->and(ProductMedia::query()->count())->toBe(1);
});

it('keeps exactly the product images Shopify has, dropping duplicates and removed ones', function () {
    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Mug', 'slug' => 'mug', 'handle' => 'mug',
        'shopify_product_id' => 'gid://shopify/Product/90',
    ]);
    $row = fn (string $url, ?string $id) => File::query()->create([
        'store_id' => $this->store->id, 'disk' => 'public', 'type' => 'image', 'role' => 'product_image',
        'path' => $url, 'url' => $url, 'shopify_id' => $id,
        'fileable_type' => Product::class, 'fileable_id' => $product->id,
    ]);
    $row('https://cdn.shopify.com/mug.png', '1');
    $row('https://cdn.shopify.com/mug.png', '1');       // duplicate copy
    $row('https://cdn.shopify.com/old.png', '2');       // removed in Shopify

    Http::fake(['*/admin/api/*/graphql.json' => Http::response(['data' => ['product' => ['images' => [
        'edges' => [['cursor' => 'a', 'node' => ['id' => 'gid://shopify/ProductImage/1', 'url' => 'https://cdn.shopify.com/mug.png']]],
        'pageInfo' => ['hasNextPage' => false, 'endCursor' => 'a'],
    ]]]])]);

    app(\App\Modules\Shopify\Services\Sync\ImageProductSyncService::class)
        ->syncByProduct($this->store, $product, 'gid://shopify/Product/90');

    expect(File::query()->where('fileable_id', $product->id)->pluck('url')->all())->toBe(['https://cdn.shopify.com/mug.png']);
});

it('leaves product images alone when Shopify answers without the image list', function () {
    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Bag', 'slug' => 'bag', 'handle' => 'bag',
        'shopify_product_id' => 'gid://shopify/Product/91',
    ]);
    File::query()->create([
        'store_id' => $this->store->id, 'disk' => 'public', 'type' => 'image', 'role' => 'product_image',
        'path' => 'https://cdn.shopify.com/bag.png', 'url' => 'https://cdn.shopify.com/bag.png',
        'fileable_type' => Product::class, 'fileable_id' => $product->id,
    ]);
    Http::fake(['*/admin/api/*/graphql.json' => Http::response(['data' => ['product' => null]])]);

    app(\App\Modules\Shopify\Services\Sync\ImageProductSyncService::class)
        ->syncByProduct($this->store, $product, 'gid://shopify/Product/91');

    expect(File::query()->where('fileable_id', $product->id)->count())->toBe(1);
});

it('stores the new product image right away from the Shopify answer, exactly once', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/558',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/scarf.png', 'width' => 10, 'height' => 10],
    ]);

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Scarf', 'slug' => 'scarf', 'handle' => 'scarf',
        'shopify_product_id' => 'gid://shopify/Product/79',
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('scarf.png'),
            'owner_type' => 'product',
            'owner_id' => $product->id,
            'role' => 'product_image',
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $images = File::query()->where('fileable_type', Product::class)->where('fileable_id', $product->id)->get();
    expect($images)->toHaveCount(1)
        ->and((string) $images[0]->shopify_id)->toBe('900')
        ->and($images[0]->url)->toBe('https://cdn.shopify.com/s/files/scarf.png?v=1');

    // Only the images were re-read, not the whole product (variants, inventory, metafields...).
    Http::assertNotSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'query GetProduct('));
});

it('shows an image uploaded to the gallery without a product right away, first in the list', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/700',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/banner.png', 'width' => 1200, 'height' => 400],
    ]);
    File::query()->create([
        'store_id' => $this->store->id, 'disk' => 'shopify', 'type' => 'image', 'role' => 'global_file',
        'path' => 'https://cdn.shopify.com/old.png', 'url' => 'https://cdn.shopify.com/old.png', 'created_at' => now()->subDay(),
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', ['file' => fakePng('banner.png'), 'title' => 'banner'], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.fileable_id', null);

    $list = $this->actingAs($this->owner)
        ->postJson('/api/admin/files', ['rowsPerPage' => 15, 'page' => 1, 'sort_by' => 'created_at', 'sort_direction' => 'desc'])
        ->assertOk()
        ->json('data');

    expect($list)->toHaveCount(2)
        ->and((int) $list[0]['width'])->toBe(1200)
        ->and($list[0]['url'])->not->toBeEmpty();
});
