<?php

namespace App\Modules\Drawing\Services;

use App\Modules\Drawing\Models\Drawing;
use Illuminate\Support\Facades\DB;

/**
 * The publish workflow: private -> pending -> published or rejected.
 *
 * Nothing becomes publicly visible without an admin passing it, because the
 * editor lets anyone paste an image from their clipboard. Auto-publishing
 * would put copyrighted material on the site under the site's own name, and
 * "the user uploaded it" is a weak defence once it is being served from your
 * gallery and offered to others for reuse.
 */
class DrawingModerationService
{
    /** Bonus awarded once, the first time a drawing passes review. */
    public const PUBLISH_BONUS = 5;

    /**
     * The owner asks for their drawing to be published.
     *
     * Re-submitting a rejected drawing is allowed: the author is expected to
     * fix whatever it was rejected for and try again.
     */
    public function submit(Drawing $drawing): array
    {
        if ($drawing->status === Drawing::STATUS_PENDING) {
            return ['ok' => false, 'reason' => 'already-pending'];
        }
        if ($drawing->status === Drawing::STATUS_PUBLISHED) {
            return ['ok' => false, 'reason' => 'already-published'];
        }

        $drawing->update([
            'status' => Drawing::STATUS_PENDING,
            'submitted_at' => now(),
            // Clear the previous verdict so the queue does not show a stale
            // rejection beside a fresh request.
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return ['ok' => true];
    }

    /**
     * The drawing's contents changed, so any approval it had no longer applies.
     *
     * Separate from `submit()` because that one refuses a published drawing -
     * which is right when a user clicks "publish" on something already live,
     * and exactly wrong here. Without this, approval would be a one-time gate:
     * publish something harmless, wait for the tick, then swap the contents
     * while it stays in the gallery.
     */
    public function requeueAfterEdit(Drawing $drawing): array
    {
        // Something never reviewed and never submitted just stays private.
        if ($drawing->status === Drawing::STATUS_PRIVATE) {
            return ['ok' => false, 'reason' => 'still-private'];
        }
        if ($drawing->status === Drawing::STATUS_PENDING) {
            return ['ok' => true, 'reason' => 'already-pending'];
        }

        $drawing->update([
            'status' => Drawing::STATUS_PENDING,
            'submitted_at' => now(),
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return ['ok' => true];
    }

    /** An admin passes the drawing; it becomes publicly visible. */
    public function approve(Drawing $drawing, int $reviewerId): array
    {
        if ($drawing->status === Drawing::STATUS_PUBLISHED) {
            return ['ok' => false, 'reason' => 'already-published'];
        }

        return DB::transaction(function () use ($drawing, $reviewerId) {
            // `published_at` doubles as "has this ever been published", which
            // is what keeps the bonus a one-off across re-reviews.
            $firstTime = $drawing->published_at === null;

            $drawing->update([
                'status' => Drawing::STATUS_PUBLISHED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'published_at' => $drawing->published_at ?? now(),
                'rejection_reason' => null,
            ]);

            if ($firstTime && self::PUBLISH_BONUS > 0) {
                \App\Modules\Drawing\Models\CreatorPointEntry::create([
                    'user_id' => $drawing->user_id,
                    'amount' => self::PUBLISH_BONUS,
                    'reason' => \App\Modules\Drawing\Models\CreatorPointEntry::REASON_PUBLISH,
                    'drawing_id' => $drawing->id,
                    'created_by' => $reviewerId,
                    'note' => 'Passed review',
                ]);

                $drawing->newQuery()->whereKey($drawing->id)->update([
                    'points' => DB::raw('points + ' . (int) self::PUBLISH_BONUS),
                ]);
            }

            return ['ok' => true, 'bonus' => $firstTime ? self::PUBLISH_BONUS : 0];
        });
    }

    /**
     * An admin turns the drawing down.
     *
     * A reason is required: a rejection with no explanation gives the author
     * nothing to act on and generates a support message every time.
     */
    public function reject(Drawing $drawing, int $reviewerId, string $reason): array
    {
        $drawing->update([
            'status' => Drawing::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return ['ok' => true];
    }

    /**
     * Take a published drawing back out of public view.
     *
     * Used when something was approved that should not have been. Existing
     * usages and the points already earned are deliberately left alone -
     * clawing back points for work someone did in good faith would be worse
     * than the mistake.
     */
    public function unpublish(Drawing $drawing, int $reviewerId, ?string $reason = null): array
    {
        $drawing->update([
            'status' => Drawing::STATUS_REJECTED,
            'rejection_reason' => $reason ?? 'Removed from the gallery by an administrator.',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return ['ok' => true];
    }
}
