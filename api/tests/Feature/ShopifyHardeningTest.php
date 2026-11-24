<?php

use App\Modules\Shopify\Webhooks\Jobs\ProcessShopifyWebhookJob;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'partner'] as $r) {
        Role::findOrCreate($r, 'web');
    }
    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'shopify_domain' => 'hard.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_TESTTOKEN'),
        'shopify_webhook_secret' => 'whsec_test',
        'name' => 'Hard',
    ]);
});

function hardeningAdmin(): User
{
    $u = User::create([
        'first_name' => 'A', 'last_name' => 'D', 'name' => 'admin-'.Str::random(5),
        'email' => Str::random(6).'@test.com', 'password' => 'secret-pass', 'status' => 1,
    ]);
    $u->assignRole('admin');

    return $u;
}

function postWebhook($test, string $shop, string $secret, string $body = '{"id":1}')
{
    return $test->call('POST', '/api/shopify/webhooks', [], [], [], [
        'HTTP_X_SHOPIFY_TOPIC' => 'products/update',
        'HTTP_X_SHOPIFY_SHOP_DOMAIN' => $shop,
        'HTTP_X_SHOPIFY_WEBHOOK_ID' => (string) Str::uuid(),
        'HTTP_X_SHOPIFY_HMAC_SHA256' => base64_encode(hash_hmac('sha256', $body, $secret, true)),
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

it('rejects a webhook with a bad signature and queues nothing', function () {
    Queue::fake();
    postWebhook($this, 'hard.myshopify.com', 'wrong-secret')->assertStatus(401);
    Queue::assertNothingPushed();
});

it('rejects a webhook for an unknown shop and queues nothing', function () {
    Queue::fake();
    postWebhook($this, 'nobody.myshopify.com', 'whsec_test')->assertStatus(401);
    Queue::assertNothingPushed();
});

it('accepts a valid webhook and queues exactly one job', function () {
    Queue::fake();
    postWebhook($this, 'hard.myshopify.com', 'whsec_test')->assertOk();
    Queue::assertPushed(ProcessShopifyWebhookJob::class, 1);
});

it('never puts a shopify token or a password hash in the cache', function () {
    config(['cache.default' => 'database']);
    $admin = hardeningAdmin();
    $this->actingAs($admin)->postJson('/api/admin/stores')->assertOk();
    $this->actingAs($admin)->postJson('/api/admin/all-users')->assertOk();

    $values = DB::table('cache')->pluck('value')->implode("\n");
    $encrypted = $this->store->getRawOriginal('shopify_access_token');

    expect($values)->not->toContain('shpat_TESTTOKEN')
        ->and($values)->not->toContain((string) $encrypted)
        ->and($values)->not->toContain('$2y$');
});

it('does not wipe the whole cache when roles change', function () {
    $admin = hardeningAdmin();
    Cache::put('unrelated_sentinel', 'keep-me', 600);
    $role = Role::findOrCreate('editor', 'web');

    $this->actingAs($admin)->postJson("/api/admin/update-role-permissions/{$role->id}", ['permissions' => []]);
    $this->actingAs($admin)->deleteJson("/api/admin/roles/{$role->id}");

    expect(Cache::get('unrelated_sentinel'))->toBe('keep-me');
});

function hardeningPartnerWithStore(string $domain): array
{
    $u = User::create([
        'first_name' => 'P', 'last_name' => 'O', 'name' => 'owner-'.Str::random(5),
        'email' => Str::random(6).'@test.com', 'password' => 'secret-pass', 'status' => 1,
    ]);
    $u->assignRole('partner');
    $store = Store::query()->create([
        'id' => (string) Str::uuid(), 'owner_id' => $u->id, 'shopify_domain' => $domain,
        'shopify_access_token' => Crypt::encryptString('shpat_'.Str::random(6)), 'name' => $domain,
    ]);

    return [$u, $store];
}

it('stops a store owner from deleting another store\'s product or reaching its Shopify', function () {
    \Illuminate\Support\Facades\Http::fake();
    [$attacker] = hardeningPartnerWithStore('attacker.myshopify.com');
    $victim = \App\Modules\Catalog\Models\Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Victim', 'slug' => 'victim', 'handle' => 'victim',
    ]);

    $this->actingAs($attacker)->deleteJson("/api/admin/products/{$victim->id}", [
        'shopify_sync' => ['query' => '{ customers(first: 5) { edges { node { email } } } }'],
    ])->assertNotFound();

    expect(\App\Modules\Catalog\Models\Product::query()->whereKey($victim->id)->exists())->toBeTrue();
    \Illuminate\Support\Facades\Http::assertNothingSent();
});

it('creates a store owner\'s product in their own store whatever store_id they send', function () {
    \Illuminate\Support\Facades\Http::fake();
    [$owner, $own] = hardeningPartnerWithStore('own.myshopify.com');

    $this->actingAs($owner)->postJson('/api/admin/products', [
        'store_id' => $this->store->id, 'title' => 'Mine', 'slug' => 'mine', 'handle' => 'mine',
    ]);

    expect(\App\Modules\Catalog\Models\Product::query()->where('store_id', $this->store->id)->where('title', 'Mine')->exists())->toBeFalse();
});
