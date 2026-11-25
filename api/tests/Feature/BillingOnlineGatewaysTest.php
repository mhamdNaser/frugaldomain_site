<?php

use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\FreePlanService;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ogUser(string $role): User
{
    Role::findOrCreate($role, 'web');
    $u = User::create([
        'first_name' => 'O', 'last_name' => 'G', 'name' => $role.'-'.Str::random(5),
        'email' => Str::random(8).'@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $u->assignRole($role);

    return $u;
}

function ogStore(User $owner): Store
{
    $store = Store::query()->create([
        'id' => (string) Str::uuid(), 'owner_id' => $owner->id,
        'shopify_domain' => Str::random(6).'.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_x'), 'name' => 'S',
    ]);
    app(FreePlanService::class)->subscribe($store);

    return $store;
}

function ogMethod(string $driver, array $config, Plan $plan): PaymentMethod
{
    $m = PaymentMethod::create(['name' => $driver, 'driver' => $driver, 'config' => $config, 'is_active' => true,
        'instructions' => ['en' => 'Send it', 'ar' => 'حوّل']]);
    $plan->paymentMethods()->attach($m->id);

    return $m;
}

beforeEach(function () {
    config(['app.frontend_url' => 'https://front.test']);
    $this->admin = ogUser('admin');
    $this->owner = ogUser('partner');
    $this->store = ogStore($this->owner);
    $this->plan = Plan::create(['name' => 'Pro', 'price' => 19, 'billing_interval' => 'month', 'is_active' => true]);
});

it('offers PayPal, Stripe and Sham Cash next to manual', function () {
    $keys = collect($this->actingAs($this->admin)->getJson('/api/admin/billing/drivers')->assertOk()->json('data'))->pluck('key');

    expect($keys->all())->toEqualCanonicalizing(['manual', 'paypal', 'stripe', 'shamcash']);
});

it('keeps saved secrets when an edit leaves them empty and never lists them', function () {
    $id = $this->actingAs($this->admin)->postJson('/api/admin/payment-methods', [
        'name' => 'PayPal', 'driver' => 'paypal',
        'config' => ['client_id' => 'cid', 'client_secret' => 'top-secret', 'mode' => 'sandbox', 'currency' => 'USD', 'junk' => 'x'],
    ])->assertCreated()->json('data.id');

    $this->actingAs($this->admin)->putJson("/api/admin/payment-methods/{$id}", ['config' => ['currency' => 'EUR', 'client_secret' => '']])->assertOk();

    expect(PaymentMethod::find($id)->config)->toBe(['client_id' => 'cid', 'client_secret' => 'top-secret', 'mode' => 'sandbox', 'currency' => 'EUR']);

    $list = $this->actingAs($this->admin)->getJson('/api/admin/payment-methods')->assertOk();
    expect($list->getContent())->not->toContain('top-secret')
        ->and($list->json('data.0.settings'))->toBe(['client_id' => 'cid', 'mode' => 'sandbox', 'currency' => 'EUR'])
        ->and($list->json('data.0.secrets_set'))->toBe(['client_secret']);
});

it('refuses a PayPal method without its required keys', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/payment-methods', [
        'name' => 'PayPal', 'driver' => 'paypal', 'config' => ['client_id' => 'cid'],
    ])->assertStatus(422)->assertJsonValidationErrors('config.client_secret');
});

it('sends a PayPal checkout to the approval page and activates after capture', function () {
    $m = ogMethod('paypal', ['client_id' => 'cid', 'client_secret' => 'sec', 'mode' => 'sandbox', 'currency' => 'USD'], $this->plan);
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORDER1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']]], 201),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER1/capture' => Http::response([
            'id' => 'ORDER1', 'status' => 'COMPLETED',
            'purchase_units' => [['payments' => ['captures' => [['status' => 'COMPLETED', 'amount' => ['value' => '19.00', 'currency_code' => 'USD']]]]]],
        ], 201),
    ]);

    $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->plan->id, 'payment_method_id' => $m->id])
        ->assertOk()->assertJsonPath('data.type', 'redirect')->assertJsonPath('data.url', 'https://paypal.test/approve');

    $sub = Subscription::where('payment_method_id', $m->id)->firstOrFail();
    expect($sub->reference)->toBe('ORDER1');
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v2/checkout/orders') && $r['purchase_units'][0]['amount']['value'] === '19.00'
        && str_starts_with($r['application_context']['return_url'], 'https://front.test/admin/billing/upgrade'));

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/verify")->assertOk();

    expect($sub->fresh()->status)->toBe('active')->and($this->store->fresh()->plan_id)->toBe($this->plan->id);
});

