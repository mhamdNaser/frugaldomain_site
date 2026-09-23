<?php

namespace App\Modules\Account\Repositories\Interfaces;

use App\Modules\User\Models\User;
use Illuminate\Support\Collection;

interface AccountRepositoryInterface
{
    public function register(array $data): User;

    public function updateProfile(User $user, array $data): User;

    public function changePassword(User $user, string $password): void;

    public function deleteAccount(User $user): void;

    /** Counts for the overview cards. */
    public function overview(User $user): array;

    public function favoriteIcons(User $user): Collection;

    /** Returns whether the icon is a favourite after the call. */
    public function toggleFavoriteIcon(User $user, int $iconId): bool;

    public function downloads(User $user, int $limit = 200): Collection;

    public function savedComponents(User $user): Collection;

    /** Returns whether the component is saved after the call. */
    public function toggleSavedComponent(User $user, int $componentId): bool;

    /** Ids only, for marking "saved" buttons across the component gallery. */
    public function savedComponentIds(User $user): array;
}
