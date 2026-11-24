<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Gateways\GatewayRegistry;
use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(private GatewayRegistry $gateways) {}

    /** Free plans switch at once; paid plans create a pending subscription and hand over to the gateway. */
    public function checkout(Store $store, Plan $plan, ?int $paymentMethodId): array
    {
        abort_unless($plan->is_active, 422, 'This plan is not available.');

        if ((float) $plan->price <= 0) {
            $this->activate(Subscription::create([
                'store_id' => $store->id, 'plan_id' => $plan->id, 'status' => 'pending',
            ]));

            return ['type' => 'activated'];
        }

        $method = $paymentMethodId
            ? $plan->paymentMethods()->where('payment_methods.id', $paymentMethodId)->where('is_active', true)->first()
            : null;

        if (! $method) {
            throw ValidationException::withMessages(['payment_method_id' => 'Choose a payment method offered for this plan.']);
        }

        $subscription = DB::transaction(function () use ($store, $plan, $method) {
            // One open checkout per store: a new choice replaces the previous pending one.
            Subscription::where('store_id', $store->id)->where('status', 'pending')->update(['status' => 'canceled']);

            return Subscription::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'payment_method_id' => $method->id,
                'status' => 'pending',
            ]);
        });

        return $this->gateways->get($method->driver)->checkout($subscription, $method);
    }

    /** Idempotent: confirming an already active subscription changes nothing. */
    public function activate(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription = Subscription::lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->status === 'active') {
                return $subscription;
            }
            abort_unless($subscription->status === 'pending', 422, 'Only a pending subscription can be confirmed.');

            Subscription::where('store_id', $subscription->store_id)
                ->where('status', 'active')
                ->where('id', '!=', $subscription->id)
                ->update(['status' => 'canceled', 'ends_at' => now()]);

            $subscription->forceFill(['status' => 'active', 'started_at' => now()])->save();
            Store::whereKey($subscription->store_id)->update(['plan_id' => $subscription->plan_id]);

            return $subscription;
        });
    }
}
