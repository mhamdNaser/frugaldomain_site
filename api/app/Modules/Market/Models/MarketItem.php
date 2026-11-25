<?php

namespace App\Modules\Market\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** A Shopify theme or app for sale. */
class MarketItem extends Model
{
    protected $fillable = [
        'type', 'slug', 'name', 'summary', 'readme', 'features', 'seo_title', 'seo_description',
        'translations', 'price', 'preview_url', 'preview_password', 'logo_path', 'source', 'file_path',
        'github_repo', 'github_token', 'version', 'release_notes', 'released_at', 'checked_at', 'status', 'ordering',
    ];

    protected $hidden = ['file_path', 'github_token', 'logo_path'];

    protected $casts = [
        'features' => 'array',
        'translations' => 'array',
        'price' => 'decimal:2',
        'github_token' => 'encrypted',
        'released_at' => 'datetime',
        'checked_at' => 'datetime',
        'ordering' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $item): void {
            foreach (['file_path', 'logo_path'] as $field) {
                if ($item->wasChanged($field) && $item->getOriginal($field)) {
                    Storage::disk('local')->delete($item->getOriginal($field));
                }
            }
        });

        static::deleting(function (self $item): void {
            Storage::disk('local')->delete(array_filter([$item->file_path, $item->logo_path]));
        });
    }

    public function purchases()
    {
        return $this->hasMany(MarketPurchase::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function downloadFilename(): string
    {
        return $this->slug.($this->version ? '-'.preg_replace('/[^A-Za-z0-9._-]/', '', $this->version) : '').'.zip';
    }

    public function ownedBy(?\App\Modules\User\Models\User $user): bool
    {
        return $user !== null && $this->purchases()->where('user_id', $user->id)->where('status', 'paid')->exists();
    }
}
