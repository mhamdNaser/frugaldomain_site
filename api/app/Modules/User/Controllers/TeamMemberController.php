<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\StoreRole;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff members of the current store. How many a store may have comes from its plan
 * (plans.max_team_members).
 */
class TeamMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);

        $members = User::query()
            ->where('store_id', $store->id)
            ->with('storeRoles:id,name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'data' => $members->map(fn (User $user) => $this->present($user))->values(),
            'meta' => $this->limits($store),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);
        $data = $this->validated($request, $store);
        $limits = $this->limits($store);

        if ($limits['used'] >= $limits['max']) {
            throw ValidationException::withMessages([
                'email' => $limits['max'] === 0
                    ? 'Your plan does not include team members. Upgrade your plan to add staff.'
                    : "Your plan allows {$limits['max']} team member(s). Remove one or upgrade your plan.",
            ]);
        }

        $user = DB::transaction(function () use ($data, $store) {
            $user = User::query()->create([
                'store_id' => $store->id,
                'name' => $data['email'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'password' => $data['password'],
                'status' => $data['status'] ?? true,
            ]);

            // The partner role lets staff through the store routes; what they can do there
            // is decided by their store roles (EnforceStorePermissions).
            $user->assignRole('partner');
            $user->storeRoles()->sync($data['role_ids'] ?? []);

            return $user;
        });

        return response()->json([
            'message' => 'Team member added successfully.',
            'data' => $this->present($user->load('storeRoles:id,name')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->currentStore($request);
        $user = $this->member($store, $id);
        $data = $this->validated($request, $store, $user);

        DB::transaction(function () use ($user, $data) {
            $user->fill(array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'name' => $data['email'] ?? null,
            ], fn ($value) => $value !== null));

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            if (array_key_exists('status', $data)) {
                $user->status = (bool) $data['status'];
            }

            $user->save();

            if (array_key_exists('role_ids', $data)) {
                $user->storeRoles()->sync($data['role_ids'] ?? []);
            }

            // A disabled account or a new password signs the member out everywhere.
            if (!$user->status || filled($data['password'] ?? null)) {
                $user->tokens()->delete();
            }
        });

        return response()->json([
            'message' => 'Team member updated successfully.',
            'data' => $this->present($user->fresh()->load('storeRoles:id,name')),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $this->currentStore($request);
        $user = $this->member($store, $id);

        abort_if($user->is($request->user()), 422, 'You cannot remove your own account.');

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Team member removed successfully.']);
    }

    private function currentStore(Request $request): Store
    {
        $store = $request->user()?->store;
        abort_if(!$store, 404, 'No store is linked to the authenticated user.');

        return $store;
    }

    private function member(Store $store, int $id): User
    {
        return User::query()->where('store_id', $store->id)->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Store $store, ?User $user = null): array
    {
        $creating = $user === null;

        return $request->validate([
            'first_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'last_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [
                $creating ? 'required' : 'sometimes', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
                Rule::unique('users', 'name')->ignore($user?->id),
            ],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8'],
            'status' => ['sometimes', 'boolean'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', Rule::exists(StoreRole::class, 'id')->where('store_id', $store->id)],
        ]);
    }

    /**
     * @return array{max: int, used: int}
     */
    private function limits(Store $store): array
    {
        return [
            'max' => (int) ($store->plan?->max_team_members ?? 0),
            'used' => User::query()->where('store_id', $store->id)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => (bool) $user->status,
            'roles' => $user->storeRoles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
            'created_at' => $user->created_at,
        ];
    }
}
