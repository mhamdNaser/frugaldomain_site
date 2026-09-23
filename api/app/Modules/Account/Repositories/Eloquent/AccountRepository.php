<?php

namespace App\Modules\Account\Repositories\Eloquent;

use App\Modules\Account\Models\SavedComponent;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
use App\Modules\Component\Models\Component;
use App\Modules\Drawing\Models\CreatorPointEntry;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconDownloads;
use App\Modules\Icon\Models\IconFavorite;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AccountRepository implements AccountRepositoryInterface
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // The users table keeps first and last names for the accounts an
            // admin creates; a sign-up gives one name, so it is split once
            // here and the person can correct it from their profile.
            $parts = preg_split('/\s+/u', trim($data['name']), 2);

            $user = User::create([
                'name' => $data['name'],
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? null,
                'email' => $data['email'],
                // The model casts password as "hashed".
                'password' => $data['password'],
                'status' => true,
            ]);

            // Every self-registered account is a plain user. The role is what
            // keeps it out of the admin and partner routes, so it is assigned
            // here rather than trusted from the request.
            $role = Role::where('name', 'user')->first();
            if ($role) {
                $user->assignRole($role);
            }

            return $user;
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'],
        ]);

        // A changed address has not been confirmed by anyone yet.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }

    public function changePassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->save();

        // Sign out everywhere else: a password is usually changed because
        // someone else might know the old one.
        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn($query) => $query->where('id', '!=', $current->id))
            ->delete();
    }

    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            // Favourites and saved components cascade with the user row;
            // icon_favorites is soft-deleting, so clear it explicitly.
            IconFavorite::withTrashed()->where('user_id', $user->id)->forceDelete();
            // Downloads are site statistics: keep the count, drop the person.
            IconDownloads::where('user_id', $user->id)->update(['user_id' => null]);
            $user->syncRoles([]);
            $user->delete();
        });
    }

    public function overview(User $user): array
    {
        $drawings = Drawing::where('user_id', $user->id);

        return [
            'favorite_icons' => IconFavorite::where('user_id', $user->id)->count(),
            'downloads' => IconDownloads::where('user_id', $user->id)->count(),
            'saved_components' => SavedComponent::where('user_id', $user->id)->count(),
            'drawings' => (clone $drawings)->count(),
            'published_drawings' => (clone $drawings)->where('status', Drawing::STATUS_PUBLISHED)->count(),
            'pending_drawings' => (clone $drawings)->where('status', Drawing::STATUS_PENDING)->count(),
            'creator_points' => CreatorPointEntry::balanceFor($user->id),
        ];
    }

    public function favoriteIcons(User $user): Collection
    {
        return Icon::query()
            ->whereIn('id', IconFavorite::where('user_id', $user->id)->select('icon_id'))
            ->with(['files', 'category'])
            ->get()
            ->sortByDesc(fn($icon) => $icon->id)
            ->values();
    }

    public function toggleFavoriteIcon(User $user, int $iconId): bool
    {
        // icon_favorites soft-deletes but also has unique(user_id, icon_id):
        // a soft-deleted row still occupies the index, so re-adding an icon
        // that was once removed must bring that row back, not insert a new one.
        $favorite = IconFavorite::withTrashed()
            ->where('user_id', $user->id)
            ->where('icon_id', $iconId)
            ->first();

        if ($favorite && !$favorite->trashed()) {
            $favorite->forceDelete();
            return false;
        }

        if ($favorite) {
            $favorite->restore();
            $favorite->touch();
            return true;
        }

        IconFavorite::create(['user_id' => $user->id, 'icon_id' => $iconId]);
        return true;
    }

    public function downloads(User $user, int $limit = 200): Collection
    {
        return IconDownloads::with(['icon.files', 'icon.category'])
            ->where('user_id', $user->id)
            ->latest('downloaded_at')
            ->limit($limit)
            ->get();
    }

    public function savedComponents(User $user): Collection
    {
        return SavedComponent::with(['component.categories'])
            ->where('user_id', $user->id)
            // A component the admin has since unpublished is not shown: its
            // page would 404. The bookmark stays, and returns with it.
            ->whereHas('component', fn($query) => $query->published())
            ->latest()
            ->get();
    }

    public function toggleSavedComponent(User $user, int $componentId): bool
    {
        $saved = SavedComponent::where('user_id', $user->id)
            ->where('component_id', $componentId)
            ->first();

        if ($saved) {
            $saved->delete();
            return false;
        }

        // Only a published component can be saved; its id is otherwise a
        // guess at a draft the user has never seen.
        Component::published()->findOrFail($componentId);

        SavedComponent::create(['user_id' => $user->id, 'component_id' => $componentId]);
        return true;
    }

    public function savedComponentIds(User $user): array
    {
        return SavedComponent::where('user_id', $user->id)->pluck('component_id')->all();
    }
}
