<?php

use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'partner', 'user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

function siteAccount(array $extra = []): User
{
    $n = Str::random(8);

    return User::create(array_merge([
        'first_name' => 'Site', 'last_name' => 'User', 'name' => $n,
        'email' => "{$n}@test.com", 'password' => 'x', 'status' => 1,
    ], $extra));
}

it('turns a FrugalDomain account into a store owner when they enable Shopify', function () {
    $user = siteAccount();

    $this->actingAs($user)->postJson('/api/account/shopify/enable')->assertOk()->assertJsonPath('data.is_partner', true);

    expect($user->fresh()->hasRole('partner'))->toBeTrue();
});

it('is idempotent and keeps existing roles', function () {
    $user = siteAccount();
    $user->assignRole('user');

    $this->actingAs($user)->postJson('/api/account/shopify/enable')->assertOk();
    $this->actingAs($user)->postJson('/api/account/shopify/enable')->assertOk();

    expect($user->fresh()->getRoleNames()->sort()->values()->all())->toBe(['partner', 'user']);
});

it('rejects guests', function () {
    $this->postJson('/api/account/shopify/enable')->assertUnauthorized();
});

it('lets an enabled account read its own store', function () {
    $user = siteAccount();
    $this->actingAs($user)->postJson('/api/account/shopify/enable')->assertOk();
    $store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $user->id,
        'shopify_domain' => 'flow-test.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_flow'),
        'name' => 'Flow',
    ]);

    $this->actingAs($user->fresh())->getJson('/api/admin/my-store')->assertOk()->assertJsonPath('data.id', $store->id);
});

it('keeps admin-only routes forbidden to store staff', function () {
    $owner = siteAccount();
    $owner->assignRole('partner');
    $store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $owner->id,
        'shopify_domain' => 'staff-test.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_staff'),
        'name' => 'Staff',
    ]);
    $staff = siteAccount(['store_id' => $store->id]);
    $staff->assignRole('partner');

    $this->actingAs($staff)->postJson('/api/admin/allPlans')->assertForbidden();
});

it('still lets an account that enabled Shopify but never connected a store delete itself', function () {
    $user = siteAccount(['password' => bcrypt('secret-pass')]);
    $this->actingAs($user)->postJson('/api/account/shopify/enable')->assertOk();

    $this->actingAs($user->fresh())->deleteJson('/api/account', ['password' => 'secret-pass'])->assertOk();
    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('keeps blocking self-deletion for a store owner with a store', function () {
    $user = siteAccount(['password' => bcrypt('secret-pass')]);
    $user->assignRole('partner');
    Store::query()->create([
        'id' => (string) Str::uuid(), 'owner_id' => $user->id, 'shopify_domain' => 'keep.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_keep'), 'name' => 'Keep',
    ]);

    $this->actingAs($user)->deleteJson('/api/account', ['password' => 'secret-pass'])->assertForbidden();
});

it('gives every store on the free plan room for a team', function () {
    expect(app(\App\Modules\Billing\Services\FreePlanService::class)->plan()->max_team_members)->toBeGreaterThanOrEqual(5);
});

it('has unique route names so route:cache works', function () {
    $names = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())->map->getName()->filter();
    expect($names->duplicates()->values()->all())->toBe([]);
});
