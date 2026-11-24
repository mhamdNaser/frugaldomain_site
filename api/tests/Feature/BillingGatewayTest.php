<?php

use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\FreePlanService;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function billingUser(string $role): User
{
    Role::findOrCreate($role, 'web');
    $u = User::create([
        'first_name' => 'B', 'last_name' => 'U', 'name' => $role.'-'.Str::random(5),
        'email' => Str::random(8).'@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $u->assignRole($role);

    return $u;
}

beforeEach(function () {
    $this->admin = billingUser('admin');
    $this->owner = billingUser('partner');
    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $this->owner->id,
        'shopify_domain' => 'bill.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_bill'),
        'name' => 'Bill',
    ]);
    app(FreePlanService::class)->subscribe($this->store);
    $this->paid = Plan::create(['name' => 'Pro', 'price' => 19, 'billing_interval' => 'month', 'is_active' => true]);
});

function manualMethod($test, array $extra = []): int
{
    return $test->actingAs($test->admin)->postJson('/api/admin/payment-methods', array_merge([
        'name' => 'Bank transfer',
        'driver' => 'manual',
        'is_active' => true,
        'instructions' => ['en' => 'Transfer to IBAN X', 'ar' => 'حوّل إلى IBAN X'],
    ], $extra))->assertCreated()->json('data.id');
}

it('lists the manual driver for the admin form', function () {
    $this->actingAs($this->admin)->getJson('/api/admin/billing/drivers')
        ->assertOk()->assertJsonPath('data.0.key', 'manual');
});

it('lets an admin create a manual payment method and link it to a plan', function () {
    $id = manualMethod($this);

    $this->actingAs($this->admin)->putJson("/api/admin/plans/{$this->paid->id}/payment-methods", ['payment_method_ids' => [$id]])
        ->assertOk();

    expect($this->paid->fresh()->paymentMethods()->pluck('payment_methods.id')->all())->toBe([$id]);
});

it('forbids partners from managing payment methods', function () {
    $this->actingAs($this->owner)->postJson('/api/admin/payment-methods', ['name' => 'x', 'driver' => 'manual'])->assertForbidden();
});

it('rejects an unknown driver', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/payment-methods', ['name' => 'x', 'driver' => 'bitcoin-cash'])
        ->assertStatus(422)->assertJsonValidationErrors(['driver']);
});

it('shows a partner the active plans with their active payment methods', function () {
    $id = manualMethod($this);
    $off = manualMethod($this, ['name' => 'Off', 'is_active' => false]);
    $this->paid->paymentMethods()->sync([$id, $off]);

    $plans = collect($this->actingAs($this->owner)->getJson('/api/admin/billing/plans')->assertOk()->json('data'));
    $pro = $plans->firstWhere('id', $this->paid->id);

    expect(collect($pro['payment_methods'])->pluck('id')->all())->toBe([$id])
        ->and($plans->firstWhere('name', 'Free')['current'])->toBeTrue();
});

it('creates a pending subscription with instructions on manual checkout', function () {
    $id = manualMethod($this);
    $this->paid->paymentMethods()->sync([$id]);

    $res = $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->paid->id, 'payment_method_id' => $id])
        ->assertOk()->assertJsonPath('data.type', 'instructions');

    expect($res->json('data.instructions.en'))->toBe('Transfer to IBAN X')
        ->and(Subscription::where('store_id', $this->store->id)->where('status', 'pending')->count())->toBe(1)
        ->and(Subscription::where('store_id', $this->store->id)->where('status', 'active')->value('plan_id'))->not->toBe($this->paid->id);
});

it('refuses checkout with a method that is not linked to the plan or is inactive', function () {
    $linked = manualMethod($this);
    $other = manualMethod($this, ['name' => 'Other']);
    $off = manualMethod($this, ['name' => 'Off', 'is_active' => false]);
    $this->paid->paymentMethods()->sync([$linked, $off]);

    foreach ([$other, $off] as $bad) {
        $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->paid->id, 'payment_method_id' => $bad])
            ->assertStatus(422);
    }
});

