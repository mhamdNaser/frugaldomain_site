<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Billing\Models\Plan;
use App\Modules\Catalog\Models\Tag;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use App\Modules\User\Models\StoreRole;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
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
        $m('User', '2026_11_20_120000_create_store_team_tables'),
        $m('Catalog', '2026_03_31_091712_create_tags_table'),
        $m('Catalog', '2026_03_31_091723_create_taggables_table'),
        $m('User', '2026_02_26_091802_create_customers_table'),
        $m('User', '2026_04_16_140000_extend_customers_and_create_customer_addresses_table'),
        $m('Orders', '2026_02_26_091849_create_orders_table'),
        $m('User', '2026_04_20_130300_create_customer_marketing_consents_table'),
        $m('Billing', '2026_02_26_091421_create_subscriptions_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('Catalog', '2026_02_26_091703_create_collections_table'),
        $m('Orders', '2026_03_31_091528_create_draft_orders_table'),
        $m('Orders', '2026_03_31_091541_create_draft_order_items_table'),
        $m('Orders', '2026_04_16_120001_extend_shopify_draft_orders_tables_for_sync'),
        $m('Orders', '2026_02_26_091803_create_carts_table'),
        $m('Core', '2026_02_26_092042_create_sync_metas_table'),
        $m('Core', '2026_03_26_105253_create_sync_runs_table'),
        $m('Catalog', '2026_02_26_091538_create_product_variants_table'),
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
        $m('Inventory', '2026_02_27_135717_create_locations_table'),
        $m('Inventory', '2026_03_25_141614_create_inventory_levels_table'),
        $m('Inventory', '2026_04_20_130200_extend_inventory_levels_for_stateful_quantities'),
    ]]);
    Role::findOrCreate('partner', 'web');

    $this->owner = User::create([
        'first_name' => 'Store', 'last_name' => 'Owner', 'name' => 'owner',
        'email' => 'owner@test.com', 'password' => 'secret-123', 'status' => 1,
    ]);
    $this->owner->assignRole('partner');

    $this->plan = Plan::query()->create(['name' => 'Pro', 'price' => 10, 'billing_interval' => 'month', 'is_active' => true, 'max_team_members' => 2]);
    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $this->owner->id,
        'plan_id' => $this->plan->id,
        'name' => 'Demo',
    ]);
});

/** A staff member of the test store holding one role with the given permissions. */
function staffWith(array $permissions, $test): User
{
    $role = StoreRole::query()->create(['store_id' => $test->store->id, 'name' => 'Role ' . Str::random(4), 'permissions' => $permissions]);
    $user = User::create([
        'store_id' => $test->store->id, 'first_name' => 'Staff', 'last_name' => 'Member',
        'name' => 'staff-' . Str::random(4) . '@test.com', 'email' => Str::random(6) . '@test.com', 'password' => 'secret-123', 'status' => 1,
    ]);
    $user->assignRole('partner');
    $user->storeRoles()->attach($role);

    return $user;
}

