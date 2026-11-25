<?php

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Market\Models\MarketItem;
use App\Modules\Market\Models\MarketPurchase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function mkUser(string $role = 'user'): User
{
    Role::findOrCreate($role, 'web');
    $u = User::create([
        'first_name' => 'M', 'last_name' => 'K', 'name' => $role.'-'.Str::random(5),
        'email' => Str::random(8).'@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $u->assignRole($role);

    return $u;
}

function mkDownload($test, User $user, string $slug)
{
    $url = $test->actingAs($user)->postJson("/api/market/items/{$slug}/download-link")->assertOk()->json("data.url");

    return $test->get($url);
}

function mkItem(array $extra = []): MarketItem
{
    Storage::disk('local')->put('market/test/theme.zip', 'ZIPDATA');

    return MarketItem::create(array_merge([
        'type' => 'theme', 'slug' => 'aurora', 'name' => 'Aurora', 'summary' => 'A clean theme',
        'readme' => '# Aurora', 'price' => 49, 'status' => 'published', 'source' => 'upload',
        'file_path' => 'market/test/theme.zip', 'preview_url' => 'https://aurora-demo.myshopify.com',
        'preview_password' => 'demo123', 'features' => ['Fast', 'RTL ready'],
    ], $extra));
}

beforeEach(function () {
    Storage::fake('local');
    config(['app.frontend_url' => 'https://front.test']);
    $this->admin = mkUser('admin');
    $this->buyer = mkUser();
});

it('lets the admin create a theme, upload its zip and logo, and publish it', function () {
    $id = $this->actingAs($this->admin)->postJson('/api/admin/market/items', [
        'type' => 'theme', 'slug' => 'nova', 'name' => 'Nova', 'summary' => 'Bold', 'readme' => '# Nova',
        'price' => 39, 'status' => 'published', 'source' => 'upload', 'preview_url' => 'https://nova.myshopify.com',
        'preview_password' => 'pw', 'features' => ['One', 'Two'],
        'translations' => ['ar' => ['name' => 'نوفا', 'summary' => 'جريء']],
        'seo_title' => 'Nova Shopify theme', 'seo_description' => 'A bold theme',
    ])->assertCreated()->json('data.id');

    $this->actingAs($this->admin)->post("/api/admin/market/items/{$id}/file", ['file' => UploadedFile::fake()->create('nova.zip', 50, 'application/zip')], ['Accept' => 'application/json'])->assertOk();
    $this->actingAs($this->admin)->post("/api/admin/market/items/{$id}/logo", ['logo' => UploadedFile::fake()->create('logo.png', 20, 'image/png')], ['Accept' => 'application/json'])->assertOk();

    $this->getJson('/api/market/items?type=theme')->assertOk()->assertJsonPath('data.0.slug', 'nova')
        ->assertJsonPath('data.0.translations.ar.name', 'نوفا');
    $this->getJson('/api/market/items?type=app')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/market/items/nova')->assertOk()->assertJsonPath('data.preview_password', 'pw')
        ->assertJsonPath('data.features.1', 'Two')->assertJsonPath('data.owned', false);
});

it('hides drafts and never shows the file path or GitHub token', function () {
    mkItem(['slug' => 'draft-one', 'status' => 'draft']);
    mkItem(['slug' => 'gh', 'source' => 'github', 'github_repo' => 'me/theme', 'github_token' => 'ghp_SECRET']);

    $this->getJson('/api/market/items/draft-one')->assertNotFound();
    $res = $this->getJson('/api/market/items/gh')->assertOk();
    expect($res->getContent())->not->toContain('ghp_SECRET')->not->toContain('market/test')->not->toContain('me/theme');
    expect($this->actingAs($this->admin)->getJson('/api/admin/market/items')->getContent())->not->toContain('ghp_SECRET');
});

it('asks for an account before checkout and download', function () {
    mkItem();
    $this->postJson('/api/market/items/aurora/checkout', [])->assertUnauthorized();
    $this->postJson('/api/market/items/aurora/download-link')->assertUnauthorized();
    $this->get('/api/market/items/aurora/download')->assertForbidden(); // unsigned
});

it('gives a free item at once and lets the buyer download it', function () {
    mkItem(['price' => 0]);

    $this->actingAs($this->buyer)->postJson('/api/market/items/aurora/checkout', [])->assertOk()->assertJsonPath('data.type', 'owned');
    $res = mkDownload($this, $this->buyer, 'aurora')->assertOk();
    expect($res->streamedContent())->toBe('ZIPDATA');
});

it('sells through Sham Cash: proof, admin confirmation, then the download', function () {
    $item = mkItem();
    $m = PaymentMethod::create(['name' => 'Sham Cash', 'driver' => 'shamcash', 'config' => ['account_code' => 'SC-9'], 'is_active' => true]);

    $res = $this->actingAs($this->buyer)->postJson('/api/market/items/aurora/checkout', ['payment_method_id' => $m->id])
        ->assertOk()->assertJsonPath('data.type', 'instructions')->assertJsonPath('data.account.code', 'SC-9')->assertJsonPath('data.amount', '49.00');
    $pid = $res->json('data.payable_id');

    $this->actingAs($this->buyer)->postJson('/api/market/items/aurora/download-link')->assertForbidden();
    $this->actingAs($this->buyer)->postJson("/api/market/purchases/{$pid}/proof", ['reference' => 'TX-1'])->assertOk();

    $this->actingAs($this->admin)->getJson('/api/admin/market/purchases')->assertOk()->assertJsonPath('data.0.reference', 'TX-1');
    $this->actingAs($this->admin)->postJson("/api/admin/market/purchases/{$pid}/confirm")->assertOk();

    expect(MarketPurchase::find($pid)->status)->toBe('paid');
    mkDownload($this, $this->buyer, 'aurora')->assertOk();
    $this->actingAs($this->buyer)->getJson('/api/account/purchases')->assertOk()->assertJsonPath('data.0.item.slug', 'aurora')
        ->assertJsonPath('data.0.status', 'paid');
    $this->actingAs($this->buyer)->getJson('/api/market/items/aurora')->assertJsonPath('data.owned', true);
    $this->actingAs($this->buyer)->postJson('/api/market/items/aurora/checkout', ['payment_method_id' => $m->id])->assertStatus(422);
});

it('sells through Stripe and returns the buyer to the purchase page', function () {
    mkItem();
    $m = PaymentMethod::create(['name' => 'Card', 'driver' => 'stripe', 'config' => ['secret_key' => 'sk_test', 'currency' => 'usd'], 'is_active' => true]);
    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_9', 'url' => 'https://stripe.test/pay']),
        'api.stripe.com/v1/checkout/sessions/cs_9' => Http::response(['payment_status' => 'paid', 'amount_total' => 4900, 'currency' => 'usd']),
    ]);

    $this->actingAs($this->buyer)->postJson('/api/market/items/aurora/checkout', ['payment_method_id' => $m->id])->assertJsonPath('data.url', 'https://stripe.test/pay');
    $p = MarketPurchase::firstOrFail();
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v1/checkout/sessions')
        && $r['success_url'] === "https://front.test/shopify/purchase/return?purchase={$p->id}&paid=1");

    $this->actingAs($this->buyer)->postJson("/api/market/purchases/{$p->id}/verify")->assertOk();
    expect($p->fresh()->status)->toBe('paid');
});

