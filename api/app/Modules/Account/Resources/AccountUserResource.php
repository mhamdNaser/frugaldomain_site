<?php

namespace App\Modules\Account\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in person, as their own account pages see them.
 *
 * Deliberately narrower than the admin UserResource: no permissions list, no
 * store, no address. The account area only needs to greet the user, fill the
 * profile form, and know whether to also offer a link into the admin panel.
 */
class AccountUserResource extends JsonResource
{
    public function toArray($request): array
    {
        $roles = $this->getRoleNames();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'image' => $this->image,
            'roles' => $roles->values(),
            // The dashboard link is offered on these; the API still checks
            // the role on every admin route, so this is a hint, not a key.
            'can_manage' => $roles->contains(fn($role) => in_array($role, ['admin', 'partner'], true)),
            'created_at' => $this->created_at,
        ];
    }
}
