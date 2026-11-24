<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Vendor;
use App\Modules\Shopify\AutoSync\ShopifyAutoSync;
use App\Modules\Shopify\OutboundSync\Jobs\ProcessOutboundSyncJob;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('Catalog', '2026_02_26_091703_create_collections_table'),
        $m('Catalog', '2026_02_26_091740_create_collection_products_table'),
        $m('Catalog', '2026_02_26_091520_create_options_table'),
        $m('Catalog', '2026_03_31_091008_create_product_options_table'),
        $m('Catalog', '2026_03_31_091712_create_tags_table'),
        $m('Catalog', '2026_03_31_091723_create_taggables_table'),
        $m('Catalog', '2026_02_26_091521_create_option_values_table'),
        $m('Catalog', '2026_02_26_091538_create_product_variants_table'),
        $m('Catalog', '2026_03_31_091024_create_variant_option_values_table'),
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
        $m('CMS', '2026_03_31_091039_create_metafields_table'),
        $m('Inventory', '2026_02_27_135717_create_locations_table'),
        $m('Inventory', '2026_02_27_135719_create_inventories_table'),
        $m('Inventory', '2026_03_25_141614_create_inventory_levels_table'),
        $m('Shopify', '2026_04_25_170000_create_shopify_outbound_syncs_table'),
        $m('Shopify', '2026_04_25_170100_create_shopify_outbound_sync_attempts_table'),
    ]]);

    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'shopify_domain' => 'demo.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_test'),
        'name' => 'Demo',
    ]);
});

/**
 * Fakes the Shopify GraphQL endpoint: picks the response by the operation name it finds
 * in the query, and records every call.
 *
 * @param array<string, array|callable> $responses
 */
function fakeShopifyGraphql(array $responses): void
{
    Http::fake(['*/admin/api/*/graphql.json' => function (Request $request) use ($responses) {
        $query = (string) $request['query'];

        foreach ($responses as $needle => $response) {
            if (str_contains($query, $needle)) {
                return is_callable($response) ? $response($request) : Http::response($response);
            }
        }

        return Http::response(['data' => []]);
    }]);
}

function sentShopifyQueries(string $needle): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains((string) $pair[0]['query'], $needle))
        ->map(fn ($pair) => $pair[0]->data())
        ->values()
        ->all();
}

it('creates a new collection in Shopify, stores its id and publishes it', function () {
    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => ['id' => 'gid://shopify/Collection/101', 'handle' => 'summer'],
            'userErrors' => [],
        ]]],
        'publications(' => ['data' => ['publications' => ['nodes' => [
            ['id' => 'gid://shopify/Publication/9', 'catalog' => ['title' => 'Online Store']],
        ]]]],
        'publishablePublish' => ['data' => ['publishablePublish' => ['userErrors' => []]]],
    ]);

    $sync = app(ShopifyAutoSync::class);
    $collection = $sync->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer',
        'description' => '<p>Hot</p>',
    ]));

    expect($collection->fresh()->shopify_collection_id)->toBe('gid://shopify/Collection/101')
        ->and($collection->fresh()->handle)->toBe('summer')
        ->and($sync->report()['status'])->toBe('synced');

    $create = sentShopifyQueries('collectionCreate')[0];
    expect($create['variables']['input'])->toMatchArray(['title' => 'Summer', 'descriptionHtml' => '<p>Hot</p>']);

    $publish = sentShopifyQueries('publishablePublish')[0];
    expect($publish['variables'])->toBe([
        'id' => 'gid://shopify/Collection/101',
        'input' => [['publicationId' => 'gid://shopify/Publication/9']],
    ]);
});

it('rolls the local record back when Shopify rejects the data', function () {
    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => null,
            'userErrors' => [['field' => ['input', 'handle'], 'message' => 'Handle has already been taken']],
        ]]],
    ]);

    expect(fn () => app(ShopifyAutoSync::class)->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Duplicate',
        'handle' => 'taken',
    ])))->toThrow(ValidationException::class, 'Handle has already been taken');

    expect(Collection::query()->count())->toBe(0);
});

