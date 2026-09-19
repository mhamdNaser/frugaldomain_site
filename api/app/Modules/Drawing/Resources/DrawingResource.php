<?php

namespace App\Modules\Drawing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A drawing as the API returns it.
 *
 * The document is included only when explicitly asked for: a gallery page
 * returns dozens of rows and each document can be hundreds of kilobytes, so
 * shipping it by default would make listing the gallery many times heavier
 * than rendering it.
 */
class DrawingResource extends JsonResource
{
    protected bool $withDocument = false;

    /** Opt this resource into carrying the full editable document. */
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
            'thumbnail' => $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'element_count' => (int) $this->element_count,
            'status' => $this->status,
            'allow_reuse' => (bool) $this->allow_reuse,
            'tags' => $this->tags ?? [],

            'use_count' => (int) $this->use_count,
            'view_count' => (int) $this->view_count,
            'points' => (int) $this->points,

            // Only meaningful to the owner and to an admin; harmless to others.
            'rejection_reason' => $this->rejection_reason,
            'submitted_at' => optional($this->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($this->reviewed_at)->toIso8601String(),
            'published_at' => optional($this->published_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),

            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'document' => $this->when($this->withDocument, fn () => $this->document),
        ];
    }
}
