<?php

namespace App\Modules\DesktopSoftware\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DesktopSoftwareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'readme' => $this->readme,
            // An object even when empty, so the client can index it by language.
            'translations' => (object) ($this->translations ?? []),
            'platform' => $this->platform,
            'platform_name' => $this->platform_name,
            'environment' => $this->environment,
            'programming_language' => $this->programming_language,
            'is_open_source' => $this->is_open_source,
            'version' => $this->version,
            'status' => $this->status,
            'ordering' => $this->ordering,
            'file_name' => $this->file_path ? $this->downloadFilename() : null,
            'has_image' => (bool) $this->image_path,
            'image_url' => $this->image_path
                && $this->status === 'published'
                ? url('/api/desktop-apps/'.$this->slug.'/image')
                : null,
            'download_url' => $this->file_path
                ? url('/api/desktop-apps/'.$this->slug.'/download')
                : null,
        ];
    }
}
