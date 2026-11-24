<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables these routes touch.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('Locale', '0001_01_01_000003_create_countries_table'),
        $m('Locale', '0001_01_01_000004_create_states_table'),
        $m('Locale', '0001_01_01_000005_create_cities_table'),
        $m('Locale', '2025_10_09_220814_create_languages_table'),
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('User', '2026_09_29_120000_allow_self_registration_on_users_table'),
        $m('User', '2025_10_04_075133_create_personal_access_tokens_table'),
        $m('User', '2025_10_11_123012_create_permission_tables'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Stores', '2026_04_24_131615_add_shopify_webhook_secret_to_stores_table'),
        $m('User', '2026_11_20_120000_create_store_team_tables'),
        $m('Core', '2026_03_26_105253_create_sync_runs_table'),
        $m('Core', '2026_03_31_091803_create_sync_jobs_table'),
    ]]);
    Role::findOrCreate('partner', 'web');
    Role::findOrCreate('admin', 'web');
});

function accessUser(string $key, string $role): User
{
    $user = User::create([
        'first_name' => 'U', 'last_name' => $key, 'name' => "user-{$key}",
        'email' => "{$key}@test.com", 'password' => 'x', 'status' => 1,
    ]);
    $user->assignRole($role);

    return $user;
}

function syncRunFor(User $owner): string
{
    $store = Store::query()->create(['id' => (string) Str::uuid(), 'owner_id' => $owner->id, 'name' => "Store {$owner->id}"]);
    DB::table('sync_runs')->insert([
        'store_id' => $store->id, 'type' => 'products', 'trigger' => 'manual', 'status' => 'completed',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return $store->id;
}

dataset('locale admin routes', [
    'list languages' => ['get', 'api/admin/all-languages'],
    'add language' => ['post', 'api/admin/add-language'],
    'add word' => ['post', 'api/admin/add-word/en'],
    'show translation' => ['post', 'api/admin/show-translation/en'],
    'delete language' => ['get', 'api/admin/delete-language/1'],
    'delete languages' => ['post', 'api/admin/delete-languages'],
    'toggle language' => ['get', 'api/admin/changestatus-language/1'],
    'countries' => ['post', 'api/admin/countries'],
    'states' => ['post', 'api/admin/all-states-id/1'],
    'cities' => ['post', 'api/admin/all-cities-id/1'],
]);

it('rejects guests on the language and location admin routes', function (string $method, string $uri) {
    $this->{$method.'Json'}($uri)->assertUnauthorized();
})->with('locale admin routes');

it('rejects partners on the language and location admin routes', function (string $method, string $uri) {
    Sanctum::actingAs(accessUser('partner', 'partner'));
    $this->{$method.'Json'}($uri)->assertForbidden();
})->with('locale admin routes');

it('still lets an admin list languages', function () {
    Sanctum::actingAs(accessUser('admin', 'admin'));
    $this->getJson('api/admin/all-languages')->assertOk();
});

it('keeps the public language and country lists open', function () {
    $this->getJson('api/active-languages')->assertOk();
    $this->getJson('api/countries')->assertOk();
});

it('hides every store\'s sync runs from a partner without a store', function () {
    syncRunFor(accessUser('owner', 'partner'));
    Sanctum::actingAs(accessUser('storeless', 'partner'));

    $this->postJson('api/admin/sync-monitor/runs')->assertNotFound();
});

it('shows a store owner only their own sync runs', function () {
    $mine = syncRunFor($owner = accessUser('mine', 'partner'));
    syncRunFor(accessUser('other', 'partner'));
    Sanctum::actingAs($owner);

    $rows = $this->postJson('api/admin/sync-monitor/runs')->assertOk()->json('data');
    expect($rows)->toHaveCount(1)->and($rows[0]['store_id'])->toBe($mine);
});

it('still shows an admin the sync runs of every store', function () {
    syncRunFor(accessUser('a', 'partner'));
    syncRunFor(accessUser('b', 'partner'));
    Sanctum::actingAs(accessUser('admin', 'admin'));

    $this->postJson('api/admin/sync-monitor/runs')->assertOk()->assertJsonPath('meta.total', 2);
});
