<?php

use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function apUser(string $role, string $name = null): User
{
    Role::findOrCreate($role, 'web');
    $u = User::create([
        'first_name' => 'P', 'last_name' => 'T', 'name' => $name ?? $role.'-'.Str::random(5),
        'email' => Str::random(8).'@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $u->assignRole($role);

    return $u;
}

beforeEach(function () {
    $this->admin = apUser('admin');
});

it('lists store owners with their store, with and without one', function () {
    $withStore = apUser('partner', 'Rami Store');
    Store::query()->create(['id' => (string) Str::uuid(), 'owner_id' => $withStore->id, 'shopify_domain' => 'rami.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('t'), 'name' => 'Rami']);
    apUser('partner', 'Lina NoStore');
    apUser('admin', 'Not A Partner');

    $res = $this->actingAs($this->admin)->getJson('/api/admin/partners')->assertOk();

    expect(collect($res->json('data'))->pluck('name')->sort()->values()->all())->toBe(['Lina NoStore', 'Rami Store'])
        ->and(collect($res->json('data'))->firstWhere('name', 'Rami Store')['store_domain'])->toBe('rami.myshopify.com')
        ->and($res->json('meta.total'))->toBe(2);

    $this->actingAs($this->admin)->getJson('/api/admin/partners?search=lina')->assertJsonCount(1, 'data');
});

it('lets the admin add a partner who can then sign in', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/partners', [
        'first_name' => 'Sara', 'last_name' => 'Aziz', 'email' => 'sara@shop.test', 'password' => 'Secret-pass-123',
    ])->assertCreated();

    $u = User::where('email', 'sara@shop.test')->firstOrFail();
    expect($u->hasRole('partner'))->toBeTrue()
        ->and(Hash::check('Secret-pass-123', $u->password))->toBeTrue();

    $this->actingAs($this->admin)->postJson('/api/admin/partners', [
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'sara@shop.test', 'password' => 'Secret-pass-123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('keeps the partners list for the super admin only', function () {
    $partner = apUser('partner');

    $this->actingAs($partner)->getJson('/api/admin/partners')->assertForbidden();
    $this->actingAs($partner)->postJson('/api/admin/partners', [])->assertForbidden();
});
