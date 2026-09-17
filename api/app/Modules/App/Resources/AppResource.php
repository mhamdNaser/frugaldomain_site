<?php

namespace App\Modules\App\Resources;

use App\Modules\App\Models\AppImage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes an app exactly the way the public site already consumes it.
 *
 * AppsGallery reads: slug, name, tagline, summary, type, status, tags[], cover,
 * accent, preview.
 * AppDetails additionally reads: version, updated, highlights[], stack[],
 * docs[], repository, preview.embed.
 *
 * Those key names are therefore part of the contract and are emitted verbatim,
 * with the richer new fields (id, features, images, archive) added alongside
 * for the admin screen.
 */
class AppResource extends JsonResource
{
    public function toArray($request): array
    {
        $features = $this->relationLoaded('features')
            ? $this->features
            : collect();

        $images = $this->relationLoaded('images')
            ? $this->images
            : collect();

        $main = $images->firstWhere('role', AppImage::ROLE_MAIN);

        $secondary = $images
            ->where('role', AppImage::ROLE_SECONDARY)
            ->sortBy('ordering')
            ->values();

        $cover = $this->cover ?: ($main?->path ?: $this->main_image);

        return [
            // ---- contract with the existing public pages -------------------
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'summary' => $this->summary,
            'type' => $this->type,
            'status' => $this->status,
            'version' => $this->version,
            // The JSON catalogue called this "updated" and rendered it raw.
            'updated' => optional($this->updated_on)->format('Y-m-d'),
            'cover' => $this->url($cover),
            'accent' => $this->accent,
            'tags' => $this->tags ?? [],
            'preview' => [
                'mode' => $this->preview_mode,
                'url' => $this->resolvedPreviewUrl(),
                'embed' => (bool) $this->preview_embed,
                'openInNewTab' => (bool) $this->preview_open_in_new_tab,
            ],
            'repository' => $this->repository,
            // Features are the highlights the detail page lists.
            'highlights' => $features->pluck('label')->values()->all(),
            'stack' => $this->stack ?? [],
            'docs' => $this->docs ?? [],

            // ---- additions used by the admin screen ------------------------
            'id' => $this->id,
            'subdomain' => $this->subdomain,
            'ordering' => $this->ordering,
            'features' => $features->map(fn($feature) => [
                'id' => $feature->id,
                'label' => $feature->label,
                'ordering' => $feature->ordering,
            ])->values()->all(),
            'mainImage' => $main ? $this->imagePayload($main) : null,
            'secondaryImages' => $secondary->map(fn($image) => $this->imagePayload($image))->all(),
            'archive' => $this->archive_path ? [
                'path' => $this->archive_path,
                'url' => $this->url($this->archive_path),
                'name' => $this->archive_name,
                'size' => $this->archive_size,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function imagePayload(AppImage $image): array
    {
        return [
            'id' => $image->id,
            'path' => $image->path,
            'url' => $this->url($image->path),
            'role' => $image->role,
            'alt' => $image->alt,
            'ordering' => $image->ordering,
        ];
    }

    /**
     * The preview location. A stored URL always wins; otherwise it is derived
     * from the mode so the front-end never has to guess.
     */
    protected function resolvedPreviewUrl(): ?string
    {
        if ($this->preview_url) {
            return $this->preview_url;
        }

        if ($this->preview_mode === 'subdomain' && $this->subdomain) {
            return $this->subdomain;
        }

        return $this->slug ? '/apps/' . $this->slug . '/' : null;
    }

    /** Turns a public-relative stored path into something the browser can load. */
    protected function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('#^(?:https?:)?//#i', $path)) {
            return $path;
        }

        return asset($path);
    }
}
