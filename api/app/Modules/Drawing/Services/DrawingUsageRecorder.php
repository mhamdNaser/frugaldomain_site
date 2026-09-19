<?php

namespace App\Modules\Drawing\Services;

use App\Modules\Drawing\Models\CreatorPointEntry;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Models\DrawingUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Recording that someone used a published drawing, and crediting its author.
 *
 * Every rule that decides whether points are earned lives here rather than in
 * the controller, because this is the one thing in the feature that is worth
 * attacking: points are meant to become money. A rule enforced in a request
 * handler is a rule that the next endpoint forgets.
 */
class DrawingUsageRecorder
{
    /** Points a single first-time use is worth. */
    public const POINTS_PER_USAGE = 1;

    /**
     * Record a use and credit the author, once.
     *
     * Returns a small result array rather than throwing, so a caller can tell
     * "already counted" (a perfectly normal outcome) apart from a real error.
     */
    public function record(Drawing $drawing, int $userId, ?int $usedInDrawingId = null, ?string $ip = null): array
    {
        // A drawing that is not published cannot be used by anyone else, so
        // nothing about it can earn points.
        if (! $drawing->isReusable()) {
            return ['counted' => false, 'reason' => 'not-reusable'];
        }

        // Using your own drawing earns you nothing. Without this, every
        // creator's first move is to place their own work repeatedly - and
        // with a fresh account each time, the unique index alone would not
        // stop them.
        if ($drawing->user_id === $userId) {
            return ['counted' => false, 'reason' => 'own-drawing'];
        }

        try {
            return DB::transaction(function () use ($drawing, $userId, $usedInDrawingId, $ip) {
                // The insert is the check. Relying on the unique index rather
                // than on "does a row exist?" closes the race where two
                // requests arrive together and both see no row.
                $usage = DrawingUsage::create([
                    'drawing_id' => $drawing->id,
                    'user_id' => $userId,
                    'used_in_drawing_id' => $usedInDrawingId,
                    'points_awarded' => self::POINTS_PER_USAGE,
                    'ip_address' => $ip,
                    'used_at' => now(),
                ]);

                CreatorPointEntry::create([
                    'user_id' => $drawing->user_id,
                    'amount' => self::POINTS_PER_USAGE,
                    'reason' => CreatorPointEntry::REASON_USAGE,
                    'source_type' => DrawingUsage::class,
                    'source_id' => $usage->id,
                    'drawing_id' => $drawing->id,
                    'note' => 'First use by another account',
                ]);

                // Counter caches, moved atomically with the rows they count.
                $drawing->newQuery()
                    ->whereKey($drawing->id)
                    ->update([
                        'use_count' => DB::raw('use_count + 1'),
                        'points' => DB::raw('points + ' . (int) self::POINTS_PER_USAGE),
                    ]);

                return ['counted' => true, 'points' => self::POINTS_PER_USAGE];
            });
        } catch (QueryException $e) {
            // 23000 is the integrity-constraint class, which here means the
            // unique index did its job: this pair has already been counted.
            if ($this->isDuplicate($e)) {
                return ['counted' => false, 'reason' => 'already-counted'];
            }
            throw $e;
        }
    }

    /**
     * Whether the failure was the duplicate key we expect, rather than
     * something that deserves to surface as a 500.
     */
    private function isDuplicate(QueryException $e): bool
    {
        if ((string) $e->getCode() === '23000') {
            return true;
        }

        // SQLite reports the same condition differently, and the test suite
        // runs on it.
        return str_contains(strtolower($e->getMessage()), 'unique constraint failed');
    }
}
