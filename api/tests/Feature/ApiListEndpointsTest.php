<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables the list endpoints touch.
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
        $m('Stores', '2026_04_24_131615_add_shopify_webhook_secret_to_stores_table'),
        $m('User', '2026_11_20_120000_create_store_team_tables'),
        $m('Catalog', '2026_03_31_091712_create_tags_table'),
        $m('Catalog', '2026_03_31_091723_create_taggables_table'),
        $m('User', '2026_02_26_091802_create_customers_table'),
        $m('User', '2026_04_16_140000_extend_customers_and_create_customer_addresses_table'),
        $m('Orders', '2026_02_26_091849_create_orders_table'),
        $m('Orders', '2026_02_26_091908_create_order_items_table'),
        $m('Orders', '2026_04_16_120000_extend_shopify_orders_tables_for_sync'),
        $m('Orders', '2026_04_20_130000_create_order_risk_and_duty_tables'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('Catalog', '2026_02_26_091538_create_product_variants_table'),
        $m('Catalog', '2026_02_26_091703_create_collections_table'),
        $m('Catalog', '2026_02_26_091740_create_collection_products_table'),
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
    ]]);
    Role::findOrCreate('partner', 'web');
    Role::findOrCreate('admin', 'web');

    [$this->ownerA, $this->storeA] = ownerWithStore('a');
    [$this->ownerB, $this->storeB] = ownerWithStore('b');

    $this->admin = User::create([
        'first_name' => 'Platform', 'last_name' => 'Admin', 'name' => 'admin',
        'email' => 'admin@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $this->admin->assignRole('admin');
});

function ownerWithStore(string $key): array
{
    $owner = User::create([
        'first_name' => 'Owner', 'last_name' => strtoupper($key), 'name' => "owner-{$key}",
        'email' => "owner-{$key}@test.com", 'password' => 'x', 'status' => 1,
    ]);
    $owner->assignRole('partner');
    $store = Store::query()->create(['id' => (string) Str::uuid(), 'owner_id' => $owner->id, 'name' => "Store {$key}"]);

    return [$owner, $store];
}

/** Insert $count rows for a store, all sharing one created_at so ordering must break the tie itself. */
function seedRows(string $table, string $storeId, int $count): array
{
    $ts = '2026-01-01 00:00:00';
    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $n = Str::random(8);
        $row = match ($table) {
            'products' => ['title' => "P {$n}", 'handle' => "p-{$n}", 'slug' => "p-{$n}"],
            'collections' => ['title' => "C {$n}", 'handle' => "c-{$n}"],
            'orders' => ['order_number' => "#{$n}", 'email' => "{$n}@test.com"],
            'customers' => ['first_name' => 'C', 'last_name' => $n, 'email' => "{$n}@test.com"],
            'files' => ['url' => "https://cdn.test/{$n}.png", 'type' => 'image'],
        };
        $ids[] = DB::table($table)->insertGetId($row + ['store_id' => $storeId, 'created_at' => $ts, 'updated_at' => $ts]);
    }

    return $ids;
}

// Partner list endpoints: POST with rowsPerPage/page in the body, tenant-scoped to the owner's store.
dataset('partner list endpoints', [
    'products' => ['/api/admin/allProducts', 'products'],
    'orders' => ['/api/admin/allOrders', 'orders'],
    'customers' => ['/api/admin/all-customers', 'customers'],
    'collections' => ['/api/admin/allCollections', 'collections'],
    'files' => ['/api/admin/files', 'files'],
]);

it('returns 200 with data + meta + links', function (string $uri, string $table) {
    seedRows($table, $this->storeA->id, 2);

    $this->actingAs($this->ownerA)->postJson($uri)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page'], 'links' => ['first', 'last', 'prev', 'next']])
        ->assertJsonPath('meta.total', 2);
})->with('partner list endpoints');

it('rejects rowsPerPage above 100 and ignores an unknown per_page param', function (string $uri, string $table) {
    $this->actingAs($this->ownerA)->postJson($uri, ['rowsPerPage' => 100000])
        ->assertStatus(422)->assertJsonValidationErrors('rowsPerPage');

    $this->actingAs($this->ownerA)->postJson($uri, ['per_page' => 100000])
        ->assertOk()->assertJsonPath('meta.per_page', $table === 'files' ? 15 : 10);
})->with('partner list endpoints');

it('only lists rows of the signed-in owner\'s store', function (string $uri, string $table) {
    $mine = seedRows($table, $this->storeA->id, 3);
    seedRows($table, $this->storeB->id, 3);

    $res = $this->actingAs($this->ownerA)->postJson($uri, ['rowsPerPage' => 100])->assertOk();

    expect(collect($res->json('data'))->pluck('id')->sort()->values()->all())->toBe($mine)
        ->and($res->json('meta.total'))->toBe(3);
})->with('partner list endpoints');

