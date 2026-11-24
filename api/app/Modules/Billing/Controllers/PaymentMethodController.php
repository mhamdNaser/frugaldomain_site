<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Gateways\GatewayRegistry;
use App\Modules\Billing\Models\PaymentMethod;
use App\Modules\Billing\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/** Super admin: payment methods and which plans offer them. */
class PaymentMethodController extends Controller
{
    public const PLANS_CACHE = 'billing_public_plans';

    public function drivers(GatewayRegistry $registry)
    {
        return response()->json(['data' => $registry->drivers()]);
    }

    public function index()
    {
        return response()->json(['data' => PaymentMethod::orderBy('sort')->orderBy('id')->with('plans:id,name')->get()]);
    }

    public function store(Request $request)
    {
        $method = PaymentMethod::create($this->validated($request));
        Cache::forget(self::PLANS_CACHE);

        return response()->json(['data' => $method], 201);
    }

    public function update(Request $request, int $id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update($this->validated($request, partial: true));
        Cache::forget(self::PLANS_CACHE);

        return response()->json(['data' => $method]);
    }

    public function destroy(int $id)
    {
        PaymentMethod::findOrFail($id)->delete();
        Cache::forget(self::PLANS_CACHE);

        return response()->json(['message' => 'Payment method deleted.']);
    }

    public function syncPlan(Request $request, int $planId)
    {
        $data = $request->validate([
            'payment_method_ids' => ['present', 'array'],
            'payment_method_ids.*' => ['integer', 'exists:payment_methods,id'],
        ]);
        Plan::findOrFail($planId)->paymentMethods()->sync($data['payment_method_ids']);
        Cache::forget(self::PLANS_CACHE);

        return response()->json(['message' => 'Payment methods updated.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        $langs = ['en', 'ar', 'hi', 'zh', 'tr'];

        $data = $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'driver' => [$req, 'string', Rule::in(GatewayRegistry::keys())],
            'config' => ['sometimes', 'nullable', 'array'],
            'instructions' => ['sometimes', 'nullable', 'array'],
            'instructions.*' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (isset($data['instructions'])) {
            $data['instructions'] = collect($data['instructions'])
                ->only($langs)
                ->map(fn ($t) => trim(strip_tags((string) $t)))
                ->all();
        }

        return $data;
    }
}
