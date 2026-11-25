<?php

namespace App\Modules\Market\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** Public view of an item. `owned` is set by the controller for the signed-in visitor. */
class MarketItemResource extends JsonResource
{
    public bool $owned = false;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'readme' => $this->readme,
            'features' => $this->features ?? [],
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'translations' => (object) ($this->translations ?? []),
            'price' => (string) $this->price,
            'is_free' => $this->isFree(),
            'preview_url' => $this->preview_url,
            'preview_password' => $this->preview_password,
            'logo_url' => $this->logo_path ? url('/api/market/items/'.$this->slug.'/logo').'?v='.$this->updated_at?->timestamp : null,
            'version' => $this->version,
            'release_notes' => $this->release_notes,
            'released_at' => $this->released_at,
            'has_file' => (bool) $this->file_path,
            'owned' => $this->owned,
        ];
    }
}