it('lets the owner create a role and add members up to the plan limit', function () {
    $this->actingAs($this->owner)->postJson('/api/admin/team/roles', [
        'name' => 'Catalog editor',
        'permissions' => ['tags.view', 'tags.edit', 'fields.products.prices'],
    ])->assertCreated();
    $roleId = StoreRole::query()->value('id');

    foreach (['a@team.com', 'b@team.com'] as $email) {
        $this->actingAs($this->owner)->postJson('/api/admin/team/members', [
            'first_name' => 'Team', 'last_name' => 'Member', 'email' => $email,
            'password' => 'password-1', 'role_ids' => [$roleId],
        ])->assertCreated();
    }

    $this->actingAs($this->owner)->postJson('/api/admin/team/members', [
        'first_name' => 'One', 'last_name' => 'Too many', 'email' => 'c@team.com', 'password' => 'password-1',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    $member = User::query()->where('email', 'a@team.com')->sole();
    expect($member->store->id)->toBe($this->store->id)
        ->and($member->storePermissions())->toContain('tags.edit')
        ->and($this->owner->fresh()->store->id)->toBe($this->store->id);

    $this->actingAs($this->owner)->getJson('/api/admin/team/members')
        ->assertOk()->assertJsonPath('meta.used', 2)->assertJsonPath('meta.max', 2);
});

it('rejects unknown permission keys on a role', function () {
    $this->actingAs($this->owner)->postJson('/api/admin/team/roles', ['name' => 'Hacker', 'permissions' => ['everything.all']])
        ->assertStatus(422)->assertJsonValidationErrors('permissions.0');
});

it('lets staff do exactly what their roles allow', function () {
    Tag::query()->create(['store_id' => $this->store->id, 'name' => 'Sale', 'slug' => 'sale']);
    $staff = staffWith(['tags.view'], $this);

    $this->actingAs($staff)->postJson('/api/admin/allTags')->assertOk()->assertJsonPath('data.0.name', 'Sale');
    $this->actingAs($staff)->deleteJson('/api/admin/tags/' . Tag::query()->value('id'))->assertForbidden();
    $this->actingAs($staff)->postJson('/api/admin/all-customers')->assertForbidden();
    $this->actingAs($staff)->getJson('/api/admin/team/members')->assertForbidden();

    expect(Tag::query()->count())->toBe(1);
});

it('hides customer contact info from staff without that field', function () {
    $customer = Customer::query()->create([
        'store_id' => $this->store->id, 'first_name' => 'Rana', 'last_name' => 'K',
        'email' => 'rana@shop.com', 'phone' => '+963900000000',
    ]);
    $staff = staffWith(['customers.view', 'customers.edit'], $this);

    $row = $this->actingAs($staff)->postJson('/api/admin/all-customers')->assertOk()->json('data.0');
    expect($row)->not->toHaveKey('email')->not->toHaveKey('phone')->and($row['first_name'])->toBe('Rana');

    $withContact = staffWith(['customers.view', 'fields.customers.contact'], $this);
    expect($this->actingAs($withContact)->postJson('/api/admin/all-customers')->json('data.0.email'))->toBe('rana@shop.com');
});

it('blocks a disabled staff member at login and on every call', function () {
    $staff = staffWith(['tags.view'], $this);
    $staff->update(['status' => 0]);

    $this->postJson('/api/login', ['email' => $staff->email, 'password' => 'secret-123'])->assertForbidden();
    $this->actingAs($staff)->postJson('/api/admin/allTags')->assertForbidden();
});

it('keeps the owner on the store when staff update the connection', function () {
    $this->store->update(['shopify_domain' => 'demo.myshopify.com', 'shopify_access_token' => Crypt::encryptString('shpat_old')]);
    $staff = staffWith(['store-connection.edit'], $this);
    app()->instance(\App\Modules\Stores\Services\ShopifyConnectionService::class, Mockery::mock(\App\Modules\Stores\Services\ShopifyConnectionService::class, function ($mock) {
        $mock->shouldReceive('verify')->andReturn([
            'shop' => ['id' => 1, 'name' => 'Demo', 'email' => 'shop@test.com', 'domain' => 'demo.myshopify.com', 'currency' => 'USD', 'timezone' => 'UTC'],
            'scopes' => [], 'missing_required' => [], 'missing_recommended' => [],
        ]);
    }));

    $this->actingAs($staff)->postJson('/api/admin/my-store/connect', ['shopify_domain' => 'demo.myshopify.com'])->assertSuccessful();

    expect($this->store->fresh()->owner_id)->toBe($this->owner->id);
});

it('drops hidden fields from what staff send, so they cannot change them', function () {
    $product = \App\Modules\Catalog\Models\Product::query()->create(['store_id' => $this->store->id, 'title' => 'Cap', 'slug' => 'cap', 'handle' => 'cap']);
    $variant = \App\Modules\Catalog\Models\ProductVariant::query()->create(['store_id' => $this->store->id, 'product_id' => $product->id, 'title' => 'Default', 'shopify_variant_id' => 'gid://shopify/ProductVariant/1']);
    $level = \App\Modules\Inventory\Models\InventoryLevel::query()->create([
        'store_id' => $this->store->id, 'product_variant_id' => $variant->id, 'inventory_item_id' => 'gid://shopify/InventoryItem/1', 'shopify_location_id' => 'gid://shopify/Location/1', 'available' => 5,
    ]);
    $staff = staffWith(['inventory.view', 'inventory.edit'], $this);

    $this->actingAs($staff)->putJson("/api/admin/inventories/{$level->id}", [
        'available' => 999,
        'shopify_location_id' => 'gid://shopify/Location/2',
    ])->assertOk()->assertJsonMissingPath('data.available');

    expect($level->fresh()->available)->toBe(5)
        ->and($level->fresh()->shopify_location_id)->toBe('gid://shopify/Location/2');
});

it('lets the super admin create, edit and delete plans with their details', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::create(['first_name' => 'Super', 'last_name' => 'Admin', 'name' => 'admin', 'email' => 'admin@test.com', 'password' => 'secret-123', 'status' => 1]);
    $admin->assignRole('admin');

    $id = $this->actingAs($admin)->postJson('/api/admin/plans', [
        'name' => 'Growth', 'description' => 'For growing stores', 'price' => 29, 'billing_interval' => 'month',
        'trial_days' => 14, 'max_team_members' => 5, 'features' => ['Up to 5 team members', 'Priority support'],
    ])->assertCreated()->assertJsonPath('data.max_team_members', 5)->json('data.id');

    $this->actingAs($admin)->putJson("/api/admin/plans/{$id}", ['max_team_members' => 8, 'features' => ['Everything']])
        ->assertOk()->assertJsonPath('data.max_team_members', 8)->assertJsonPath('data.features', ['Everything']);

    // The test store is on $this->plan, so it cannot be deleted; the new one can.
    $this->actingAs($admin)->deleteJson("/api/admin/plans/{$this->plan->id}")->assertStatus(422);
    $this->actingAs($admin)->deleteJson("/api/admin/plans/{$id}")->assertOk();
    expect(Plan::query()->find($id))->toBeNull();

    $this->actingAs($this->owner)->postJson('/api/admin/plans', ['name' => 'Hack', 'price' => 0, 'billing_interval' => 'month'])->assertForbidden();
});

it('never lets a staff member send raw Shopify queries with the owner\'s token', function () {
    $staff = staffWith(['tags.view', 'tags.create', 'tags.edit'], $this);

    $this->actingAs($staff)->postJson('/api/admin/tags', [
        'store_id' => $this->store->id, 'name' => 'x', 'slug' => 'x',
        'shopify_sync' => ['query' => '{ customers(first: 5) { edges { node { email } } } }'],
    ])->assertForbidden();
});
