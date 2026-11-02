<?php

namespace App\Modules\DesktopSoftware\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DesktopSoftwareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'readme' => $this->readme,
            'platform' => $this->platform,
            'platform_name' => $this->platform_name,
            'environment' => $this->environment,
            'is_open_source' => $this->is_open_source,
            'version' => $this->version,
            'file_name' => $this->downloadFilename(),
            'download_url' => url('/api/desktop-apps/'.$this->slug.'/download'),
        ];
    }
}
