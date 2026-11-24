<?php

namespace App\Modules\User\Team;

use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a staff member's store role on every API request (owners and admins pass through):
 * - blocks actions the role does not grant (unknown store-dashboard routes are denied too);
 * - drops hidden fields from the request and removes them from the response "data".
 */
class EnforceStorePermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if (!$user instanceof User || !$user->isStoreStaff()) {
            return $next($request);
        }

        if (!$user->status) {
            abort(403, 'Your account has been disabled by the store owner.');
        }

        // A raw GraphQL passthrough runs with the owner's Shopify token and would bypass
        // every section and hidden-field rule below, so staff may never send one.
        abort_if($request->has('shopify_sync'), 403, 'You do not have permission to do this.');

        $permission = StorePermissions::requiredFor($request);

        if ($permission === null) {
            // Routes outside the store dashboard (own account, login...) stay open.
            abort_if($this->isStoreDashboardRoute($request), 403, 'You do not have permission to do this.');

            return $next($request);
        }

        abort_unless($user->canInStore($permission), 403, 'You do not have permission to do this.');

        $section = StorePermissions::sectionOf($permission);
        $hidden = $section ? StorePermissions::hiddenKeys($section, $user->storePermissions()) : [];

        if ($hidden === []) {
            return $next($request);
        }

        $request->replace(self::strip($request->input(), $hidden));

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);

            if (is_array($payload) && array_key_exists('data', $payload)) {
                $payload['data'] = self::strip($payload['data'], $hidden);
                $response->setData($payload);
            }
        }

        return $response;
    }

    private function isStoreDashboardRoute(Request $request): bool
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:') && str_contains($middleware, 'partner')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the keys at every depth (variants inside a product, items inside an order).
     *
     * @param array<int, string> $keys
     */
    public static function strip(mixed $value, array $keys): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array($key, $keys, true)) {
                unset($value[$key]);
                continue;
            }

            $value[$key] = self::strip($item, $keys);
        }

        return $value;
    }
}
