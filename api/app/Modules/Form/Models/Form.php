<?php

namespace App\Modules\Form\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A form as its owner defined it.
 *
 * Lives in the site's database next to the user who owns it; the answers live
 * on the `forms` connection. Nothing here reaches across that line except by
 * `id`, which is the only link the two halves share.
 */
class Form extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'status',
        'settings',
        'published_at',
        'closes_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'submissions_count' => 'integer',
        'published_at' => 'datetime',
        'closes_at' => 'datetime',
        'last_submission_at' => 'datetime',
    ];

    /**
     * Defaults that keep a half-built form from behaving unpredictably: a new
     * form is a draft, and the settings below are what the public page reads
     * when the owner has not touched them.
     */
    public const DEFAULT_SETTINGS = [
        'success_message' => null,
        'success_message_ar' => null,
        'allow_multiple_per_user' => true,
        'require_login' => false,
        'max_submissions' => null,
        'collect_email_notifications' => false,
        /*
         * The heading of the running-number column in an export. Sheets
         * people already work with tend to have one under their own name -
         * "تسلسل العائلة", "م", "No." - and an export that calls it something
         * else is an export they have to edit before using.
         */
        'serial_label' => null,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $form) {
            $form->uuid ??= (string) Str::uuid();
            $form->slug = $form->slug ?: self::uniqueSlug($form->title ?: 'form');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function settingsWithDefaults(): array
    {
        return array_merge(self::DEFAULT_SETTINGS, $this->settings ?? []);
    }

    /**
     * Whether the public link should accept an answer right now.
     *
     * Three things can close a form and they are checked in one place so the
     * public page, the submit endpoint and the owner's dashboard never
     * disagree about whether it is open.
     */
    public function isAcceptingSubmissions(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return false;
        }

        $max = $this->settingsWithDefaults()['max_submissions'];

        return ! ($max && $this->submissions_count >= (int) $max);
    }

    /** Why it is closed, for a message that tells the visitor something useful. */
    public function closedReason(): ?string
    {
        if ($this->isAcceptingSubmissions()) {
            return null;
        }

        if ($this->status === self::STATUS_DRAFT) {
            return 'not_published';
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return 'expired';
        }

        $max = $this->settingsWithDefaults()['max_submissions'];
        if ($max && $this->submissions_count >= (int) $max) {
            return 'full';
        }

        return 'closed';
    }

    public function markPublished(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_at ??= Carbon::now();
        $this->save();
    }

    /**
     * A readable slug that is still unique.
     *
     * Arabic titles transliterate to nothing useful, so a title with no ASCII
     * left in it falls back to a short random handle rather than producing a
     * link that is just a number.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 60, ''));

        if ($base === '') {
            $base = 'form-' . Str::lower(Str::random(6));
        }

        $slug = $base;
        $suffix = 2;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
