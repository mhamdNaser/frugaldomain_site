<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\StoreRole;
use App\Modules\User\Team\StorePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Roles a store owner defines for their staff. A role can be given to many members and
 * a member can hold many roles (their permissions add up).
 */
class TeamRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = StoreRole::query()
            ->where('store_id', $this->currentStore($request)->id)
            ->with('users:id,first_name,last_name,email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $roles->map(fn (StoreRole $role) => $this->present($role))->values(),
        ]);
    }

    /** Every section, action and field a role can be given, for the role editor. */
    public function catalog(): JsonResponse
    {
        return response()->json(['data' => StorePermissions::catalog()]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);
        $data = $this->validated($request, $store);

        $role = StoreRole::query()->create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
        ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $this->present($role->load('users:id,first_name,last_name,email')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->currentStore($request);
        $role = $this->role($store, $id);
        $data = $this->validated($request, $store, $role);

        $role->fill(array_filter([
            'name' => $data['name'] ?? null,
            'permissions' => array_key_exists('permissions', $data) ? array_values(array_unique($data['permissions'] ?? [])) : null,
        ], fn ($value) => $value !== null))->save();

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => $this->present($role->fresh()->load('users:id,first_name,last_name,email')),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->role($this->currentStore($request), $id)->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    private function currentStore(Request $request): Store
    {
        $store = $request->user()?->store;
        abort_if(!$store, 404, 'No store is linked to the authenticated user.');

        return $store;
    }

    private function role(Store $store, int $id): StoreRole
    {
        return StoreRole::query()->where('store_id', $store->id)->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Store $store, ?StoreRole $role = null): array
    {
        return $request->validate([
            'name' => [
                $role ? 'sometimes' : 'required', 'string', 'max:100',
                Rule::unique('store_roles', 'name')->where('store_id', $store->id)->ignore($role?->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(StorePermissions::allKeys())],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(StoreRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions ?? [],
            'users' => $role->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => trim("{$user->first_name} {$user->last_name}") ?: $user->email,
            ])->values(),
            'created_at' => $role->created_at,
        ];
    }
}
