<?php

namespace App\Modules\Drawing\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Drawing extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PRIVATE = 'private';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'document',
        'document_version',
        'thumbnail_path',
        'width',
        'height',
        'element_count',
        'status',
        'rejection_reason',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'published_at',
        'allow_reuse',
        'tags',
    ];

    protected $casts = [
        'document' => 'array',
        'tags' => 'array',
        'allow_reuse' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * The document itself is deliberately hidden from the default serialised
     * shape. A gallery listing returns hundreds of rows and each document can
     * be hundreds of kilobytes; the detail endpoint adds it back explicitly.
     */
    protected $hidden = ['document'];

    protected static function booted(): void
    {
        static::creating(function (self $drawing) {
            if (! $drawing->slug) {
                $drawing->slug = static::uniqueSlug($drawing->title);
            }
        });
    }

    /** A slug that is unique even when two people pick the same title. */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'drawing';
        $slug = $base;
        $suffix = 1;

        // withTrashed: a soft-deleted row still holds the unique index.
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$suffix);
        }

        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function usages()
    {
        return $this->hasMany(DrawingUsage::class, 'drawing_id');
    }

    /** Only what the public is allowed to see. */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeAwaitingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** Whether this drawing may be placed into someone else's work. */
    public function isReusable(): bool
    {
        return $this->isPublished() && $this->allow_reuse;
    }
}