it('activates a confirmed manual payment once, even when confirmed twice', function () {
    $id = manualMethod($this);
    $this->paid->paymentMethods()->sync([$id]);
    $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->paid->id, 'payment_method_id' => $id])->assertOk();
    $pending = Subscription::where('store_id', $this->store->id)->where('status', 'pending')->firstOrFail();

    $this->actingAs($this->admin)->postJson("/api/admin/subscriptions/{$pending->id}/confirm")->assertOk();
    $this->actingAs($this->admin)->postJson("/api/admin/subscriptions/{$pending->id}/confirm")->assertOk();

    $active = Subscription::where('store_id', $this->store->id)->where('status', 'active')->get();
    expect($active)->toHaveCount(1)
        ->and($active->first()->plan_id)->toBe($this->paid->id)
        ->and($this->store->fresh()->plan_id)->toBe($this->paid->id);
});

it('lets a store upgrade again after an earlier upgrade was canceled', function () {
    $id = manualMethod($this);
    $this->paid->paymentMethods()->sync([$id]);

    foreach ([1, 2] as $round) {
        $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->paid->id, 'payment_method_id' => $id])->assertOk();
        $pending = Subscription::where('store_id', $this->store->id)->where('status', 'pending')->latest('id')->firstOrFail();
        $this->actingAs($this->admin)->postJson("/api/admin/subscriptions/{$pending->id}/confirm")->assertOk();
        app(FreePlanService::class)->plan(); // free plan still exists
        DB::table('subscriptions')->where('id', $pending->id)->update(['status' => 'canceled']);
        app(FreePlanService::class)->subscribe($this->store);
    }

    expect(Subscription::where('store_id', $this->store->id)->where('status', 'active')->count())->toBe(1)
        ->and(Subscription::where('store_id', $this->store->id)->where('status', 'canceled')->count())->toBe(4);
});

it('switches to the free plan immediately without a payment method', function () {
    $id = manualMethod($this);
    $this->paid->paymentMethods()->sync([$id]);
    $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $this->paid->id, 'payment_method_id' => $id]);
    $pending = Subscription::where('store_id', $this->store->id)->where('status', 'pending')->firstOrFail();
    $this->actingAs($this->admin)->postJson("/api/admin/subscriptions/{$pending->id}/confirm");

    $free = app(FreePlanService::class)->plan();
    $this->actingAs($this->owner)->postJson('/api/admin/billing/checkout', ['plan_id' => $free->id])
        ->assertOk()->assertJsonPath('data.type', 'activated');

    expect(Subscription::where('store_id', $this->store->id)->where('status', 'active')->value('plan_id'))->toBe($free->id);
});

it('shows a renamed or disabled plan to partners right away', function () {
    $this->actingAs($this->owner)->getJson('/api/admin/billing/plans')->assertOk();

    $this->actingAs($this->admin)->putJson("/api/admin/plans/{$this->paid->id}", ['name' => 'Pro Max'])->assertOk();
    $names = collect($this->actingAs($this->owner)->getJson('/api/admin/billing/plans')->json('data'))->pluck('name');
    expect($names)->toContain('Pro Max');

    $this->actingAs($this->admin)->patchJson("/api/admin/plans/{$this->paid->id}/status")->assertOk();
    $names = collect($this->actingAs($this->owner)->getJson('/api/admin/billing/plans')->json('data'))->pluck('name');
    expect($names)->not->toContain('Pro Max');
});

it('returns each plan with its linked payment method ids for the admin form', function () {
    $id = manualMethod($this);
    $this->paid->paymentMethods()->sync([$id]);

    $this->actingAs($this->admin)->getJson("/api/admin/plans/{$this->paid->id}")
        ->assertOk()->assertJsonPath('data.payment_method_ids', [$id]);
});
