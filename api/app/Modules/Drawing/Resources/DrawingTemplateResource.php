<?php

namespace App\Modules\Drawing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DrawingTemplateResource extends JsonResource
{
    protected bool $withDocument = false;

    public function withDocument(bool $include = true): self
    {
        $this->withDocument = $include;

        return $this;
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'use_count' => (int) $this->use_count,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'tags' => $this->tags ?? [],
            'thumbnail' => $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null,
            'document' => $this->when($this->withDocument, fn () => $this->document),
        ];
    }
}