it('keeps one buyer away from another buyer\'s purchase', function () {
    mkItem();
    $p = MarketPurchase::create(['user_id' => $this->buyer->id, 'market_item_id' => MarketItem::first()->id, 'amount' => 49, 'status' => 'pending']);
    $other = mkUser();

    $this->actingAs($other)->postJson("/api/market/purchases/{$p->id}/proof", ['reference' => 'X'])->assertNotFound();
    $this->actingAs($other)->postJson("/api/market/purchases/{$p->id}/verify")->assertNotFound();
    $this->actingAs($other)->postJson('/api/market/items/aurora/download-link')->assertForbidden();
});

it('pulls the latest GitHub release zip once per tag', function () {
    $item = mkItem(['slug' => 'gh', 'source' => 'github', 'github_repo' => 'me/theme', 'github_token' => 'ghp_x', 'file_path' => null, 'version' => null]);
    Http::fake([
        'api.github.com/repos/me/theme/releases/latest' => Http::response([
            'tag_name' => 'v1.2.0', 'body' => 'Faster cart', 'published_at' => '2026-11-20T10:00:00Z',
            'assets' => [['id' => 5, 'name' => 'notes.txt'], ['id' => 7, 'name' => 'theme.zip']],
        ]),
        'api.github.com/repos/me/theme/releases/assets/7' => Http::response('RELEASEZIP'),
    ]);

    $this->actingAs($this->admin)->postJson("/api/admin/market/items/{$item->id}/sync")->assertOk()->assertJsonPath('data.version', 'v1.2.0');
    $this->actingAs($this->admin)->postJson("/api/admin/market/items/{$item->id}/sync")->assertOk();

    $item->refresh();
    expect($item->release_notes)->toBe('Faster cart')->and(Storage::disk('local')->get($item->file_path))->toBe('RELEASEZIP');
    Http::assertSentCount(3); // latest, asset, latest again (same tag: no second download)
    Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer ghp_x'));
});
