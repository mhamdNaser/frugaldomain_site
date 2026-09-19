<?php

namespace App\Modules\Component\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComponentCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'accent' => $this->accent ?: '#38bdf8',
            'icon' => $this->icon,
            'is_active' => (bool) $this->is_active,
            'ordering' => (int) $this->ordering,
            // withCount() is not always applied, so this stays optional.
            'components_count' => $this->when(
                isset($this->components_count),
                fn() => (int) $this->components_count,
            ),
        ];
    }
}
