<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\CMS\Models\File;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

// The reset itself is checked against MySQL (it spans ~50 tables); this covers the guard.
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
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
    ]]);
    Role::findOrCreate('partner', 'web');

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

    File::query()->create([
        'store_id' => $this->store->id, 'disk' => 'shopify', 'type' => 'image',
        'path' => 'https://cdn.shopify.com/a.png', 'url' => 'https://cdn.shopify.com/a.png',
    ]);
});

it('clears nothing and starts no import when Shopify cannot be reached', function () {
    Bus::fake();
    Http::fake(['*' => Http::response('Service unavailable', 503)]);

    $this->actingAs($this->owner)
        ->postJson('/api/admin/sync/bootstrap', ['preset' => 'full_commerce'])
        ->assertStatus(503)
        ->assertJsonPath('message', 'Shopify could not be reached, so nothing was cleared. Please try again.');

    expect(File::query()->count())->toBe(1);
    Bus::assertNothingDispatched();
});