it('saves nothing and reports an error when Shopify is unreachable', function () {
    Queue::fake();
    Http::fake(['*' => Http::response('Service unavailable', 503)]);

    expect(fn () => app(ShopifyAutoSync::class)->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Later',
    ])))->toThrow(HttpException::class, 'Shopify could not be reached');

    expect(Collection::query()->count())->toBe(0)
        ->and(DB::table('shopify_outbound_syncs')->count())->toBe(0);
    Queue::assertNotPushed(ProcessOutboundSyncJob::class);
});

it('re-pushes linked products when a vendor is renamed', function () {
    fakeShopifyGraphql([
        'productUpdate' => ['data' => ['productUpdate' => ['product' => ['id' => 'x'], 'userErrors' => []]]],
    ]);

    $vendor = Vendor::query()->create(['store_id' => $this->store->id, 'name' => 'Old Co', 'slug' => 'old-co']);
    Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Shirt', 'slug' => 'shirt', 'handle' => 'shirt',
        'vendor_id' => $vendor->id, 'shopify_product_id' => 'gid://shopify/Product/55',
    ]);
    Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Local only', 'slug' => 'local', 'handle' => 'local',
        'vendor_id' => $vendor->id,
    ]);

    app(ShopifyAutoSync::class)->update($vendor, ['name'], function () use ($vendor) {
        $vendor->update(['name' => 'New Co']);

        return $vendor;
    });

    $updates = sentShopifyQueries('productUpdate');
    expect($updates)->toHaveCount(1)
        ->and($updates[0]['variables']['product'])->toBe(['id' => 'gid://shopify/Product/55', 'vendor' => 'New Co']);
});

it('sends collection membership changes with the product update', function () {
    fakeShopifyGraphql([
        'productUpdate' => ['data' => ['productUpdate' => ['product' => ['id' => 'x'], 'userErrors' => []]]],
    ]);

    $old = Collection::query()->create(['store_id' => $this->store->id, 'title' => 'Old', 'shopify_collection_id' => 'gid://shopify/Collection/1']);
    $new = Collection::query()->create(['store_id' => $this->store->id, 'title' => 'New', 'shopify_collection_id' => 'gid://shopify/Collection/2']);
    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Hat', 'slug' => 'hat', 'handle' => 'hat',
        'shopify_product_id' => 'gid://shopify/Product/77',
    ]);
    $product->collections()->attach($old->id, ['store_id' => $this->store->id]);

    app(ShopifyAutoSync::class)->update($product, ['collection_ids'], function () use ($product, $new) {
        $product->collections()->sync([$new->id => ['store_id' => $product->store_id]]);

        return $product;
    });

    expect(sentShopifyQueries('productUpdate')[0]['variables']['product'])->toBe([
        'id' => 'gid://shopify/Product/77',
        'collectionsToJoin' => ['gid://shopify/Collection/2'],
        'collectionsToLeave' => ['gid://shopify/Collection/1'],
    ]);
});

it('deletes the Shopify product together with the local one', function () {
    fakeShopifyGraphql([
        'productDelete' => ['data' => ['productDelete' => ['deletedProductId' => 'gid://shopify/Product/9', 'userErrors' => []]]],
    ]);

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Bag', 'slug' => 'bag', 'handle' => 'bag',
        'shopify_product_id' => '9',
    ]);

    app(ShopifyAutoSync::class)->delete($product, fn () => $product->delete());

    expect(sentShopifyQueries('productDelete')[0]['variables'])->toBe(['input' => ['id' => 'gid://shopify/Product/9']]);
});

it('only saves locally when the store is not connected to Shopify', function () {
    Http::fake();
    $this->store->update(['shopify_access_token' => null]);

    $sync = app(ShopifyAutoSync::class);
    $sync->create(fn () => Collection::query()->create(['store_id' => $this->store->id, 'title' => 'Offline']));

    expect($sync->report()['status'])->toBe('skipped');
    Http::assertNothingSent();
});