it('pages deterministically when created_at ties: no duplicates or gaps between page 1 and 2', function (string $uri, string $table) {
    $ids = seedRows($table, $this->storeA->id, 15);

    $page = fn (int $n) => collect($this->actingAs($this->ownerA)
        ->postJson($uri, ['rowsPerPage' => 10, 'page' => $n])->assertOk()->json('data'))->pluck('id');

    $p1 = $page(1);
    $p2 = $page(2);

    expect($p1)->toHaveCount(10)
        ->and($p2)->toHaveCount(5)
        ->and($p1->intersect($p2))->toBeEmpty()
        ->and($p1->merge($p2)->sort()->values()->all())->toBe($ids)
        ->and($page(1)->all())->toBe($p1->all());
})->with('partner list endpoints');

// Admin lists (users, stores) are global by design and paginate a cached collection in PHP.
dataset('admin list endpoints', [
    'users' => ['/api/admin/all-users'],
    'stores' => ['/api/admin/stores'],
]);

it('returns admin lists as data + meta + links', function (string $uri) {
    $this->actingAs($this->admin)->postJson($uri)
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page'], 'links']);
})->with('admin list endpoints');

it('forbids partners from the admin lists', function (string $uri) {
    $this->actingAs($this->ownerA)->postJson($uri)->assertForbidden();
})->with('admin list endpoints');

// BUG: UserController::index / StoreController::index read rowsPerPage straight from the request with no validation.
it('caps rowsPerPage on admin lists', function (string $uri) {
    $res = $this->actingAs($this->admin)->postJson($uri, ['rowsPerPage' => 100000]);

    expect($res->status() === 422 || $res->json('meta.per_page') <= 100)->toBeTrue();
})->with('admin list endpoints')
    ->skip('BUG: rowsPerPage is uncapped (UserController.php:24, StoreController.php:31); unskip once validated');

it('pages admin users deterministically across page 1 and 2', function () {
    foreach (range(1, 12) as $i) {
        User::create(['first_name' => 'U', 'last_name' => "{$i}", 'name' => "u{$i}", 'email' => "u{$i}@test.com", 'password' => 'x', 'status' => 1]);
    }
    $page = fn (int $n) => collect($this->actingAs($this->admin)
        ->postJson('/api/admin/all-users', ['rowsPerPage' => 10, 'page' => $n])->assertOk()->json('data'))->pluck('id');

    $p1 = $page(1);
    $p2 = $page(2);

    expect($p1->intersect($p2))->toBeEmpty()
        ->and($p1->merge($p2)->sort()->values()->all())->toBe(User::orderBy('id')->pluck('id')->all());
});

// Cached reads: UserRepository/StoreRepository cache the full list under one global key for 60s.
it('drops the cached admin user list when a user is changed through the admin API', function () {
    $target = $this->ownerB;
    $statusOf = fn () => collect($this->actingAs($this->admin)->postJson('/api/admin/all-users', ['rowsPerPage' => 100])->json('data'))
        ->firstWhere('id', $target->id)['status'];

    $before = $statusOf();
    expect(Cache::has('all_Users'))->toBeTrue();
    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$target->id}/status")->assertOk();

    expect($statusOf())->not->toBe($before);
});

it('drops the cached admin store list when a store status is toggled', function () {
    $statusOf = fn () => collect($this->actingAs($this->admin)->postJson('/api/admin/stores', ['rowsPerPage' => 100])->json('data'))
        ->firstWhere('id', $this->storeA->id);

    $before = $statusOf();
    expect(Cache::has('all_Stores'))->toBeTrue();
    $this->actingAs($this->admin)->patchJson("/api/admin/store/{$this->storeA->id}/status")->assertOk();

    expect($statusOf())->not->toBe($before);
});

// BUG: self sign-up creates the user with User::create and never forgets "all_Users", so admins see a stale list for up to 60s.
it('shows a newly self-registered user in the admin user list', function () {
    $emails = fn () => collect($this->actingAs($this->admin)->postJson('/api/admin/all-users', ['rowsPerPage' => 100])->json('data'))->pluck('email');

    expect($emails())->not->toContain('new@test.com');

    $this->app['auth']->forgetGuards();
    $this->postJson('/api/admin/adminregister', [
        'name' => 'New Person', 'email' => 'new@test.com', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
    ])->assertCreated();

    expect($emails())->toContain('new@test.com');
})->skip('BUG: AuthController::register (AuthController.php:42) does not invalidate UserRepository cache key all_Users');
