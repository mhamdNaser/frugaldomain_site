<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /** Store owner: active plans with their active payment methods, and which one is current. */
    public function plans(Request $request)
    {
        $plans = Cache::remember(PaymentMethodController::PLANS_CACHE, 600, fn () => Plan::where('is_active', true)
            ->orderBy('price')
            ->with(['paymentMethods' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->get()
            ->map(fn (Plan $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'billing_interval' => $p->billing_interval,
                'trial_days' => $p->trial_days,
                'max_team_members' => $p->max_team_members,
                'description' => $p->description,
                'features' => $p->features,
                'payment_methods' => $p->paymentMethods->map(fn ($m) => [
                    'id' => $m->id, 'name' => $m->name, 'driver' => $m->driver,
                ])->values(),
            ])->values()->all());

        $current = optional($request->user()->store()->first())->plan_id;

        return response()->json(['data' => array_map(fn ($p) => $p + ['current' => $p['id'] === $current], $plans)]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'payment_method_id' => ['nullable', 'integer'],
        ]);
        $store = $request->user()->store()->first();
        abort_unless($store, 404, 'Connect a store first.');

        $result = $this->subscriptions->checkout($store, Plan::findOrFail($data['plan_id']), $data['payment_method_id'] ?? null);

        return response()->json(['data' => $result]);
    }

    /** Super admin: confirm a manual payment. Safe to repeat. */
    public function confirm(int $id)
    {
        $subscription = $this->subscriptions->activate(Subscription::findOrFail($id));

        return response()->json(['data' => $subscription]);
    }
}
