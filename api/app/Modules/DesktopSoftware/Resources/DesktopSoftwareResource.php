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
            'platform' => $this->platform,
            'platform_name' => $this->platform_name,
            'environment' => $this->environment,
            'is_open_source' => $this->is_open_source,
            'version' => $this->version,
            'status' => $this->status,
            'ordering' => $this->ordering,
            'file_name' => $this->file_path ? $this->downloadFilename() : null,
            'download_url' => $this->file_path
                ? url('/api/desktop-apps/'.$this->slug.'/download')
                : null,
        ];
    }
}