it('keeps a PayPal subscription pending when the captured amount is wrong', function () {
    $m = ogMethod('paypal', ['client_id' => 'cid', 'client_secret' => 'sec', 'mode' => 'sandbox', 'currency' => 'USD'], $this->plan);
    $sub = Subscription::create(['store_id' => $this->store->id, 'plan_id' => $this->plan->id, 'payment_method_id' => $m->id, 'status' => 'pending', 'reference' => 'ORDER2']);
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER2/capture' => Http::response([
            'status' => 'COMPLETED',
            'purchase_units' => [['payments' => ['captures' => [['status' => 'COMPLETED', 'amount' => ['value' => '1.00', 'currency_code' => 'USD']]]]]],
        ], 201),
    ]);

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/verify")->assertStatus(422);

    expect($sub->fresh()->status)->toBe('pending');
});

it('activates a Stripe subscription only once the session is paid', function () {
    $m = ogMethod('stripe', ['secret_key' => 'sk_test', 'currency' => 'usd'], $this->plan);
    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://stripe.test/pay']),
        'api.stripe.com/v1/checkout/sessions/cs_1' => Http::sequence()
            ->push(['id' => 'cs_1', 'payment_status' => 'unpaid', 'amount_total' => 1900, 'currency' => 'usd'])
            ->push(['id' => 'cs_1', 'payment_status' => 'paid', 'amount_total' => 1900, 'currency' => 'usd']),
    ]);

    $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->plan->id, 'payment_method_id' => $m->id])
        ->assertOk()->assertJsonPath('data.url', 'https://stripe.test/pay');
    $sub = Subscription::where('payment_method_id', $m->id)->firstOrFail();
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v1/checkout/sessions') && $r['line_items'][0]['price_data']['unit_amount'] == 1900
        && $r['client_reference_id'] == $sub->id);

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/verify")->assertStatus(422);
    expect($sub->fresh()->status)->toBe('pending');

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/verify")->assertOk();
    expect($sub->fresh()->status)->toBe('active');
});

it('shows the Sham Cash account code and takes a transfer number or receipt', function () {
    Storage::fake('local');
    $m = ogMethod('shamcash', ['account_code' => 'SC-12345', 'account_name' => 'FrugalDomain'], $this->plan);

    $res = $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->plan->id, 'payment_method_id' => $m->id])
        ->assertOk()->assertJsonPath('data.type', 'instructions')->assertJsonPath('data.account.code', 'SC-12345')
        ->assertJsonPath('data.proof', 'required');
    $subId = $res->json('data.subscription_id');

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$subId}/proof", [])->assertStatus(422);

    $this->actingAs($this->owner)->post("/api/admin/billing/subscriptions/{$subId}/proof", [
        'reference' => 'TX-998', 'receipt' => UploadedFile::fake()->create('r.jpg', 120, 'image/jpeg'),
    ], ['Accept' => 'application/json'])->assertOk();

    $sub = Subscription::find($subId);
    expect($sub->reference)->toBe('TX-998')->and($sub->status)->toBe('pending');
    Storage::disk('local')->assertExists($sub->receipt_path);

    $this->actingAs($this->admin)->get("/api/admin/subscriptions/{$subId}/receipt")->assertOk();
    $this->actingAs($this->owner)->get("/api/admin/subscriptions/{$subId}/receipt")->assertForbidden();
});

it('stops a store owner from touching another store\'s subscription', function () {
    $m = ogMethod('shamcash', ['account_code' => 'SC-1'], $this->plan);
    $otherOwner = ogUser('partner');
    $other = ogStore($otherOwner);
    $sub = Subscription::create(['store_id' => $other->id, 'plan_id' => $this->plan->id, 'payment_method_id' => $m->id, 'status' => 'pending']);

    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/proof", ['reference' => 'X'])->assertNotFound();
    $this->actingAs($this->owner)->postJson("/api/admin/billing/subscriptions/{$sub->id}/verify")->assertNotFound();
});
