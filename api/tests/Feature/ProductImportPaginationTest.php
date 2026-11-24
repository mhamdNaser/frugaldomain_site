<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\Jobs\Pagination\SyncProductsPaginationOrchestratorJob;
use App\Modules\Shopify\Services\Sync\SyncRunTracker;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('Core', '0001_01_01_000002_create_jobs_table'),
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Core', '2026_02_26_092042_create_sync_metas_table'),
        $m('Core', '2026_03_26_105253_create_sync_runs_table'),
        $m('Core', '2026_03_31_091803_create_sync_jobs_table'),
        $m('Core', '2026_03_31_091814_create_sync_errors_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091520_create_options_table'),
        $m('Catalog', '2026_02_26_091521_create_option_values_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('Catalog', '2026_03_31_091008_create_product_options_table'),
        $m('Catalog', '2026_03_31_091712_create_tags_table'),
        $m('Catalog', '2026_03_31_091723_create_taggables_table'),
    ]]);

    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'shopify_domain' => 'demo.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_test'),
        'name' => 'Demo',
    ]);
});

function productPage(int $id, bool $hasNext): array
{
    return ['data' => ['products' => [
        'edges' => [['cursor' => "c{$id}", 'node' => [
            'id' => "gid://shopify/Product/{$id}", 'title' => "Product {$id}", 'handle' => "product-{$id}",
            'status' => 'ACTIVE', 'vendor' => 'Velvet Ritual', 'productType' => 'Lipstick', 'tags' => ['lip'],
            'options' => [], 'category' => ['id' => 'gid://shopify/TaxonomyCategory/aa', 'name' => 'Lipsticks'],
        ]]],
        'pageInfo' => ['hasNextPage' => $hasNext, 'hasPreviousPage' => false, 'endCursor' => "c{$id}", 'startCursor' => "c{$id}"],
    ]]];
}

it('imports every page: each page job queues the next one until Shopify says there are no more', function () {
    Http::fake(['*/admin/api/*/graphql.json' => function (Request $request) {
        $after = $request['variables']['after'] ?? null;

        return Http::response(productPage($after === 'c1' ? 2 : 1, hasNext: $after !== 'c1'));
    }]);
    Queue::fake();

    $runId = app(SyncRunTracker::class)->startRun($this->store->id, 'products');

    // Page 1, then run whatever page job it queued (page 2) exactly like the worker would.
    app()->call([new SyncProductsPaginationOrchestratorJob($this->store->id, $runId, 20), 'handle']);

    $next = null;
    Queue::assertPushed(SyncProductsPaginationOrchestratorJob::class, function ($job) use (&$next) {
        $next = $job;

        return $job->after === 'c1';
    });

    app()->call([$next, 'handle']);

    expect(Product::query()->count())->toBe(2)
        ->and(DB::table('sync_runs')->where('id', $runId)->value('status'))->toBe('completed')
        ->and((int) DB::table('sync_runs')->where('id', $runId)->value('fetched_count'))->toBe(2);
});

it('records how many items each section imported, so an empty section shows 0 instead of nothing', function () {
    $tracker = app(SyncRunTracker::class);

    foreach (['discounts' => [\App\Modules\Shopify\Jobs\SyncDiscountsJob::class, \App\Modules\Shopify\Services\Sync\DiscountsSyncService::class, 0],
              'orders' => [\App\Modules\Shopify\Jobs\SyncOrdersJob::class, \App\Modules\Shopify\Services\Sync\OrdersSyncService::class, 7]] as $type => [$job, $service, $count]) {
        $this->mock($service)->shouldReceive('sync')->andReturn($count);
        $runId = $tracker->startRun($this->store->id, $type);

        app()->call([new $job($this->store->id, $runId), 'handle']);

        $run = DB::table('sync_runs')->find($runId);
        expect($run->status)->toBe('completed')
            ->and((int) $run->fetched_count)->toBe($count);
    }
});