// Bulk re-pushes (e.g. many products after a vendor rename) still go through the queue.
it('finishes a queued push through the outbound worker', function () {
    Queue::fake();

    $collection = Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Retry me',
        'is_active' => false,
    ]);
    $outboundId = app(ShopifyAutoSync::class)->queue($this->store->id, $collection, 'create', [], []);

    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => ['id' => 'gid://shopify/Collection/202', 'handle' => 'retry-me'],
            'userErrors' => [],
        ]]],
    ]);

    app(\App\Modules\Shopify\OutboundSync\Services\OutboundSyncProcessor::class)->process($outboundId);

    expect($collection->fresh()->shopify_collection_id)->toBe('gid://shopify/Collection/202')
        ->and(DB::table('shopify_outbound_syncs')->value('status'))->toBe('synced');
});

it('reads the product back from Shopify after saving it', function () {
    fakeShopifyGraphql([
        'productCreate' => ['data' => ['productCreate' => [
            'product' => ['id' => 'gid://shopify/Product/300', 'handle' => 'red-shirt-1'],
            'userErrors' => [],
        ]]],
        'GetProduct' => ['data' => ['product' => [
            'id' => 'gid://shopify/Product/300',
            'title' => 'Red Shirt',
            'handle' => 'red-shirt-1',
            'status' => 'DRAFT',
            'vendor' => '',
            'productType' => '',
            'tags' => [],
            'options' => [],
        ]]],
    ]);

    $sync = app(ShopifyAutoSync::class);
    $product = $sync->create(fn () => Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Red Shirt', 'slug' => 'red-shirt', 'handle' => 'red-shirt',
        'status' => 'draft',
    ]));

    expect(sentShopifyQueries('GetProduct'))->not->toBeEmpty()
        ->and($product->handle)->toBe('red-shirt-1')
        ->and($product->shopify_product_id)->toBe('gid://shopify/Product/300')
        ->and(Product::query()->count())->toBe(1);
});

it('sets inventory with the 2026-04 input (changeFromQuantity null, no ignoreCompareQuantity)', function () {
    fakeShopifyGraphql([
        'inventorySetQuantities' => ['data' => ['inventorySetQuantities' => ['userErrors' => []]]],
    ]);

    (new \App\Modules\Shopify\AutoSync\ShopifyGateway($this->store))
        ->setAvailableQuantity('gid://shopify/InventoryItem/1', 'gid://shopify/Location/2', 7);

    $input = sentShopifyQueries('inventorySetQuantities')[0]['variables']['input'];
    expect($input)->not->toHaveKey('ignoreCompareQuantity')
        ->and($input['quantities'][0])->toHaveKey('changeFromQuantity')
        ->and($input['quantities'][0]['changeFromQuantity'])->toBeNull()
        ->and($input['quantities'][0]['quantity'])->toBe(7);
});

it('waits and retries when Shopify answers THROTTLED instead of failing', function () {
    Http::fake(['*/admin/api/*/graphql.json' => Http::sequence()
        ->push([
            'errors' => [['message' => 'Throttled', 'extensions' => ['code' => 'THROTTLED']]],
            'extensions' => ['cost' => ['requestedQueryCost' => 60, 'throttleStatus' => ['currentlyAvailable' => 10, 'restoreRate' => 100]]],
        ])
        ->push(['data' => ['shop' => ['id' => 'gid://shopify/Shop/1']]])]);

    $response = (new \App\Modules\Shopify\Services\ShopifyClient($this->store))->query('query { shop { id } }');

    expect($response['data']['shop']['id'])->toBe('gid://shopify/Shop/1');
    Http::assertSentCount(2);
});

it('reuses a same-name category instead of breaking the product import', function () {
    \App\Modules\Catalog\Models\Category::query()->create(['store_id' => $this->store->id, 'name' => 'Lipsticks', 'slug' => 'lipsticks']);

    $id = app(\App\Modules\Shopify\Services\Sync\CategorySyncService::class)
        ->sync($this->store, 'gid://shopify/TaxonomyCategory/hb-3-2-7', 'Lipsticks');

    expect(\App\Modules\Catalog\Models\Category::query()->count())->toBe(1)
        ->and(\App\Modules\Catalog\Models\Category::query()->find($id)->shopify_category_id)->toBe('gid://shopify/TaxonomyCategory/hb-3-2-7');
});
