<?php

namespace App\Modules\Component\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a component for the gallery, the detail page and the admin screen.
 *
 * `source` is intentionally only included when the caller asked for one
 * component: sending every template's full text down with a list of forty
 * would be megabytes of payload nobody has looked at yet.
 */
class ComponentResource extends JsonResource
{
    /** Set by ::withSource() so the detail endpoint opts in explicitly. */
    protected bool $includeSource = false;

    public static function withSource($resource): self
    {
        $instance = new self($resource);
        $instance->includeSource = true;

        return $instance;
    }

    public function toArray($request): array
    {
        $features = $this->relationLoaded('features') ? $this->features : collect();
        $categories = $this->relationLoaded('categories') ? $this->categories : collect();

        $payload = [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'tagline' => $this->tagline,
            'tagline_ar' => $this->tagline_ar,
            'summary' => $this->summary,
            'summary_ar' => $this->summary_ar,

            'type' => $this->file_type,
            'status' => $this->status,
            'version' => $this->version,
            'accent' => $this->accent ?: '#38bdf8',

            'tags' => $this->tags ?: [],
            'stack' => $this->stack ?: [],

            'features' => $features->map(fn($f) => [
                'id' => $f->id,
                'label' => $f->label,
                'label_ar' => $f->label_ar,
                'ordering' => $f->ordering,
            ])->values(),

            'categories' => $categories->map(fn($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'name_ar' => $c->name_ar,
                'accent' => $c->accent,
            ])->values(),

            // Everything the preview frame and the download button need.
            'preview' => [
                // Only html can actually run; jsx/vue are source-only.
                'renderable' => $this->file_type === 'html',
                'url' => $this->file_path ? '/' . ltrim($this->file_path, '/') : null,
                'theme' => $this->preview_theme ?: 'auto',
                'height' => (int) ($this->preview_height ?: 420),
            ],

            'file' => [
                'name' => $this->file_name,
                'size' => $this->file_size,
                'url' => $this->file_path ? '/' . ltrim($this->file_path, '/') : null,
            ],

            'downloads' => (int) $this->downloads,
            'ordering' => (int) $this->ordering,
            'updated' => optional($this->updated_at)->toDateString(),
        ];

        if ($this->includeSource) {
            $payload['source'] = $this->source;
        }

        return $payload;
    }
}
