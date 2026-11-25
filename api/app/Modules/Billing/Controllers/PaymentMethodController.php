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

    /** Each method with its non-secret settings; secrets only say whether they are set. */
    public function index(GatewayRegistry $registry)
    {
        $methods = PaymentMethod::orderBy('sort')->orderBy('id')->with('plans:id,name')->get()->map(function (PaymentMethod $m) use ($registry) {
            $fields = in_array($m->driver, GatewayRegistry::keys(), true) ? $registry->fields($m->driver) : [];
            $config = $m->config ?? [];
            $secret = fn ($k) => (bool) ($fields[$k]['secret'] ?? false);

            return $m->toArray() + [
                'settings' => collect($config)->reject(fn ($v, $k) => $secret($k))->all(),
                'secrets_set' => collect($config)->filter(fn ($v, $k) => $secret($k) && $v !== '')->keys()->values()->all(),
            ];
        });

        return response()->json(['data' => $methods]);
    }

    public function store(Request $request, GatewayRegistry $registry)
    {
        $data = $this->validated($request);
        $data['config'] = $this->config($registry, $data['driver'], [], $data['config'] ?? []);
        $method = PaymentMethod::create($data);
        Cache::forget(self::PLANS_CACHE);

        return response()->json(['data' => $method], 201);
    }

    public function update(Request $request, int $id, GatewayRegistry $registry)
    {
        $method = PaymentMethod::findOrFail($id);
        $data = $this->validated($request, partial: true);
        $driver = $data['driver'] ?? $method->driver;
        if (array_key_exists('config', $data) || $driver !== $method->driver) {
            // A new driver starts from scratch; the same driver keeps what the form left empty.
            $saved = $driver === $method->driver ? ($method->config ?? []) : [];
            $data['config'] = $this->config($registry, $driver, $saved, $data['config'] ?? []);
        }
        $method->update($data);
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

    /**
     * Saved config + the posted one: unknown keys dropped, empty values keep the saved
     * value, option fields checked, and every required field present in the result.
     */
    private function config(GatewayRegistry $registry, string $driver, array $saved, array $posted): array
    {
        $fields = $registry->fields($driver);
        $config = array_intersect_key($saved, $fields);
        $errors = [];

        foreach ($fields as $key => $field) {
            $value = trim((string) ($posted[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > 500 || (isset($field['options']) && ! in_array($value, $field['options'], true))) {
                $errors["config.{$key}"] = "The {$field['label']} value is not valid.";
                continue;
            }
            $config[$key] = $value;
        }

        foreach ($fields as $key => $field) {
            if (($field['required'] ?? false) && ($config[$key] ?? '') === '') {
                $errors["config.{$key}"] = "{$field['label']} is required.";
            }
        }

        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return $config;
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
