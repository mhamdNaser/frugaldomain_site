<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'medium_name' => $this->medium_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(), // ترجع كل الأدوار كـ array
            // Without this the dashboard boots with an empty permission set
            // straight after login and the sidebar renders nothing until the
            // user refreshes and /admin/me (UserResource) fills it in.
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
